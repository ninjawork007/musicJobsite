<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * Class UserInfoLanguageRepository
 *
 * @package App\Repository]
 */
class UserInfoLanguageRepository extends EntityRepository
{
    /**
     * @param $lang
     *
     * @return array
     */
    public function getUserLanguages($lang)
    {
        return $this->createQueryBuilder('ul')
            ->where('ul.language in (:language)')
            ->setParameter('language', $lang)
            ->getQuery()->getResult();
    }
}
