<?php

namespace App\EventListener\StripeWebhook;

use Doctrine\ORM\EntityManager;
use App\Entity\PaymentSessionData;
use App\Entity\StripeCharge;
use App\Event\StripeWebhookEvent;
use App\Exception\WebhookProcessingException;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class ChargeListener
 * @package App\EventListener\StripeWebhook
 */
class ChargeListener
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * @param StripeWebhookEvent $event
     *
     * @throws WebhookProcessingException
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
                    ->findOneBy(['user' => $user]);
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