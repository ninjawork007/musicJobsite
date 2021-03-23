<?php

namespace Vocalizr\AppBundle\EventListener\StripeWebhook;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\Templating\EngineInterface;
use Vocalizr\AppBundle\Entity\UserSubscription;
use Vocalizr\AppBundle\Event\StripeWebhookEvent;
use Vocalizr\AppBundle\Exception\WebhookProcessingException;
use Vocalizr\AppBundle\Model\UserSubscriptionModel;
use Vocalizr\AppBundle\Service\MandrillService;
use Vocalizr\AppBundle\Service\PayPalService;

/**
 * Class SubscriptionListener
 * @package Vocalizr\AppBundle\EventListener\StripeWebhook
 */
class SubscriptionListener
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var UserSubscriptionModel
     */
    private $subscriptionModel;

    /**
     * @var MandrillService
     */
    private $mandrillService;
    /**
     * @var EngineInterface
     */
    private $twig;

    /**
     * SubscriptionListener constructor.
     * @param EntityManager $em
     * @param EngineInterface $twig
     * @param MandrillService $mandrillService
     * @param UserSubscriptionModel $subscriptionModel
     */
    public function __construct(
        EntityManager $em,
        EngineInterface $twig,
        MandrillService $mandrillService,
        UserSubscriptionModel $subscriptionModel
    ) {
        $this->em = $em;
        $this->twig = $twig;
        $this->mandrillService = $mandrillService;
        $this->subscriptionModel = $subscriptionModel;
    }

    /**
     * @param StripeWebhookEvent $event
     */
    public function onWebhook(StripeWebhookEvent $event)
    {
        if (!$event->matchApiEvent([
            StripeWebhookEvent::EVENT_SUBSCRIPTION_UPDATED,
            StripeWebhookEvent::EVENT_SUBSCRIPTION_DELETED,
        ])) {
            return;
        }

        if ($event->matchApiEvent(StripeWebhookEvent::EVENT_SUBSCRIPTION_UPDATED)) {
            $message = $this->processUpdate($event);
        } else {
            $message = $this->processDelete($event);
        }

        $event->stopPropagation();
        $event->setResponseMessage($message);
    }

    /**
     * @param StripeWebhookEvent $event
     * @return string
     * @throws WebhookProcessingException
     * @throws OptimisticLockException
     */
    private function processUpdate(StripeWebhookEvent $event)
    {
        $object = $event->getPayloadObject();
        $user   = $event->getUser();

        $subscription = $this->resolveSubscriptionOrFail($object['id']);

        $oldNextPaymentDate = $subscription->getNextPaymentDate() ? $subscription->getNextPaymentDate() : new \DateTime();

        $this->subscriptionModel->updateStripeSubscription($subscription, $object);

        $amount = ($object['plan']['amount']) / 100;

        $sendMessage = $object['status'] === 'active' &&
            $oldNextPaymentDate != $subscription->getNextPaymentDate();
        if ($sendMessage) {
            $this->mandrillService->sendSubscriptionRenewedMessage($user, $amount, $object['customer'], $subscription->getNextPaymentDate());

            if (!in_array($amount, PayPalService::getSubscriptionPaymentAmounts())) {
                error_log('Subscription amount ' . $amount
                    . ' is not equal to any known amount. Can not determine which message Vocalizr should send (fallback to monthly).');
            }
        }

        $action = $user->getSubscriptionPlan() ? 'renewed' : 'cancelled';

        return ('ok subscription ' . $action . '. Message ' . ($sendMessage ? '' : 'not ') . 'sent');
    }

    private function processDelete(StripeWebhookEvent $event)
    {
        $user = $event->getUser();
        $object = $event->getPayloadObject();

        if (!$user) {
            throw new WebhookProcessingException('User not found by stripe customer id');
        }

        $stripeSubscriptionId = isset($object['id']) ? $object['id'] : null;

        if (!$stripeSubscriptionId) {
            throw new WebhookProcessingException('No subscription id specified in request');
        }

        // get active plan for the User
        $userSubscription = $this->em->getRepository('VocalizrAppBundle:UserSubscription')->findOneBy([
            'user_info'        => $user,
            'stripe_subscr_id' => $stripeSubscriptionId,
        ]);

        if (!$userSubscription) {
            throw new WebhookProcessingException('No user subscription found by id '
                . $stripeSubscriptionId . ' for user ' . $user->getUsername());
        }

        $userSubscription
            ->setIsActive(false)
            ->setDateEnded(new \DateTime())
        ;

        $this->subscriptionModel->setUserPlanBasedOnSubscriptions($user);

        $this->em->flush();

        $body = $this->twig->render('VocalizrAppBundle:Mail:membershipCancelled.html.twig', [
            'user' => $user,
        ]);

        $this->mandrillService->sendMessage($user->getEmail(), 'Your PRO Membership has been cancelled', 'default', [
            'body' => $body,
        ]);

        return 'ok subscription deactivated';
    }

    /**
     * @param $id
     * @return UserSubscription
     * @throws WebhookProcessingException
     */
    private function resolveSubscriptionOrFail($id)
    {
        $subscriptionRepo = $this->em->getRepository(UserSubscription::class);

        $attempts = 0;

        while($attempts < 3) {
            /** @var UserSubscription $subscription */
            $subscription = $subscriptionRepo->findOneBy(['stripe_subscr_id' => $id]);

            if ($subscription) {
                return $subscription;
            }

            $attempts++;
            sleep(2);
        }

        throw new WebhookProcessingException('Could not find subscription with id ' . $id);
    }
}