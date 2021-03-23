<?php

namespace Vocalizr\AppBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Vocalizr\AppBundle\Entity\UserWalletTransaction;

/**
 * Class UserWalletListener
 *
 * @package Vocalizr\AppBundle\EventListener
 */
class UserWalletListener
{
    private $container;

    private $enabled = true;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        if (!$this->enabled) {
            return;
        }

        $entity = $args->getEntity();
        $em     = $args->getEntityManager();

        // Update wallet amount for user info
        if ($entity instanceof UserWalletTransaction) {
            $userInfo     = $entity->getUserInfo();
            $walletAmount = $userInfo->getWallet();

            // Add amount to wallet
            $walletAmount += $entity->getAmount();
            $userInfo->setWallet($walletAmount);

            if (is_null($entity->getActualBalance())) {
                $entity->setActualBalance($walletAmount);
            }
        }
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     *
     * @return UserWalletListener
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
        return $this;
    }
}