<?php

namespace Vocalizr\AppBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Vocalizr\AppBundle\Entity\PayPalTransaction;
use Vocalizr\AppBundle\Entity\UserInfo;
use Vocalizr\AppBundle\Entity\UserReview;
use Vocalizr\AppBundle\Entity\UserWalletTransaction;

class UserInfoRepository extends EntityRepository
{
    /**
     * Tries to find a user using the parsed search term
     *
     * @param string $searchTerm
     * @param array $searchFields
     * @param bool $strictComparison
     * @param array $ppSearchFields
     * @return UserInfo[]
     */
    public function findUser(
        $searchTerm,
        $searchFields = ['email', 'username', 'display_name'],
        $strictComparison = false,
        $ppSearchFields = []
    ) {
        if (!$searchFields && !$ppSearchFields) {
            return [];
        }

        $qb = $this->createQueryBuilder('u');

        $fieldExpressions = [];
        foreach ($searchFields as $searchField) {
            $fieldExpressions[] = $strictComparison ?
                $qb->expr()->eq('u.' . $searchField, ':searchTerm') :
                $qb->expr()->like('u.' . $searchField, ':searchTerm')
            ;
        }

        // Do not search multiple ids and email simultaneously as it produces huge query which cannot be executed.
        $idSearchesCount = 0;

        if (in_array('subscription_id', $ppSearchFields)) {
            $qb->leftJoin('u.user_subscriptions', 'us');
            $fieldExpressions[] = $strictComparison ?
                $qb->expr()->eq('us.paypal_subscr_id', ':searchTerm') :
                $qb->expr()->like('us.paypal_subscr_id', ':searchTerm');
            $idSearchesCount++;
        }

        if (in_array('transaction_id', $ppSearchFields)) {
            $qb->join(PayPalTransaction::class, 'ppt', 'WITH', 'u.id = ppt.user_info');
            $fieldExpressions[] = $strictComparison ?
                $qb->expr()->eq('ppt.txn_id', ':searchTerm') :
                $qb->expr()->like('ppt.txn_id', ':searchTerm');
            $idSearchesCount++;
        }

        if (in_array('email', $ppSearchFields) && $idSearchesCount < 2) {
            // Disable
            $qb->leftJoin('u.user_wallet_transactions', 'uw');
            $fieldExpressions[] = $strictComparison ?
                $qb->expr()->andX(
                    $qb->expr()->eq('uw.email', ':searchTerm'),
                    $qb->expr()->orX(
                        $qb->expr()->eq('uw.type', ':deposit'),
                        $qb->expr()->eq('uw.type', ':withdraw')
                    )
                ) :
                $qb->expr()->andX(
                    $qb->expr()->like('uw.email', ':searchTerm'),
                    $qb->expr()->orX(
                        $qb->expr()->eq('uw.type', ':deposit'),
                        $qb->expr()->eq('uw.type', ':withdraw')
                    )
                )
            ;
            $fieldExpressions[] = $strictComparison ?
                $qb->expr()->eq('u.withdrawEmail', ':searchTerm') :
                $qb->expr()->like('u.withdrawEmail', ':searchTerm')
            ;
            $qb
                ->setParameter('deposit', UserWalletTransaction::TYPE_DEPOSIT)
                ->setParameter('withdraw', UserWalletTransaction::TYPE_WITHDRAW)
            ;
        }
        if (in_array('review', $ppSearchFields)) {
            $qb->leftJoin('u.user_reviews', 'ur');
            $qb->andWhere('ur.content IS NOT NULL');
        }


        $qb->select('u');


        if (!$strictComparison) {
            $searchTerm = '%' . $searchTerm . '%';
        }

        if ($fieldExpressions) {
            $expressionBuilder = $qb->expr();
            $qb
                ->where(call_user_func_array([$expressionBuilder, 'orX'], $fieldExpressions))
                ->setParameter('searchTerm', $searchTerm)
            ;

        }

        $query = $qb->getQuery();

        return $query->execute();
    }

    /**
     * Case insenitive find one by email
     *
     * @param string $email
     *
     * @return string|null
     */
    public function findFirstByEmail($email)
    {
        $q = $this->createQueryBuilder('e')
            ->where('UPPER(e.email) = :email')
            ->setParameter('email', strtoupper($email))

            ->setMaxResults(1)
        ;

        $query = $q->getQuery();

        try {
            return $query->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            throw new \LogicException('Unexpected state');
        }
    }

    /**
     * @param string $str
     *
     * @return object|UserInfo|null
     */
    public function findByUniqueStr($str)
    {
        return $this->findOneBy(['unique_str' => $str]);
    }

    /**
     * Check if user is a favorite or not
     *
     * @param int $userInfoId
     */
    public function isUserFavorite($userInfoId, $favoriteUserInfoId)
    {
        $q = $this->createQueryBuilder('ui')
                ->select('count(ui.id)')
                ->innerJoin('ui.favorites', 'f')
                ->where('ui.id = :userInfoId');
        $q->andWhere($q->expr()->in('f.id', [$favoriteUserInfoId]));

        $params = [
            ':userInfoId' => $userInfoId,
        ];
        $q->setParameters($params);

        $query = $q->getQuery();

        return $query->getSingleScalarResult();
    }

    /**
     * Get user favorite vocalists
     *
     * @param int  $userInfoId
     * @param bool $count
     *
     * @return array
     */
    public function getUserVocalistFavorites($userInfoId, $count = false)
    {
        $q = $this->createQueryBuilder('ui')
                ->select('ui, f')
                ->innerJoin('ui.favorites', 'f', 'WITH', 'f.is_vocalist = :isVocalist')
                ->where('ui.id = :userInfoId');
        $params = [
            ':userInfoId' => $userInfoId,
            ':isVocalist' => true,
        ];
        $q->setParameters($params);

        if ($count) {
            $q->select('count(f.id)');
            $query = $q->getQuery();
            return $query->getSingleScalarResult();
        }

        $query = $q->getQuery();
        return $query->getSingleResult();
    }

    /**
     * Get user favorites
     * join user preferences
     *
     * @param int    $userInfoId
     * @param string $type       vocalist|producer
     * @param bool count
     *
     * @return array
     */
    public function getUserFavoritesForInviting($userInfoId, $project, $count = false)
    {
        $q = $this->createQueryBuilder('ui')
                ->select('ui, f, up')
                ->innerJoin('ui.favorites', 'f')
                ->leftJoin('f.user_pref', 'up')
                //->leftJoin('f.user_block', 'ub', 'WITH', 'ub.user_block = :userInfoId')
                ->where('ui.id = :userInfoId');
        $params = [
            ':userInfoId' => $userInfoId,
        ];

        if ($project->getLookingFor() == 'vocalist') {
            $q->andWhere('f.is_vocalist = 1');
        }
        if ($project->getLookingFor() == 'producer') {
            $q->andWhere('f.is_producer = 1');
        }
        if ($gender = $project->getGender()) {
            if ($gender == 'male') {
                $q->andWhere("f.gender = 'm'");
            }
            if ($gender == 'female') {
                $q->andWhere("f.gender = 'f'");
            }
        }
        if ($project->getProRequired()) {
            $q->andWhere('f.is_certified = 1');
        }

        $q->setParameters($params);

        if ($count) {
            $q->select('count(f.id)');
            $query = $q->getQuery();
            return $query->getSingleScalarResult();
        }

        $query = $q->getQuery();
        return $query->execute();
    }

    /**
     * Get user info by username
     *
     * @param string $username
     *
     * @return UserInfo|null
     */
    public function getUserByUsername($username)
    {
        $q = $this->createQueryBuilder('ui')
                ->select('ui, ua, g')
                ->leftJoin('ui.user_audio', 'ua')
                ->leftJoin('ui.genres', 'g')
                ->where('ui.username = :username AND ui.is_active = 1')
                ->orderBy('ua.default_audio', 'DESC');
        $q->setParameter(':username', $username);
        $query = $q->getQuery();

        try {
            return $query->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    /**
     * @return array
     */
    public function getUserWithoutLanguages()
    {
        return $this->createQueryBuilder('ui')
            ->leftJoin('ui.userLanguages', 'ul')
            ->where('ul is NULL')
            ->getQuery()->getResult();
    }

    /**
     * @return UserInfo[]
     */
    public function getUsersWithInvalidSubscription()
    {
        $qb = $this->createQueryBuilder('ui');

        $qb
            ->select('ui')
            ->join('ui.user_subscriptions', 'us')
            ->where('ui.subscription_plan is not null')
            ->groupBy('ui.id')
            ->having('sum(us.is_active) < 1')
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @return int|mixed
     */
    public function getUserCount()
    {
        $qb = $this->createQueryBuilder('ui');
        $qb
            ->select('count(ui.id)')
        ;
        try {
            return $qb->getQuery()->getSingleScalarResult();
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * @param int $page
     * @param int $perPage
     *
     * @param QueryBuilder|null $qb
     * @return UserInfo[]
     */
    public function findByPage($page = 1, $perPage = 1000, $qb = null)
    {
        $first = ($page - 1) * $perPage;

        if (!$qb) {
            $qb = $this->createQueryBuilder('ui');
        }
        $qb
            ->setFirstResult($first)
            ->setMaxResults($perPage)
        ;

        return $qb->getQuery()->getResult();
    }

    public function findByConnectInvite()
    {
        $checkDateStart   = new \DateTime('-2 day');
        $checkDateEnd   = new \DateTime('-1 day');
        $qb = $this->createQueryBuilder('ui')
            ->leftJoin('ui.user_subscriptions', 'us')
            ->leftJoin('ui.user_connect_invites', 'uc')
            ->andWhere('uc.created_at between :dateStart and :dateEnd')
            ->andWhere('us.is_active = 0')
            ->andWhere('ui.is_active = 1')
            ->andWhere('ui.last_login between :dateStart and :dateEnd')
            ->setParameter(':dateStart', $checkDateStart)
            ->setParameter(':dateEnd', $checkDateEnd)
        ;
        return $qb->getQuery()->getResult();
    }

    public function findByProjectInvite()
    {
        $checkDateStart   = new \DateTime('-2 day');
        $checkDateEnd   = new \DateTime('-1 day');
        $qb = $this->createQueryBuilder('ui')
            ->leftJoin('ui.user_subscriptions', 'us')
            ->leftJoin('ui.project_invites', 'pi')
            ->andWhere('pi.created_at between :dateStart and :dateEnd')
            ->andWhere('us.is_active = 0')
            ->andWhere('ui.is_active = 1')
            ->andWhere('ui.last_login between :dateStart and :dateEnd')
            ->setParameter(':dateStart', $checkDateStart)
            ->setParameter(':dateEnd', $checkDateEnd)
        ;
        return $qb->getQuery()->getResult();
    }

    public function findByHireNowInvite()
    {
        $checkDateStart   = new \DateTime('-2 day');
        $checkDateEnd   = new \DateTime('-1 day');
        $qb = $this->createQueryBuilder('ui')
            ->leftJoin('ui.user_subscriptions', 'us')
            ->leftJoin('ui.project_invites', 'pi')
            ->andWhere('pi.created_at between :dateStart and :dateEnd')
            ->andWhere('us.is_active = 0')
            ->andWhere('ui.is_active = 1')
            ->andWhere('pi.hireNow = 1')
            ->andWhere('ui.last_login between :dateStart and :dateEnd')
            ->setParameter(':dateStart', $checkDateStart)
            ->setParameter(':dateEnd', $checkDateEnd)
        ;
        return $qb->getQuery()->getResult();
    }

    /**
     * @return int
     */
    public function findCountVocalists()
    {
        $qb = $this->createQueryBuilder('ui')
            ->select('count(ui.id) as countVocalists')
            ->andWhere('ui.is_active = true')
            ->andWhere('ui.is_vocalist = true')
        ;

        return $qb->getQuery()->getResult()[0]['countVocalists'];
    }

    /**
     * @return int
     */
    public function findCountProducers()
    {
        $qb = $this->createQueryBuilder('ui')
            ->select('count(ui.id) as countProducers')
            ->andWhere('ui.is_active = true')
            ->andWhere('ui.is_producer = true')
        ;

        return $qb->getQuery()->getResult()[0]['countProducers'];
    }

    public function findVocalistsEmail()
    {
        $qb = $this->createQueryBuilder('ui')
            ->select('ui.email')
            ->andWhere('ui.is_active = true')
            ->andWhere('ui.is_vocalist = true')
        ;

        return $qb->getQuery()->getScalarResult();
    }

    public function findProducersEmail()
    {
        $qb = $this->createQueryBuilder('ui')
            ->select('ui.email')
            ->andWhere('ui.is_active = true')
            ->andWhere('ui.is_producer = true')
        ;

        return $qb->getQuery()->getScalarResult();
    }

    public function findAllUsersEmail()
    {
        $qb = $this->createQueryBuilder('ui')
            ->select('ui.email')
            ->andWhere('ui.is_active = true')
        ;

        return $qb->getQuery()->getScalarResult();
    }
}
