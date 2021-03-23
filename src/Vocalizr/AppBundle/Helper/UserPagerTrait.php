<?php

namespace Vocalizr\AppBundle\Helper;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Generator;
use Vocalizr\AppBundle\Entity\UserInfo;

/**
 * Trait UserPagerTrait
 * @package Vocalizr\AppBundle\Helper
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
        $userRepo = $em->getRepository('VocalizrAppBundle:UserInfo');

        $usersCount = $userRepo->getUserCount();
        $pageCount  = (int)ceil($usersCount / $perPage);

        for ($page = 1; $page <= $pageCount; $page++) {
            /** @var UserInfo[] $users */
            yield $userRepo->findByPage($page, $perPage, $qb);
            $em->clear();
        }
    }
}