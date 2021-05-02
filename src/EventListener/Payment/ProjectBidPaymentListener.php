<?php

namespace App\EventListener\Payment;

use Doctrine\ORM\EntityManager;
use App\Event\PaymentSessionCompletedEvent;
use App\Model\UserWalletTransactionModel;
use App\Service\StripeConfigurationProvider;

/**
 * Class ProjectBidPaymentListener
 * @package App\EventListener\Payment
 */
class ProjectBidPaymentListener
{
    /**
     * @var StripeConfigurationProvider
     */
    private $config;
    /**
     * @var UserWalletTransactionModel
     */
    private $transactionModel;

    public function __construct(UserWalletTransactionModel $transactionModel, StripeConfigurationProvider $config)
    {
        $this->config = $config;
        $this->transactionModel = $transactionModel;
    }

    /**
     * @param PaymentSessionCompletedEvent $event
     */
    public function onPaymentSessionCompleted(PaymentSessionCompletedEvent $event)
    {
        $sessionData = $event->getPaymentSessionData();
        // Do nothing on payments without bid.
        if (!$sessionData || !$sessionData->getBid()) {
            $event->addResponseMessage('ok no bid payment');
            return;
        }

        $event->addResponseMessage('ok bid payment');

        $bid = $event->getPaymentSessionData()->getBid();

        $productData = $sessionData->getProduct(['product_key' => 'paid_bid_highlights']);

        $bid
            ->setHighlightOption((int)$productData['price_key'])
            ->setHighlightedAt(new \DateTime())
        ;

        $sessionData->setProcessed(true);
    }
}