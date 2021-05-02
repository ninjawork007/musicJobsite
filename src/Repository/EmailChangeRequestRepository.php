<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class EmailChangeRequestRepository extends EntityRepository
{
    /**
     * Case insenitive fimd one by email
     *
     * @param string $email
     */
    public function findOneByEmail($email)
    {
        $query = $this->createQueryBuilder('e')
                ->where('UPPER(e.email) = :email')
                ->setParameter('email', strtoupper($email));

        return $query->getQuery()->getOneOrNullResult();
    }
}
