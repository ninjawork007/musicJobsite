<?php


namespace App\EventListener;


use App\Entity\PaymentSessionData;
use App\Entity\UserCertification;
use App\Event\PaymentSessionCompletedEvent;

/**
 * Class UserCertificationListener
 * @package App\EventListener
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
