<?php

namespace Vocalizr\AppBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\Query\QueryException;
use Vocalizr\AppBundle\Entity\HintSkip;
use Vocalizr\AppBundle\Entity\UserInfo;

/**
 * Class HintSkipRepository
 *
 * @package Vocalizr\AppBundle\Repository
 */
class HintSkipRepository extends EntityRepository
{
    /**
     * @param UserInfo $user
     * @param int      $type
     *
     * @return bool
     */
    public function isSkipped(UserInfo $user, $type)
    {
        $qb = $this->createQueryBuilder('hs');
        $qb
            ->select($qb->expr()->count('hs.id'))
            ->where('hs.user = :user')
            ->andWhere('hs.hint = :hint_type')
            ->setParameters([
                'user'      => $user,
                'hint_type' => $type,
            ])
            ->setMaxResults(1)
        ;
        try {
            $count = $qb->getQuery()->getSingleScalarResult();
            return $count > 0;
        } catch (QueryException $e) {
            return false;
        }
    }

    /**
     * @param UserInfo $user
     * @param $type
     *
     * @return bool
     */
    public function setSkipped(UserInfo $user, $type)
    {
        if ($this->isSkipped($user, $type)) {
            return true;
        }

        $newSkipped = new HintSkip();
        $newSkipped
            ->setUser($user)
            ->setHint($type)
        ;

        $this->_em->persist($newSkipped);
        try {
            $this->_em->flush();
        } catch (OptimisticLockException $e) {
            return false;
        }
        return true;
    }

    /**
     * @param UserInfo $user
     * @param $type
     */
    public function removeSkip(UserInfo $user, $type)
    {
        $qb = $this->createQueryBuilder('hs')
            ->delete()
            ->where('hs.user = :user')
            ->andWhere('hs.hint = :hint_type')
            ->setParameters([
                'user'      => $user,
                'hint_type' => $type,
            ])
        ;

        $qb->getQuery()->execute();
    }
}
