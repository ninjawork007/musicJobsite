<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

class EngineOrderRepository extends EntityRepository
{
    public function getOrderByUid($uid)
    {
        $q = $this->createQueryBuilder('eo')
                ->select('eo, ep, ui, eoa')
                ->innerJoin('eo.engine_product', 'ep')
                ->innerJoin('eo.user_info', 'ui')
                ->innerJoin('eo.assets', 'eoa')
                ->where('eo.uid = :uid');
        $params = [
            ':uid' => $uid,
        ];
        $q->setParameters($params);

        $query = $q->getQuery();

        try {
            return $query->getOneOrNullResult();
        } catch (\Doctrine\ORM\NonUniqueResultException $e) {
            return null;
        }
    }
}
