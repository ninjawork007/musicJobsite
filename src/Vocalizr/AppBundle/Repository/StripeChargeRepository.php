<?php

namespace Vocalizr\AppBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Vocalizr\AppBundle\Entity\StripeCharge;

class StripeChargeRepository extends EntityRepository
{
    /**
     * @return StripeCharge[]
     * @throws \Exception
     */
    public function findStripeChargeForCurrentMonth()
    {
        $dateEnd = new \DateTime('now');
        $dateStart = new \DateTime('-'.($dateEnd->format('d') - 1).' day midnight');

        $qb = $this->createQueryBuilder('ch')
            ->andWhere('ch.created_at between :dateStart and :dateEnd')
            ->andWhere('ch.data like :data')
            ->setParameter('data', '%Subscription update%')
            ->setParameter('dateStart', $dateStart)
            ->setParameter('dateEnd', $dateEnd)
        ;

        return $qb->getQuery()->getResult();
    }

    /**
     * @return StripeCharge[]
     * @throws \Exception
     */
    public function findStripeChargeForAllTime()
    {
        $qbPSD = $this->getEntityManager()->getRepository('VocalizrAppBundle:PaymentSessionData')
            ->createQueryBuilder('psd')
            ->leftJoin('psd.charge', 'charge')
            ->select('charge.id')
        ;
        $qb = $this->createQueryBuilder('ch')
//            ->andWhere($qbPSD->expr()->notIn('ch.id', $qbPSD->getDQL()))
            ->andWhere('ch.data like :data')
            ->setParameter('data', '%Subscription update%')
        ;

        return $qb->getQuery()->getResult();
    }
}
