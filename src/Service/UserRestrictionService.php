<?php

namespace App\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use App\Entity\Project;
use App\Entity\UserInfo;
use App\Entity\UserWalletTransaction;
use App\Repository\ProjectBidLogRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class UserRestrictionService
 *
 * @package App\Service
 */
class UserRestrictionService
{
    const MAX_BIDS_PER_MONTH = 5;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var TokenStorageInterface
     */
    private $tokenInterface;

    /**
     * UserRestrictionService constructor.
     *
     * @param ContainerInterface $container
     * @param EntityManagerInterface $em
     * @param TokenStorageInterface $tokenInterface
     */
    public function __construct(ContainerInterface $container, EntityManagerInterface $em, TokenStorageInterface $tokenInterface)
    {
        $this->em        = $em;
        $this->container = $container;
        $this->tokenInterface  = $tokenInterface;
    }

    /**
     * @return bool
     */
    public function canBid()
    {
        $user = $this->getUser();
        if (!$user) {
            return false;
        }

        if ($user->isSubscribed()) {
            return true;
        }

        return false;

//        $bidsCount = $this->bidLogRepo()->countUserBidsThisMonth($user);
//
//        return ($bidsCount < self::MAX_BIDS_PER_MONTH);
    }

    /**
     * @return bool
     */
    public function canDiscussBid()
    {
        return $this->isSubscribed();
    }

    /**
     * @return bool
     */
    public function canHireNow()
    {
        return $this->isSubscribed();
    }

    /**
     * @return bool
     */
    public function canWithdrawInstantly()
    {
        return $this->isSubscribed();
    }

    /**
     * @param string $email
     *
     * @return bool
     */
    public function canDepositOnEmail($email)
    {
        $withdrawEmail = $this->getWithdrawEmail($this->getUser());

        if (!$withdrawEmail) {
            // Only lock user to the email if he already made a deposit or a withdrawal.
            return true;
        }

        return (strcasecmp($withdrawEmail, $email) === 0);
    }

    /**
     * @param string $email
     *
     * @return bool
     */
    public function canWithdrawOnEmail($email)
    {
        $withdrawEmail = $this->getWithdrawEmail($this->getUser());

        if (!$withdrawEmail) {
            // Only lock user to the email if he already made a deposit or a withdrawal.
            return true;
        }

        return (strcasecmp($withdrawEmail, $email) === 0);
    }

    /**
     * @param Project $project
     *
     * @return bool
     */
    public function canReviewUserOnProject(Project $project)
    {
        if (!$project->getIsComplete()) {
            return false;
        }

        $disputes = $this->em->getRepository('App:ProjectDispute')->findBy([
            'project'  => $project->getId(),
            'accepted' => true,
        ]);

        if (!empty($disputes)) {
            return false;
        }

        return true;
    }

    /**
     * @param UserInfo $user
     * @return string|null
     */
    public function getWithdrawEmail(UserInfo $user)
    {
        if ($user->getWithdrawEmail()) {
            return $user->getWithdrawEmail();
        }

        $transactionRepo = $this->em->getRepository('App:UserWalletTransaction');

        // Find last deposit transaction
        $lastTransaction = $transactionRepo->findLastByTypeAndUser(UserWalletTransaction::TYPE_DEPOSIT, $user);

        if (!$lastTransaction) {
            // If there is no deposit transaction, find last withdraw transaction.
            $lastTransaction = $transactionRepo->findLastByTypeAndUser(UserWalletTransaction::TYPE_WITHDRAW, $user);
            if (!$lastTransaction) {
                $lastWithdraw = $this->em->getRepository('App:UserWithdraw')->findLastNotCancelled($user);

                if ($lastWithdraw) {
                    return $lastWithdraw->getPaypalEmail();
                }

                // Only lock user to the email if he already made a deposit or a withdrawal.
                return null;
            }
        }

        return $lastTransaction->getEmail();
    }

    /**
     * @return bool
     */
    private function isSubscribed()
    {
        $user = $this->getUser();

        if (!$user || !$user->isSubscribed()) {
            return false;
        }

        return true;
    }

    /**
     * @return ProjectBidLogRepository
     */
    private function bidLogRepo()
    {
        return $this->em->getRepository('App:ProjectBidLog');
    }

    /**
     * @return UserInfo|null
     */
    private function getUser()
    {
        if ($this->tokenInterface->getToken()) {
            $user = $this->tokenInterface->getToken()->getUser();
            if ($user instanceof UserInfo) {
                return $user;
            }
        }

        return null;
    }
}