<?php


namespace Vocalizr\AppBundle\Repository;

use Doctrine\ORM\EntityRepository;

class StripeInvoiceRepository extends EntityRepository
{
    public function findInvoicesForCurrentMonthBidUpgrades()
    {
        $dateEnd = new \DateTime('now');
        $dateStart = new \DateTime('-'.($dateEnd->format('d') - 1).' day midnight');

        $qb = $this->createQueryBuilder('si')
            ->leftJoin('si.products', 'products')
            ->andWhere('si.is_refund = false')
            ->andWhere('products.name = :name')
            ->andWhere('si.date_create_invoice between :dateStart and :dateEnd')
            ->setParameter('dateEnd', $dateEnd)
            ->setParameter('dateStart', $dateStart)
            ->setParameter(':name', 'paid_bid_highlights')
        ;

        return $qb->getQuery()->getResult();
    }
    public function findInvoicesForAllTimeBidUpgrades()
    {
        $qb = $this->createQueryBuilder('si')
            ->leftJoin('si.products', 'products')
            ->andWhere('si.is_refund = false')
            ->andWhere('products.name = :name')
            ->setParameter(':name', 'paid_bid_highlights')
        ;

        return $qb->getQuery()->getResult();
    }
    public function findInvoicesForCurrentMonthConnectionLimits()
    {
        $dateEnd = new \DateTime('now');
        $dateStart = new \DateTime('-'.($dateEnd->format('d') - 1).' day midnight');

        $qb = $this->createQueryBuilder('si')
            ->leftJoin('si.products', 'products')
            ->andWhere('si.is_refund = false')
            ->andWhere('products.name = :name')
            ->andWhere('si.date_create_invoice between :dateStart and :dateEnd')
            ->setParameter('dateEnd', $dateEnd)
            ->setParameter('dateStart', $dateStart)
            ->setParameter(':name', 'extend_connections_limit')
        ;

        return $qb->getQuery()->getResult();
    }

    public function findInvoicesForAllTimeConnectionsLimit()
    {
        $qb = $this->createQueryBuilder('si')
            ->leftJoin('si.products', 'products')
            ->andWhere('si.is_refund = false')
            ->andWhere('products.name = :name')
            ->setParameter(':name', 'extend_connections_limit')
        ;

        return $qb->getQuery()->getResult();
    }
    public function findInvoicesForCurrentMonthCerts()
    {
        $dateEnd = new \DateTime('now');
        $dateStart = new \DateTime('-'.($dateEnd->format('d') - 1).' day midnight');

        $qb = $this->createQueryBuilder('si')
            ->leftJoin('si.products', 'products')
            ->andWhere('si.is_refund = false')
            ->andWhere('products.name = :name')
            ->andWhere('si.date_create_invoice between :dateStart and :dateEnd')
            ->setParameter('dateEnd', $dateEnd)
            ->setParameter('dateStart', $dateStart)
            ->setParameter(':name', 'certified_user')
        ;

        return $qb->getQuery()->getResult();
    }
    public function findInvoicesForAllTimeCerts()
    {
        $qb = $this->createQueryBuilder('si')
            ->leftJoin('si.products', 'products')
            ->andWhere('si.is_refund = false')
            ->andWhere('products.name = :name')
            ->setParameter(':name', 'certified_user')
        ;

        return $qb->getQuery()->getResult();
    }
    public function findInvoicesForCurrentExtendContest()
    {
        $dateEnd = new \DateTime('now');
        $dateStart = new \DateTime('-'.($dateEnd->format('d') - 1).' day midnight');

        $qb = $this->createQueryBuilder('si')
            ->leftJoin('si.products', 'products')
            ->andWhere('si.is_refund = false')
            ->andWhere('products.name = :name')
            ->andWhere('si.date_create_invoice between :dateStart and :dateEnd')
            ->setParameter('dateEnd', $dateEnd)
            ->setParameter('dateStart', $dateStart)
            ->setParameter(':name', 'extend_contest')
        ;

        return $qb->getQuery()->getResult();
    }
    public function findInvoicesForAllTimeExtendContest()
    {
        $qb = $this->createQueryBuilder('si')
            ->leftJoin('si.products', 'products')
            ->andWhere('si.is_refund = false')
            ->andWhere('products.name = :name')
            ->setParameter(':name', 'extend_contest')
        ;

        return $qb->getQuery()->getResult();
    }
    public function findInvoicesForCurrentMonthJobUpgrade()
    {
        $dateEnd = new \DateTime('now');
        $dateStart = new \DateTime('-'.($dateEnd->format('d') - 1).' day midnight');

        $qb = $this->createQueryBuilder('si')
            ->leftJoin('si.products', 'products')
            ->andWhere('si.is_refund = false')
            ->andWhere('products.name = :lock_to_cert or products.name = :publish_type or products.name = :to_favorites or products.name = :restrict_to_preferences')
            ->andWhere('si.date_create_invoice between :dateStart and :dateEnd')
            ->setParameter('dateEnd', $dateEnd)
            ->setParameter('dateStart', $dateStart)
            ->setParameter(':lock_to_cert', 'lock_to_cert')
            ->setParameter(':publish_type', 'publish_type')
            ->setParameter(':to_favorites', 'to_favorites')
            ->setParameter(':restrict_to_preferences', 'restrict_to_preferences')
        ;

        return $qb->getQuery()->getResult();
    }
}