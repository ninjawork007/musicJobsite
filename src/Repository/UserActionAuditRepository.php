<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use App\Entity\Project;
use App\Entity\UserActionAudit;
use App\Entity\UserInfo;
use App\Entity\UserWithdraw;

/**
 * Class UserActionAuditRepository
 * @package App\Repository
 * @see UserActionAudit
 */
class UserActionAuditRepository extends EntityRepository
{
    /**
     * @param string $action
     * @param UserInfo $user
     * @param Project|null $project
     * @param UserWithdraw|null $withdraw
     *
     * @return UserActionAudit|null
     */
    public function findLatestMatchingAuditRecord($action, UserInfo $user, Project $project = null, UserWithdraw $withdraw = null)
    {
        $qb = $this->createQueryBuilder('uaa');
        $qb
            ->where('uaa.action = :action')
            ->andWhere('uaa.user = :user')

            ->orderBy('uaa.createdAt', 'DESC')

            ->setMaxResults(1)
            ->setParameters([
                'action' => $action,
                'user'   => $user,
            ])
        ;

        if ($project) {
            $qb
                ->andWhere('uaa.project = :project')
                ->setParameter('project', $project)

            ;
        }

        if ($withdraw) {
            $qb
                ->andWhere('uaa.withdraw = :withdraw')
                ->setParameter('withdraw', $withdraw)
            ;
        }

        try {
            return $qb->getQuery()->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            throw new \LogicException('Unexpected state', 0, $e);
        }
    }
}