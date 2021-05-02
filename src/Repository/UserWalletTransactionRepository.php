<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use App\Entity\UserInfo;
use App\Entity\UserWalletTransaction;

/**
 * UserWalletTransactionRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserWalletTransactionRepository extends EntityRepository
{
    /**
     * @param string   $type
     * @param UserInfo $user
     *
     * @return UserWalletTransaction|null
     */
    public function findLastByTypeAndUser($type, UserInfo $user)
    {
        $qb = $this->createQueryBuilder('uwt');

        $qb
            ->where('uwt.user_info = :user')
            ->andWhere('uwt.type = :type')

            ->orderBy('uwt.created_at', 'DESC')

            ->setMaxResults(1)

            ->setParameters([
                'user' => $user,
                'type' => $type,
            ])
        ;

        try {
            $transaction = $qb->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            $transaction = null;
        }

        return $transaction;
    }

    /**
     * @param int|UserInfo $user
     *
     * @return UserWalletTransaction[]
     */
    public function findTransactionsByUser($user)
    {
        return $this->findTransactionsByUserQb($user)->getQuery()->getResult();
    }

    /**
     * @param int|UserInfo $user
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function findTransactionsByUserQb($user)
    {
        $qb = $this->createQueryBuilder('s');
        $qb
            ->where('s.user_info = :user')

            ->orderBy('s.created_at', 'DESC')
            ->addOrderBy('s.id', 'DESC')

            ->setParameter(':user', $user)
        ;

        return $qb;
    }

    /**
     * @param $type
     * @param UserInfo $user
     * @return UserWalletTransaction[]
     */
    public function findTransactionsByTypeAndUser($type, UserInfo $user)
    {
        $qb = $this->createQueryBuilder('uwt');
        $qb
            ->andWhere('uwt.user_info = :user')
            ->andWhere('uwt.type = :type')

            ->orderBy('uwt.created_at', 'DESC')

            ->setParameters([
                'user' => $user,
                'type' => $type,
            ])
        ;
        return $qb->getQuery()->getResult();
    }


    /**
     * @param $txnId
     * @param UserInfo|null $user
     * @return UserWalletTransaction[]
     */
    public function findTransactionsAfterId($txnId, $user = null)
    {
        $qb = $this->createQueryBuilder('uwt');
        $qb
            ->andWhere('uwt.id > :id')
            ->setParameter('id', $txnId)

            ->addOrderBy('uwt.id', 'ASC')
            ;
        if ($user) {
            $qb
                ->andWhere('uwt.user_info = :user')
                ->setParameter('user', $user)
                ;
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param array|string $customId
     * @return UserWalletTransaction|null
     */
    public function findByCustomId($customId)
    {
        $qb = $this->createQueryBuilder('uwt');

        if (is_array($customId)) {
            $qb
                ->andWhere('uwt.custom_id IN (:customId)')
                ;
        } else {
            $qb
                ->andWhere('uwt.custom_id = :customId')
                ;
        }
        $qb
            ->setParameter('customId', $customId)
            ->setMaxResults(1)
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findCommissionsCurrentMonth()
    {
        $dateEnd = new \DateTime('now');
        $dateStart = new \DateTime('-'.($dateEnd->format('d') - 1).' day midnight');

        $qb = $this->createQueryBuilder('uwt')
            ->andWhere('uwt.created_at between :dateStart and :dateEnd')
            ->andWhere('uwt.description like :fee OR uwt.description like :feeContest')
            ->setParameter('dateStart', $dateStart)
            ->setParameter('dateEnd', $dateEnd)
            ->setParameter('fee', 'Gig fee taken%')
            ->setParameter('feeContest', 'Contest fee taken for%')
        ;

        return $qb->getQuery()->getResult();
    }

    public function findJobUpgradesCurrentMonth()
    {
        $dateEnd = new \DateTime('now');
        $dateStart = new \DateTime('-'.($dateEnd->format('d') - 1).' day midnight');

        $qb = $this->createQueryBuilder('uwt')
            ->andWhere('uwt.created_at between :dateStart and :dateEnd')
            ->andWhere('uwt.description like :job')
            ->setParameter('dateStart', $dateStart)
            ->setParameter('dateEnd', $dateEnd)
            ->setParameter('job', 'Upgrade charges for%')
        ;

        return $qb->getQuery()->getResult();
    }

    public function findJobUpgradesAllTime()
    {
        $qb = $this->createQueryBuilder('uwt')
            ->andWhere('uwt.description like :job')
            ->setParameter('job', 'Upgrade charges for%')
        ;

        return $qb->getQuery()->getResult();
    }

    public function findCommissionsAllTime()
    {
        $qb = $this->createQueryBuilder('uwt')
            ->andWhere('uwt.description like :fee OR uwt.description like :feeContest')
            ->setParameter('fee', 'Gig fee taken%')
            ->setParameter('feeContest', 'Contest fee taken for%')
        ;

        return $qb->getQuery()->getResult();
    }
}