<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use App\Entity\UserInfo;

class UserConnectRepository extends EntityRepository
{
    /**
     * @param UserInfo $user
     *
     * @param string|null $sort
     * @return QueryBuilder
     */
    public function findUserConnectionsQb(UserInfo $user, $sort = 'date')
    {
        $qb = $this->createQueryBuilder('uc');

        $qb->select('uc, fui, tui')
            ->innerJoin('uc.to', 'tui')
            ->innerJoin('uc.from', 'fui')
            ->where('uc.to = :user')
            ->andWhere('fui.is_active = 1')
            ->setParameter('user', $user)
        ;

        switch ($sort) {
            case 'rating':
                $qb->orderBy('fui.rated_count', 'DESC')
                    ->addOrderBy('fui.rating', 'DESC')
                    ->addOrderBy('fui.last_login', 'DESC')
                ;
                break;
            default:
                $qb->orderBy('uc.created_at', 'DESC');
                break;
        }

        return  $qb;
    }
}