<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use App\Entity\Counter;

use App\Entity\UserConnectInvite;
use App\Entity\UserInfo;
use App\Exception\UpgradeException;

class UserConnectInviteRepository extends EntityRepository
{
    /**
     * Get requests made today
     *
     * @return array
     */
    public function findRequestsMadeToday($user)
    {
        $dt = new \DateTime();

        $qb = $this->createQueryBuilder('uc')
                ->where("uc.from = :user and DATE_FORMAT(uc.created_at, '%Y-%m-%d') = :date");
        $params = [
            'user' => $user,
            'date' => $dt->format('Y-m-d'),
        ];
        $qb->setParameters($params);

        return $qb->getQuery()->execute();
    }

    /**
     * Get requests made this month
     *
     * @return array
     */
    public function findRequestsMadeThisMonth($user)
    {
        $dt = new \DateTime();

        $qb = $this->createQueryBuilder('uc')
                ->where("uc.from = :user and DATE_FORMAT(uc.created_at, '%Y-%m') = :date");
        $params = [
            'user' => $user,
            'date' => $dt->format('Y-m'),
        ];
        $qb->setParameters($params);

        return $qb->getQuery()->execute();
    }

    /**
     * @deprecated use UserConnectModel::validateConnectionAttempt
     * @param UserInfo $fromUser
     * @param UserInfo $toUser
     * @return bool
     * @throws UpgradeException
     */
    public function isConnectAllowed($fromUser, $toUser)
    {
        $em = $this->_em;

        if ($fromUser->getId() == $toUser->getId()) {
            throw new \Exception('Why would you want to connect with yourself?');
        }

        if (!$fromUser->getSubscriptionPlan()) {
            throw new UpgradeException('To connect with other members please upgrade to PRO Membership');
        }

        // See if the user has blocked them
        $blockUser = $em->getRepository('App:UserBlock')->findOneBy([
            'user_info'  => $toUser,
            'block_user' => $fromUser,
        ]);
        if ($blockUser) {
            throw new \Exception('Member is not accepting connect invites at the moment');
        }

        // Check to see if there is already a connection
        $qb = $this->createQueryBuilder('uc')
                ->where('(uc.from = :from AND uc.to = :to) OR (uc.to = :from AND uc.from = :to)');
        $qb->setParameters([
            'from' => $fromUser,
            'to'   => $toUser,
        ]);
        $result = $qb->getQuery()->execute();

        if ($result) {
            $userConnectInvite = $result[0];
            if ($userConnectInvite->getConnectedAt()) {
                throw new \Exception('You are already connected');
            }
            throw new \Exception('There is already an invite pending');
        }

        // Check current users subscription and if they have met their quota
        $subscriptionPlan = $em->getRepository('App:SubscriptionPlan')->getActiveSubscription($fromUser->getId());

        // Check how many connections made this month
        $connectCount = $em->getRepository('App:Counter')->getCount($fromUser, Counter::TYPE_CONNECT);

        if ($connectCount >= $subscriptionPlan['connect_month_limit']) {
            if ($subscriptionPlan['static_key'] == \App\Entity\SubscriptionPlan::PLAN_FREE) {
                throw new UpgradeException('Monthly connection limit of ' . $subscriptionPlan['connect_month_limit'] . ' reached.', 404);
            }
            throw new \Exception('Monthly connection limit of ' . $subscriptionPlan['connect_daily_limit'] . ' has been reached.');
        }

        // Get user preference for user
        $userPref = $toUser->getUserPref();
        // If it doesn't exist, just get default values
        if (!$userPref) {
            $userPref = new \App\Entity\UserPref();
        }

        // See if user is accepting requests
        if (!$userPref->getConnectAccept()) {
            throw new \Exception('This member does not accept connection requests.');
        }

        // Check if user is only accepting requests from certified
        if ($userPref->getConnectRestrictCertified() && !$fromUser->isCertified()) {
            throw new \Exception('This member does not accept connection requests.');
        }

        // Check if user is only accepting requests from pro's
        if ($userPref->getConnectRestrictSubscribed() && !$fromUser->getSubscriptionPlan()) {
            throw new UpgradeException('This member only accepts connection requests from PRO Members.');
        }

        return true;
    }

    /**
     * Get user connections for user
     * If user logged in, see if they are connected aswell
     *
     * @param $userInfo
     * @param int $offset
     * @param int $limit
     *
     * @return array
     */
    public function getConnections($userInfo, $offset = 0, $limit = 20)
    {
        $qb = $this->createQueryBuilder('uc')
            ->select('uc')
            ->leftJoin('uc.from', 'fu')
            ->leftJoin('uc.to', 'tu')
            ->where('uc.from = :user or uc.to = :user')
            ->andWhere('uc.connected_at IS NOT NULL')
            ->andWhere('fu.is_active = 1')
            ->andWhere('tu.is_active = 1')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
        ;

        $params = [
            'user' => $userInfo,
        ];
        $qb->setParameters($params);

        return $qb->getQuery()->execute();
    }

    /**
     * @param $user
     * @param $userIds
     *
     * @return UserConnectInvite[]
     */
    public function getUserConnectionsByIds($user, $userIds)
    {
        return $this->getUserConnectionsByIdsQb($user, $userIds)->getQuery()->execute();
    }

    /**
     * @param UserInfo $me
     * @param UserInfo $other
     * @return UserConnectInvite
     */
    public function getConnectionInviteBetweenUsers(UserInfo $me, UserInfo $other)
    {
        $qb = $this->getUserConnectionsByIdsQb($me, $other->getId());
        $qb->setMaxResults(1);

        try {
            return $qb->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        } catch (NonUniqueResultException $e) {
            throw new \LogicException('Expected 1 result after limit.', 0, $e);
        }
    }

    /**
     * @param $user
     * @param $userIds
     *
     * @return QueryBuilder
     */
    private function getUserConnectionsByIdsQb($user, $userIds)
    {
        $qb = $this->createQueryBuilder('uc')
            ->select('uc');

        $qb->where('(uc.from = :user and ' . $qb->expr()->in('uc.to', $userIds) . ')');
        $qb->orWhere('(uc.to = :user and ' . $qb->expr()->in('uc.from', $userIds) . ')');

        $params = [
            'user' => $user,
        ];
        $qb->setParameters($params);

        return $qb;
    }
}
