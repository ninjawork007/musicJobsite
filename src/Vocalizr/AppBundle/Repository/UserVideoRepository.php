<?php

namespace Vocalizr\AppBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Class UserVideoRepository
 *
 * @package Vocalizr\AppBundle\Repository
 */
class UserVideoRepository extends EntityRepository
{
    /**
     * @param $userInfo
     * @param int $limit
     * @param int $offset
     *
     * @return array
     */
    public function getUserVideos($userInfo, $offset = 0, $limit = 10)
    {
        $q = $this->createQueryBuilder('uv')
            ->where('uv.userInfo = :userInfo')
            ->setParameter('userInfo', $userInfo)
            ->orderBy('uv.sortNumber')
            ->addOrderBy('uv.createdAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $q->getQuery()->getResult();
    }

    /**
     * @param $ids
     *
     * @return array
     */
    public function getUserVideosByIds($ids)
    {
        return $this->createQueryBuilder('uv')
            ->where('uv.id in (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()->getResult();
    }
}
