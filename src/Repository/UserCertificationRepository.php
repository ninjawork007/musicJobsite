<?php

namespace App\Repository;

use DateTime;
use Doctrine\ORM\EntityRepository;

/**
 * Class UserCertificationRepository
 * @package App\Repository
 */
class UserCertificationRepository extends EntityRepository
{
    /**
     * @param DateTime $fromDate
     * @param bool|null $successful
     * @return int
     */
    public function findRequestsCount(DateTime $fromDate, $successful = null)
    {
        $qb = $this->createQueryBuilder('us');
        $qb
            ->select('count(us.id)')
            ->setParameter('from_date', $fromDate)
        ;

        $qb->andWhere('us.paid = 1');

        if (is_null($successful)) {
            $qb->andWhere('us.createdAt >= :from_date');
        } else {
            $qb
                ->andWhere('us.validatedAt >= :from_date')
                ->andWhere('us.succeed = :succeed')
                ->setParameter('succeed', $successful)
            ;
        }

        return $qb->getQuery()->getSingleScalarResult();
    }

    public function findUserConfirmationsQuery()
    {
        $qb = $this->createQueryBuilder('uc')
            ->where('uc.paid = true')
            ->andWhere('uc.validatedAt is NULL')
        ;

        return $qb->getQuery();
    }
}
