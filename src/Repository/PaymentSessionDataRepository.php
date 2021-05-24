<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use App\Entity\PaymentSessionData;
use App\Entity\Project;
use App\Entity\UserInfo;

/**
 * Class PaymentSessionData
 * @package App\Repository
 */
class PaymentSessionDataRepository extends EntityRepository
{

    /**
     * @param UserInfo $user
     * @return PaymentSessionData|null
     * @throws NonUniqueResultException
     */
    public function findOneByUserForCertification(UserInfo $user)
    {
        $qb = $this->createQueryBuilder('psd')
            ->where('psd.user = :user')
            ->andWhere('psd.userCertification IS NOT NULL')
            ->setParameter('user', $user->getId())
            ->orderBy('psd.id', 'DESC')
            ->setMaxResults(1)
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param Project $project
     * @return int|mixed|string|null
     * @throws NonUniqueResultException
     */
    public function findPaymentSessionDataByProjectAndCharge(Project $project)
    {
        $qb = $this->createQueryBuilder('psd')
            ->where('psd.project = :project')
            ->andWhere('psd.charge IS NOT NULL')
            ->setParameter('project', $project->getId())
            ->orderBy('psd.id', 'DESC')
            ->setMaxResults(1)
        ;

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findForCurrentMonth()
    {
        $dateEnd = new \DateTime('now');
        $dateStart = new \DateTime('-'.($dateEnd->format('d') - 1).' day midnight');

        $qb = $this->createQueryBuilder('psd')
            ->leftJoin('psd.charge', 'ch')
            ->andWhere('ch.created_at between :dateStart and :dateEnd')
            ->setParameter('dateStart', $dateStart)
            ->setParameter('dateEnd', $dateEnd)
        ;

        return $qb->getQuery()->getResult();
    }

}
