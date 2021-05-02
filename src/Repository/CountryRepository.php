<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use App\Entity\Country;

/**
 * Class CountryRepository
 */
class CountryRepository extends EntityRepository
{
    /**
     * @param string $code
     *
     * @return Country|null
     */
    public function findByCode($code)
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.code = :code')
            ->setParameter('code', $code)
        ;

        try {
            return $qb->getQuery()->getSingleResult();
        } catch (\Doctrine\ORM\NoResultException $e) {
            return null;
        } catch (\Doctrine\ORM\NonUniqueResultException $e) {
            return null;
        }
    }

    /**
     * @return QueryBuilder
     */
    public function findAllSort()
    {
        return $this->createQueryBuilder('c')
            ->addOrderBy('c.sort', 'ASC')
            ->addOrderBy('c.title', 'ASC')
        ;
    }
}