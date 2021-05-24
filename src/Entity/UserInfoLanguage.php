<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserInfoLanguageRepository")
 * @ORM\Table(name="user_info_languages")
 */
class UserInfoLanguage
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo", inversedBy="userLanguages")
     */
    protected $userInfo;

    /**
     * @var Language
     *
     * @ORM\ManyToOne(targetEntity="Language", inversedBy="userLanguages")
     */
    protected $language;

    /**
     * @return string
     */
    public function __toString()
    {
        return ($this->language ? $this->language->__toString() : '');
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return UserInfo
     */
    public function getUserInfo()
    {
        return $this->userInfo;
    }

    /**
     * @param UserInfo $userInfo
     *
     * @return UserInfoLanguage
     */
    public function setUserInfo($userInfo)
    {
        $this->userInfo = $userInfo;

        return $this;
    }

    /**
     * @return Language
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param Language $language
     *
     * @return UserInfoLanguage
     */
    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }
}