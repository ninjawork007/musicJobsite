<?php

namespace App\EventListener\StripeWebhook;

use Doctrine\ORM\EntityManager;
use App\Entity\PaymentSessionData;
use App\Entity\StripeCharge;
use App\Event\StripeWebhookEvent;
use App\Exception\WebhookProcessingException;

/**
 * Class ChargeListener
 * @package App\EventListener\StripeWebhook
 */
class ChargeListener
{
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param StripeWebhookEvent $event
     */
    public function onWebhook(StripeWebhookEvent $event)
    {
        if ($event->matchApiEvent(StripeWebhookEvent::EVENT_CHARGE_SUCCEED)) {
            // It's the only charge handler.
            $event->stopPropagation();
            $event->setFulfilled(true);

            $object = $event->getPayloadObject();

            $charge         = new StripeCharge();
            $charge->amount = $object['amount'];
            $charge->data   = $event->getRequest()->getContent();

            $user = $event->getUser();

            if (!$user) {
                throw new WebhookProcessingException('User not found, but required for charge processing.');
            }

            /** @var PaymentSessionData $paymentSessionData */
            $paymentSessionData = $this->em->getRepository('App:PaymentSessionData')
                ->findOneByUserForCertification($user);

            // Never override stripe charge.
            if ($paymentSessionData && !$paymentSessionData->getStripeCharge()) {
                $paymentSessionData->setStripeCharge($charge);
            } else {
                $paymentSessionData = $this->em->getRepository('App:PaymentSessionData')
                    ->findOneBy(['user' => $user], ['id' => 'DESC']);
                if ($paymentSessionData && !$paymentSessionData->getStripeCharge()) {
                    $paymentSessionData->setStripeCharge($charge);
                }
            }

            $this->em->persist($charge);
            $this->em->flush();

            $event->addResponseMessage('ok charged');
        }
    }
}