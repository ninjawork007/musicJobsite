<?php

namespace Vocalizr\AppBundle\Service;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Vocalizr\AppBundle\Entity\Project;
use Vocalizr\AppBundle\Entity\UserInfo;
use Vocalizr\AppBundle\Entity\UserWalletTransaction;
use Vocalizr\AppBundle\Repository\ProjectBidLogRepository;

/**
 * Class UserRestrictionService
 *
 * @package Vocalizr\AppBundle\Service
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
     * UserRestrictionService constructor.
     *
     * @param ContainerInterface $container
     * @param EntityManager      $em
     */
    public function __construct(ContainerInterface $container, EntityManager $em)
    {
        $this->em        = $em;
        $this->container = $container;
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

        $disputes = $this->em->getRepository('VocalizrAppBundle:ProjectDispute')->findBy([
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

        $transactionRepo = $this->em->getRepository('VocalizrAppBundle:UserWalletTransaction');

        // Find last deposit transaction
        $lastTransaction = $transactionRepo->findLastByTypeAndUser(UserWalletTransaction::TYPE_DEPOSIT, $user);

        if (!$lastTransaction) {
            // If there is no deposit transaction, find last withdraw transaction.
            $lastTransaction = $transactionRepo->findLastByTypeAndUser(UserWalletTransaction::TYPE_WITHDRAW, $user);
            if (!$lastTransaction) {
                $lastWithdraw = $this->em->getRepository('VocalizrAppBundle:UserWithdraw')->findLastNotCancelled($user);

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
        return $this->em->getRepository('VocalizrAppBundle:ProjectBidLog');
    }

    /**
     * @return UserInfo|null
     */
    private function getUser()
    {
        $token = $this->container->get('security.context')->getToken();

        if ($token) {
            $user = $token->getUser();
            if ($user instanceof UserInfo) {
                return $user;
            }
        }

        return null;
    }
}