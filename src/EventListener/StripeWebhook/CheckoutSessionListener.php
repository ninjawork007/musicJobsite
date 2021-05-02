<?php

namespace App\EventListener\StripeWebhook;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use App\Entity\PaymentSessionData;
use App\Entity\Project;
use App\Entity\StripeCharge;
use App\Event\PaymentSessionCompletedEvent;
use App\Event\StripeWebhookEvent;
use App\Exception\WebhookProcessingException;

/**
 * Class CheckoutSessionListener
 * @package App\EventListener\StripeWebhook
 */
class CheckoutSessionListener
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * CheckoutSessionListener constructor.
     * @param ContainerInterface $container
     * @param EntityManager $em
     */
    public function __construct(ContainerInterface $container, EntityManager $em)
    {
        $this->em        = $em;
        $this->container = $container;
    }

    /**
     * @param StripeWebhookEvent $event
     * @throws OptimisticLockException
     * @throws WebhookProcessingException
     */
    public function onWebhook(StripeWebhookEvent $event)
    {
        if (!$event->matchApiEvent(StripeWebhookEvent::EVENT_CHECKOUT_SESSION_COMPLETED)) {
            return;
        }

        $event->setFulfilled(true);
        $event->stopPropagation();

        $event->setResponseMessage($this->processCheckout($event));
    }

    /**
     * @param StripeWebhookEvent $event
     * @return string
     * @throws WebhookProcessingException
     * @throws OptimisticLockException
     */
    private function processCheckout(StripeWebhookEvent $event)
    {
        $object = $event->getPayloadObject();

        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $this->get('event_dispatcher');

        $em = $this->em;
        $stripeManager = $this->get('vocalizr_app.stripe_manager');

        $charge         = new StripeCharge();
        $charge->amount = $object['amount_total'];
        $charge->data   = $event->getRequest()->getContent();
        if (isset($object['balance_transaction'])) {
            $balanceTransaction = $stripeManager->call('/balance_transactions/' . $object['balance_transaction'], [], [], 'GET');
            $chargeBalanceTransaction = new StripeCharge();
            $chargeBalanceTransaction->amount = $balanceTransaction['amount'];
            $chargeBalanceTransaction->data = json_encode($balanceTransaction);
            $chargeBalanceTransaction->balanceTransaction = $object['balance_transaction'];
            $em->persist($chargeBalanceTransaction);
        }

        /** @var Project $project */
        $project = $em->getRepository('App:Project')->findOneBy([
            'ssid' => $object['id']
        ]);

        /** @var PaymentSessionData|null $sessionData */
        $sessionData = $em->getRepository('App:PaymentSessionData')->findOneBy([
            'sessionId' => $object['id']
        ]);

        if (isset($object['subscription']) && !is_null($sessionData)) {
            $sessionData->setSubscriptionId($object['subscription']);
            $em->persist($sessionData);
        }

        $configuration = $this->get('vocalizr_app.stripe_configuration_provider');
        $stripe        = $this->get('vocalizr_app.stripe_manager');
        $userModel     = $this->get('vocalizr_app.model.user_info');
        $user          = $event->getUser();

        $event = new PaymentSessionCompletedEvent(
            $user,
            $project,
            $sessionData,
            $object,
            $configuration->indexLineItemsByProductKeys($stripe->getLineItemsArray($object['id'])),
            PaymentSessionCompletedEvent::METHOD_STRIPE
        );

        if (!$user) {
            error_log(sprintf(
                'Stripe Webhooks: user not found for ssid %s and customer id %s',
                $object['id'],
                $object['customer']
            ));

            throw new WebhookProcessingException('User not found');
        }
        $product = $event->getPaymentItems();

        // If mode is subscription - user choose to subscribe on monthly basis while publishing a project.
        if ($event->hasSubscription() & !isset($product['certified_user'])) {

            // Subscribe user immediately
            $userModel->addSubscription($user, $object['subscription'], true, $charge);
        }

        $eventDispatcher->dispatch(PaymentSessionCompletedEvent::NAME, $event);

        $em->persist($charge);
        $em->flush();

        return ($event->getResponseMessage() ? $event->getResponseMessage() : 'ok no message');
    }

    /**
     * @param string $serviceId
     * @return object
     */
    private function get($serviceId)
    {
        return $this->container->get($serviceId);
    }
}