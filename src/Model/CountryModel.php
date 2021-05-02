<?php

namespace App\Model;

use Doctrine\ORM\EntityManager;
use App\Entity\Country;
use App\Entity\UserInfo;
use App\Repository\CountryRepository;

/**
 * Class CountryModel
 */
class CountryModel extends Model
{
    /** @var CountryRepository $repository */
    protected $repository;

    public function getAll()
    {
        return $this->repository->findAll();
    }

    /**
     * @param string $code
     *
     * @return Country|null
     */
    public function byCode($code)
    {
        $code = strtoupper($code);
        return $this->repository->findByCode($code);
    }

    /**
     * @param UserInfo $user
     *
     * @return bool
     */
    public function migrateUser(UserInfo $user)
    {
        $code    = $user->getCountry();
        $code    = strtoupper($code);
        $country = $this->byCode($code);

        if (!$country) {
            return false;
        }

        $user->setUserCountry($country);

        return true;
    }

    protected function getEntityName()
    {
        return Country::class;
    }
}