<?php

namespace App\Helper;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Generator;
use App\Entity\UserInfo;

/**
 * Trait UserPagerTrait
 * @package App\Helper
 */
trait UserPagerTrait
{
    /**
     * @param EntityManager $em
     * @param int $perPage
     * @param QueryBuilder|null $qb
     * @return Generator|UserInfo[][]
     */
    protected function getPageGenerator(EntityManager $em, $perPage, $qb = null)
    {
        $userRepo = $em->getRepository('App:UserInfo');

        $usersCount = $userRepo->getUserCount();
        $pageCount  = (int)ceil($usersCount / $perPage);

        for ($page = 1; $page <= $pageCount; $page++) {
            /** @var UserInfo[] $users */
            yield $userRepo->findByPage($page, $perPage, $qb);
            $em->clear();
        }
    }
}