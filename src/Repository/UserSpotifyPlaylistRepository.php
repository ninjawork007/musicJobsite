<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use App\Entity\UserInfo;

/**
 * Class UserSpotifyPlaylistRepository
 *
 * @package App\Repository
 */
class UserSpotifyPlaylistRepository extends EntityRepository
{
    /**
     * @param $user
     * @param int $limit
     * @param int $offset
     *
     * @return array
     */
    public function getSpotifyPlaylists($user, $limit = 4, $offset = 0)
    {
        return $this->createQueryBuilder('usp')
            ->where('usp.userInfo = :user')
            ->setParameter('user', $user)
            ->orderBy('usp.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult($offset)
            ->getQuery()->getResult();
    }

    /**
     * @param UserInfo $user
     *
     * @return mixed
     */
    public function removeUserPlaylists($user)
    {
        $qb = $this->createQueryBuilder('usp');

        $qb
            ->delete()
            ->where('usp.userInfo = :user')
            ->setParameter('user', $user)
        ;
        return $qb->getQuery()->execute();
    }
}
