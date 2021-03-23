<?php

namespace Vocalizr\AppBundle\EventListener\Payment;

use Doctrine\ORM\EntityManager;
use Vocalizr\AppBundle\Entity\Counter;
use Vocalizr\AppBundle\Entity\UserInfo;
use Vocalizr\AppBundle\Entity\UserWalletTransaction;
use Vocalizr\AppBundle\Event\PaymentSessionCompletedEvent;
use Vocalizr\AppBundle\Model\UserWalletTransactionModel;
use Vocalizr\AppBundle\Service\StripeConfigurationProvider;

/**
 * Class UserUpgradeListener
 * @package Vocalizr\AppBundle\EventListener\Payment
 */
class UserUpgradeListener
{
    private static $userUpgrades = ['extend_connections_limit'];

    /**
     * @var StripeConfigurationProvider
     */
    private $config;
    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var UserWalletTransactionModel
     */
    private $walletTransactionModel;

    public function __construct(StripeConfigurationProvider $config, EntityManager $em, UserWalletTransactionModel $walletTransactionModel)
    {
        $this->config                 = $config;
        $this->em                     = $em;
        $this->walletTransactionModel = $walletTransactionModel;
    }

    /**
     * @param PaymentSessionCompletedEvent $event
     */
    public function onPaymentSessionCompleted(PaymentSessionCompletedEvent $event)
    {
        $upgrades = false;
        foreach (self::$userUpgrades as $userUpgrade) {
            if (!$event->hasItem($userUpgrade)) {
                continue;
            }

            $upgrades = true;
            $this->applyUserUpgrade($event->getUser(), $userUpgrade, $event->getItem($userUpgrade));
        }

        if ($upgrades) {
            $event->addResponseMessage('ok user upgrades applied');
        } else {
            $event->addResponseMessage('ok no user upgrades');
        }
    }

    /**
     * @param UserInfo $user
     * @param string $productKey
     * @param array $item
     */
    private function applyUserUpgrade(UserInfo $user, $productKey, $item)
    {

        $priceKey = $this->config->searchProductPriceKey($productKey, $item['price']['id']);

        $description = '';

        if ($productKey === 'extend_connections_limit') {
            $counter = new Counter();
            $counter
                ->setType(Counter::TYPE_PERSISTENT_CONNECTIONS)
                ->setUserInfo($user)
                ->setLimit($priceKey)
            ;
            print_r($counter->getLimit());
            $this->em->persist($counter);

            $description = sprintf('%d more connection requests', $priceKey);
        }

        $this->walletTransactionModel->createAndPersistPair(
            $user,
            $item['amount_total'],
            [
                'stripe_description' => $item['description'],
                'price_nickname'     => isset($item['nickname']) ? $item['nickname']  : null,
            ],
            [UserWalletTransaction::USER_UPGRADE_DEPOSIT, UserWalletTransaction::USER_UPGRADE],
            [
                sprintf('Stripe payment for upgrade "%s" (%s)', $description, $item['id']),
                $description,
            ]
        );
        $this->em->flush();
    }
}