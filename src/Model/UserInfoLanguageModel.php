<?php

namespace App\Model;

use Doctrine\ORM\EntityManager;
use App\Entity\Language;
use App\Entity\UserInfo;
use App\Entity\UserInfoLanguage;

/**
 * Class UserInfoLanguageModel
 *
 * @package App\Model
 */
class UserInfoLanguageModel extends Model
{
    /**
     * @param UserInfo $user
     *
     * @return array
     */
    public function getLanguagesByUser($user)
    {
        return $this->em->getRepository(Language::class)->getLanguagesByUser($user);
    }

    /**
     * @param UserInfo   $user
     * @param Language[] $languages
     *
     * @return UserInfo
     */
    public function setUserInfoLanguages($user, $languages)
    {
        $needRemove = [];

        foreach ($user->getUserLanguages() as $currentUserLanguage) {
            foreach ($languages as $language) {
                if ($language->getId() == $currentUserLanguage->getLanguage()->getId()) {
                    continue 2;
                }
            }

            $needRemove[] = $currentUserLanguage;
        }

        foreach ($needRemove as $language) {
            $user->removeUserLanguage($language);
            $this->em->remove($language);
        }

        foreach ($languages as $language) {
            $found = false;

            foreach ($user->getUserLanguages() as $currentUserLanguage) {
                if ($language->getId() == $currentUserLanguage->getLanguage()->getId()) {
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $newLanguage = new UserInfoLanguage();
                $newLanguage->setUserInfo($user);
                $newLanguage->setLanguage($language);
                $user->addUserLanguage($newLanguage);
            }
        }

        return $user;
    }

    /**
     * @param $lang
     * @param $andEmpty
     *
     * @return string
     */
    public function getUserByLanguages($lang, $andEmpty)
    {
        $resultUsers = [];

        $userLanguages = $this->repository->getUserLanguages($lang);

        foreach ($userLanguages as $language) {
            if (!isset($resultUsers[$language->getUserInfo()->getId()])) {
                $resultUsers[$language->getUserInfo()->getId()] = true;
            }
        }

        if ($andEmpty) {
            $users = $this->em->getRepository(UserInfo::class)->getUserWithoutLanguages();

            foreach ($users as $user) {
                if (!isset($resultUsers[$user->getId()])) {
                    $resultUsers[$user->getId()] = true;
                }
            }
        }

        return array_keys($resultUsers);
    }

    protected function getEntityName()
    {
        return UserInfoLanguage::class;
    }
}