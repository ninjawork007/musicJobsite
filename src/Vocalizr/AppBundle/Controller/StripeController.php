<?php

namespace Vocalizr\AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Vocalizr\AppBundle\Entity\UserInfo;
use Vocalizr\AppBundle\Entity\UserStripeIdentity;
use Vocalizr\AppBundle\Event\StripeWebhookEvent;
use Vocalizr\AppBundle\Exception\WebhookProcessingException;
use Vocalizr\AppBundle\Model\StripeModel;

/**
 * Class StripeController
 * @package Vocalizr\AppBundle\Controller
 */
class StripeController extends Controller
{
    /**
     * @Route("/stripe/webhooks", name="stipe_webhooks")
     * @Route("/stripe/webhooks/identity", name="stipe_webhooks_identity")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function indexAction(Request $request)
    {
        $content = json_decode($request->getContent(), true);
        $event   = is_array($content) && isset($content['type']) ? $content['type'] : 'unknown';

        if ($event == 'unknown') {
            return new Response('ok no event passed', 400);
        }
        if (isset($content['data']['object']['verification_intent'])) {
            $stripeCustomerId = isset($content['data']['object']['verification_intent']) ? $content['data']['object']['verification_intent'] : null;
            $user = $stripeCustomerId ? $this->getUserByStripeVerificationCustomer($stripeCustomerId) : null;
        } else {
            $stripeCustomerId = isset($content['data']['object']['customer']) ? $content['data']['object']['customer'] : null;
            $user = $stripeCustomerId ? $this->getUserByStripeCustomer($stripeCustomerId) : null;
        }

        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = $this->get('event_dispatcher');

        $sfEvent = new StripeWebhookEvent($event, $user, $content, $request);
        try {
            $dispatcher->dispatch(StripeWebhookEvent::NAME, $sfEvent);
        } catch (WebhookProcessingException $e) {
            error_log(sprintf(
                'Stripe Webhook processing error. Event: "%s", Error: %s, Content: %s',
                $sfEvent->getApiEvent(),
                $e->getResponseMessage(),
                json_encode($content)
            ));

            return new Response($e->getResponseMessage(), $e->getResponseStatus());
        }

        if ($sfEvent->isFulfilled() || $sfEvent->getResponseMessage()) {
            return new Response($sfEvent->getResponseMessage(), $sfEvent->getResponseStatus());
        }

        return new Response('ok no handler', 200);
    }

    /**
     * @Route("/stripe/identity", name="stripe_identity_intents")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getStripeIdentityIntents(Request $request)
    {
        /** @var UserInfo $user */
        $user = $this->getUser();
        if ($user->isVerificationsExists() && $user->isRequestedVerificationRecently() && !$user->isVerified()) {
            return new JsonResponse(['success' => true, 'identity_url' => $user->getUserIdentity()->last()->getVerificationUrl()]);
        }

        if ($request->get('type') == 'withdraw') {
            $returnURL = $this->generateUrl(
                'financial_withdraw',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        } elseif ($request->get('type') == 'project') {
            $returnURL = $this->generateUrl(
                'project_view',
                ['uuid' => $request->get('project')],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        } else {
            $returnURL = $this->generateUrl(
                'financial_deposit',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        }

        $data = 'type=document'
            . '&return_url='
            . $returnURL
        ;

        $stripe = new StripeModel(
            ['Stripe-Version: 2020-03-02;identity_beta=v4'],
            'https://api.stripe.com/v1/identity/verification_sessions',
            $data,
            $this->container->getParameter('stripe_api_key')
        );

        $stripe->call();

        if ($stripe->getStatusCode() >= 400 && $stripe->getStatusCode() < 500) {
            error_log('STRIPE IDENTITY ERROR: ' . $stripe->getCallOutput()->error->message);
            return new JsonResponse(['success' => false, 'error' => $stripe->getCallOutput()->error->message]);
        }
        if (isset($stripe->getCallOutput()->id)) {
            $identity = new UserStripeIdentity();
            $identity
                ->setUser($user)
                ->setVerificationIntentId($stripe->getCallOutput()->id)
                ->setVerificationUrl($stripe->getCallOutput()->url)
            ;
            $this->getDoctrine()->getManager()->persist($identity);
            $this->getDoctrine()->getManager()->flush();

            return new JsonResponse(['success' => true, 'identity_url' => $identity->getVerificationUrl()]);
        }
        return new JsonResponse(['success' => false]);
    }

    /**
     * @param $customerId
     *
     * @return UserInfo
     */
    private function getUserByStripeCustomer($customerId)
    {
        return $this->getDoctrine()->getManager()->getRepository('VocalizrAppBundle:UserInfo')->findOneBy([
            'stripe_cust_id' => $customerId,
        ]);
    }

    /**
     * @param $customerId
     *
     * @return UserInfo
     */
    private function getUserByStripeVerificationCustomer($customerId)
    {
        $identity = $this->getDoctrine()->getManager()->getRepository('VocalizrAppBundle:UserStripeIdentity')->findOneBy([
            'verificationIntentId' => $customerId,
        ]);
        return $identity->getUser();
    }
}
