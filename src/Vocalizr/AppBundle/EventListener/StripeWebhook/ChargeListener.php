<?php

namespace Vocalizr\AppBundle\EventListener\StripeWebhook;

use Doctrine\ORM\EntityManager;
use Vocalizr\AppBundle\Entity\PaymentSessionData;
use Vocalizr\AppBundle\Entity\StripeCharge;
use Vocalizr\AppBundle\Event\StripeWebhookEvent;
use Vocalizr\AppBundle\Exception\WebhookProcessingException;

/**
 * Class ChargeListener
 * @package Vocalizr\AppBundle\EventListener\StripeWebhook
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
            $paymentSessionData = $this->em->getRepository('VocalizrAppBundle:PaymentSessionData')
                ->findOneByUserForCertification($user);

            // Never override stripe charge.
            if ($paymentSessionData && !$paymentSessionData->getStripeCharge()) {
                $paymentSessionData->setStripeCharge($charge);
            } else {
                $paymentSessionData = $this->em->getRepository('VocalizrAppBundle:PaymentSessionData')
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