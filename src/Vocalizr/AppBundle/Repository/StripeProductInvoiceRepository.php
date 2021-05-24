<?php


namespace Vocalizr\AppBundle\Repository;

use Doctrine\ORM\EntityRepository;

class StripeProductInvoiceRepository extends EntityRepository
{

    public function findInvoicesForCurrentMonthSubscriptions()
    {
        $dateEnd = new \DateTime('now');
        $dateStart = new \DateTime('-'.($dateEnd->format('d') - 1).' day midnight');

        $qb = $this->createQueryBuilder('spi')
            ->leftJoin('spi.stripe_invoice', 'si')
            ->andWhere('spi.is_refund = false')
            ->andWhere('si.is_refund = false')
            ->andWhere('spi.name = :name')
            ->andWhere('si.date_create_invoice between :dateStart and :dateEnd')
            ->setParameter('dateEnd', $dateEnd)
            ->setParameter('dateStart', $dateStart)
            ->setParameter(':name', 'subscriptions')
        ;

        return $qb->getQuery()->getResult();
    }

    public function findInvoicesForAllTimeSubscriptions()
    {
        $qb = $this->createQueryBuilder('spi')
            ->leftJoin('spi.stripe_invoice', 'si')
            ->andWhere('spi.is_refund = false')
            ->andWhere('si.is_refund = false')
            ->andWhere('spi.name = :name')
            ->setParameter(':name', 'subscriptions')
        ;

        return $qb->getQuery()->getResult();
    }

    public function findInvoicesForCurrentMonthJobUpgrade()
    {
        $dateEnd = new \DateTime('now');
        $dateStart = new \DateTime('-'.($dateEnd->format('d') - 1).' day midnight');

        $qb = $this->createQueryBuilder('spi')
            ->leftJoin('spi.stripe_invoice', 'si')
            ->andWhere('spi.is_refund = false')
            ->andWhere('si.is_refund = false')
            ->andWhere('spi.name = :lock_to_cert or spi.name = :publish_type or spi.name = :to_favorites or spi.name = :restrict_to_preferences')
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

    public function findInvoicesForAllTimeJobUpgrade()
    {
        $qb = $this->createQueryBuilder('spi')
            ->leftJoin('spi.stripe_invoice', 'si')
            ->andWhere('spi.is_refund = false')
            ->andWhere('si.is_refund = false')
            ->andWhere('spi.name = :lock_to_cert or spi.name = :publish_type or spi.name = :to_favorites or spi.name = :restrict_to_preferences')
            ->setParameter(':lock_to_cert', 'lock_to_cert')
            ->setParameter(':publish_type', 'publish_type')
            ->setParameter(':to_favorites', 'to_favorites')
            ->setParameter(':restrict_to_preferences', 'restrict_to_preferences')
        ;

        return $qb->getQuery()->getResult();
    }
}