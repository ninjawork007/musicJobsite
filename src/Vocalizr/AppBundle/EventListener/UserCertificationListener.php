<?php


namespace Vocalizr\AppBundle\EventListener;


use Vocalizr\AppBundle\Entity\PaymentSessionData;
use Vocalizr\AppBundle\Entity\UserCertification;
use Vocalizr\AppBundle\Event\PaymentSessionCompletedEvent;

/**
 * Class UserCertificationListener
 * @package Vocalizr\AppBundle\EventListener
 */
class UserCertificationListener
{
    /**
     * @param PaymentSessionCompletedEvent $event
     */
    public function onPaymentCertificationCompleted(PaymentSessionCompletedEvent $event)
    {
        /** @var PaymentSessionData $paymentSessionData */
        $paymentSessionData = $event->getPaymentSessionData();

        if (isset($paymentSessionData) && !is_null($paymentSessionData->getUserCertification())) {
            /** @var UserCertification $userCertification */
            $userCertification = $paymentSessionData->getUserCertification();
            $userCertification->setPaid(true);
            $paymentSessionData->setProcessed(true);
            $event->addResponseMessage('ok user payment certified applied');
        } else {
            $event->addResponseMessage('ok no user payment certified');
        }
    }
}
