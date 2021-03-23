<?php

namespace Vocalizr\AppBundle\Controller;

use Doctrine\ORM\EntityManager;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Vocalizr\AppBundle\Entity\PaymentSessionData;
use Vocalizr\AppBundle\Entity\Project;
use Vocalizr\AppBundle\Entity\ProjectBid;
use Vocalizr\AppBundle\Entity\UserInfo;
use Vocalizr\AppBundle\Service\ProjectPriceCalculator;
use Vocalizr\AppBundle\Service\StripeManager;

/**
 * Class StripeSessionController
 * @package Vocalizr\AppBundle\Controller
 */
class StripeSessionController extends Controller
{
    /**
     * @Route("/stripe/session", name="stipe_session_checkout")
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Vocalizr\AppBundle\Exception\NotEnoughMoneyException
     */
    public function getStripeCheckoutSessionForProject(Request $request)
    {
        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        $uuid = $request->get('uuid');
        $descriptions = [
            'transaction_fee' => 'Transaction Fee',
            'vocalizr_fee'    => 'Vocalizr Commission Fee',
        ];

        /** @var Project $project */
        $project = $em->getRepository('VocalizrAppBundle:Project')->findOneBy(['uuid' => $uuid]);

        /** @var UserInfo $user */
        $user = $this->getUser();

        if (!$project || $project->getUserInfo() !== $this->getUser()) {
            throw new AccessDeniedException();
        }

        $subscribe = $request->get('upgrade_to_pro', '0') == "1";

        $planStaticKey = $subscribe || $user->isSubscribed() ? 'PRO' : 'FREE';

        $isUserRequestedUpgrade = (!$user->isSubscribed() && $subscribe);

        /** @var ProjectPriceCalculator $priceCalculator */
        $priceCalculator = $this->get('vocalizr_app.project_price_calculator');
        $configuration   = $this->get('vocalizr_app.stripe_configuration_provider');

        /** @var StripeManager $stripeManager */
        $stripeManager = $this->get('vocalizr_app.stripe_manager');

        $stripeLineItems = [];

        $splitData = $priceCalculator->getPaymentSplitData($planStaticKey, $project);

        foreach ($splitData['stripe']['product_prices'] as $productKey => $price) {
            if (!$price) {
                continue;
            }

            $stripeLineItems[] = $configuration->createPriceLineItem(
                $productKey,
                [$planStaticKey, $price],
                $price,
                isset($descriptions[$productKey]) ? $descriptions[$productKey] : null
            );
        }

        $partialData = $splitData['partial'];
        $partialKey  = $partialData['product_key'];

        if ($partialData['stripe_amount']) {
            $stripeLineItems[] = $configuration->createPriceLineItem(
                $partialKey,
                [],
                $partialData['stripe_amount'],
                isset($descriptions[$partialKey]) ? $descriptions[$partialKey] : null
            );
        }

        if ($subscribe) {
            $stripeLineItems[] = [
                'price'    => $configuration->getSubscriptionPriceId('monthly'),
                'quantity' => 1,
            ];
        }

        if ($project->getProjectType() === Project::PROJECT_TYPE_CONTEST) {
            $returnRoute = 'contest_publish_confirm';
        } else {
            $returnRoute = 'project_publish_confirm';
        }

        $successUrl = $this->generateUrl(
            $returnRoute,
            ['uuid' => $uuid, 'success' => 1],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $cancelUrl = $this->generateUrl(
            $returnRoute,
            ['uuid' => $uuid, 'cancel' => 1],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        // If there is no need to pay (even subscription), trigger successful payment.
        if (!$stripeLineItems || ($project->getPaymentStatus() === Project::PAYMENT_STATUS_PAID && !$isUserRequestedUpgrade)) {
            $this->get('vocalizr_app.model.project')->processPublicationPayment($project, 0);
            $em->flush();
            return new JsonResponse(['success' => true, 'href' => $successUrl]);
        }

        try {
            $stripeResponseData = $stripeManager->getStripeSession(
                $user,
                $stripeLineItems,
                $successUrl,
                $cancelUrl,
                $subscribe ? 'subscription' : 'payment'
            );

            if (isset($stripeResponseData['id'])) {
                $project->setSsid($stripeResponseData['id']);
                $em->persist((new PaymentSessionData())
                    ->setSessionId($stripeResponseData['id'])
                    ->setUser($user)
                    ->setProject($project)
                );
                $em->flush();

                return new JsonResponse(['success' => true, 'ssid' => $stripeResponseData['id']]);
            } else {
                $message = 'no session id in response';
            }
        } catch (GuzzleException $e) {
            $message = $e->getMessage();
        }

        return new JsonResponse(['success' => false, 'error' => $message]);
    }

    /**
     * @Route("/stripe/session/user", name="stripe_session_user_upgrade")
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws Exception
     */
    public function createUserUpgradeStripeSession(Request $request)
    {
        $key    = $request->get('key');
        $option = $request->get('option');
        $em = $this->getDoctrine()->getManager();

        $config = $this->get('vocalizr_app.stripe_configuration_provider');
        $stripe = $this->get('vocalizr_app.stripe_manager');

        if (!$config->hasProduct($key)) {
            throw new NotFoundHttpException('Product not found');
        }

        $prices = $config->getProductPrices('extend_connections_limit');

        if (!isset($prices[$option])) {
            throw new NotFoundHttpException('Price not found');
        }

        $returnUrl = $request->headers->get('referer', $this->generateUrl('dashboard', [], Router::ABSOLUTE_URL));

        /** @var UserInfo $user */
        $user = $this->getUser();

        try {
            $session = $stripe->getSessionForSingleItem($user, $prices[$option], $returnUrl);
        } catch (GuzzleException $exception) {
            return new JsonResponse(['success' => false, 'error' => $exception->getMessage()]);
        }
        $em->persist((new PaymentSessionData())
            ->setSessionId($session['id'])
            ->setUser($user)
            ->setConnectionsExtend(true)
        );

        $em->flush();

        return new JsonResponse(['success' => true, 'ssid' => $session['id']]);
    }

    /**
     * @Route("/stripe/session/contest/upgrade/extend", name="stripe_session_contest_extend")
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * @throws GuzzleException
     */
    public function createExtendContestStripeSession(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $uuid = $request->get('uuid');
        $days = (int) $request->get('days');

        try {
            $daysPriceIdsMap = $this->get('vocalizr_app.stripe_configuration_provider')->getProductPrices('extend_contest');

            if (!array_key_exists($days, $daysPriceIdsMap)) {
                throw new BadRequestHttpException('Invalid "days" parameter. Expected one of those: '
                    . join(', ', array_keys($daysPriceIdsMap)));
            }

            $priceId = $daysPriceIdsMap[$days];

            /** @var Project $project */
            $project = $em->getRepository('VocalizrAppBundle:Project')->findOneBy(['uuid' => $uuid]);
            if (!$project) {
                throw new NotFoundHttpException('Project not found');
            }

            /** @var UserInfo $user */
            $user = $this->getUser();
            if (!$project->isOwner($user)) {
                throw new AccessDeniedHttpException('You must be creator of a gig.');
            }

            if ($project->getDaysExtended() && $project->getDaysExtended() + $days > 15) {
                throw new \InvalidArgumentException('Contest extension limit reached.');
            }

            $returnUrl = $this->generateUrl('contest_view', ['uuid' => $uuid], Router::ABSOLUTE_URL);

            $stripeResponseData = $this->get('vocalizr_app.stripe_manager')->getStripeSession($user, [[
                'price'    => $priceId,
                'quantity' => 1,
            ]], $returnUrl);

            if (isset($stripeResponseData['id'])) {
                $project->setSsid($stripeResponseData['id']);
                $em->persist((new PaymentSessionData())
                    ->setSessionId($stripeResponseData['id'])
                    ->setUser($user)
                    ->setProject($project)
                    ->setContestExtension(true)
                );

                $em->flush();

                return new JsonResponse(['success' => true, 'ssid' => $stripeResponseData['id']]);
            }

            return new JsonResponse(['success' => false, 'error' => $stripeResponseData['error']]);
        } catch (Exception $exception) {
            error_log('Stripe session creation error: ' . $exception->getMessage());
            return new JsonResponse(['success' => false, 'error' => $exception->getMessage()], 400);
        }
    }

    /**
     * @Route("/stripe/session/subscription", name="stripe_session_subscription")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getStripeSubscriptionSession(Request $request)
    {

        /** @var UserInfo $user */
        $user = $this->getUser();

        /** @var StripeManager $stripeManager */
        $stripeManager = $this->get('vocalizr_app.stripe_manager');

        $priceId = $this->get('vocalizr_app.stripe_configuration_provider')
            ->getSubscriptionPriceId($request->get('plan'));

        $stripeLineItems[] = [
            'price' => $priceId,
            'quantity' => 1,
        ];

        $source = $this->get('vocalizr_app.service.membership_source_helper')->getSource(false);

        if ($source && $source->isNeedReturn() && $source->getReturnUrl()) {
            $successUrl = $source->getReturnUrl();
        } else {
            $successUrl = $this->generateUrl(
                'paypal_pro_success',
                ['stripe' => 1],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        }

        $cancelUrl = $this->generateUrl(
            'user_upgrade',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        try {
            $stripeResponseData = $stripeManager->getStripeSession(
                $user,
                $stripeLineItems,
                $successUrl,
                $cancelUrl,
                'subscription'
            );

            if (isset($stripeResponseData['id'])) {
                return new JsonResponse(['success' => true, 'ssid' => $stripeResponseData['id']]);
            }

            $message = 'no session id was returned';

        } catch (GuzzleException $e) {
            $message = $e->getMessage();
        }

        return new JsonResponse(['success' => false, 'message' => $message]);
    }

    /**
     * @Route("/stripe/session/bid_highlights", name="stripe_session_bid_upgrade")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function createBidUpgradeStripeSession(Request $request)
    {
        $em      = $this->getDoctrine()->getManager();
        $user    = $this->getUser();
        $option  = $request->get('option');
        $config  = $this->get('vocalizr_app.stripe_configuration_provider');
        $product = 'paid_bid_highlights';
        /** @var ProjectBid $bid */
        $bid     = $em->getRepository('VocalizrAppBundle:ProjectBid')->getProjectBidByUuid($request->get('uuid'));
        $bid     = reset($bid);

        if (!$bid) {
            throw new BadRequestHttpException('Bid not exist');
        }

        if (!$bid || $bid->getUserInfo() !== $user) {
            throw new NotFoundHttpException('Bid not exist or this bid is not yours');
        }

        if ($option == 4) {
            $bid->setHighlightOption(ProjectBid::HIGHLIGHT_OPTION_NONE);
            $em->flush();

            return new JsonResponse(['success' => true]);
        }

        $project = $bid->getProject();

        if (!$config->hasProductPrice($product, $option)) {
            throw new BadRequestHttpException("Invalid price option $option.");
        }

        $price  = $config->getProductPriceId($product, $option);
        $stripe = $this->get('vocalizr_app.stripe_manager');

        $stripeResponseData = $stripe->getSessionForSingleItem($user, $price, $this->generateUrl('project_view', [
            'uuid' => $project->getUuid(),
        ], UrlGeneratorInterface::ABSOLUTE_URL));

        $sessionId = $stripeResponseData['id'];

        $em->persist((new PaymentSessionData())
            ->setSessionId($sessionId)
            ->setUser($user)
            ->setBid($bid)
            ->addProduct($product, $option, $price)
        );

        $em->flush();

        return new JsonResponse([
            'success' => true,
            'ssid'    => $sessionId,
        ]);
    }
}