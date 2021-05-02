<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class UserCertification
 * @package App\Entity
 * @ORM\Entity(repositoryClass="App\Repository\UserCertificationRepository")
 * @ORM\Table(name="user_certification")
 */
class UserCertification
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $userName;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $spotifyOrAppleMusicLink;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $soundcloudLink;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $facebookPage;

    /**
     * @var UserInfo|null
     *
     * @ORM\ManyToOne(targetEntity="UserInfo")
     * @ORM\JoinColumn(name="userinfo_id", referencedColumnName="id", nullable=false)
     */
    private $userInfo;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $validatedAt;

    /**
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $succeed;

    /**
     * @var bool|null
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    private $paid;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getUserName()
    {
        return $this->userName;
    }

    /**
     * @param string|null $userName
     * @return UserCertification
     */
    public function setUserName($userName)
    {
        $this->userName = $userName;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSpotifyOrAppleMusicLink()
    {
        return $this->spotifyOrAppleMusicLink;
    }

    /**
     * @param string|null $spotifyOrAppleMusicLink
     * @return UserCertification
     */
    public function setSpotifyOrAppleMusicLink($spotifyOrAppleMusicLink)
    {
        $this->spotifyOrAppleMusicLink = $spotifyOrAppleMusicLink;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSoundcloudLink()
    {
        return $this->soundcloudLink;
    }

    /**
     * @param string|null $soundcloudLink
     * @return UserCertification
     */
    public function setSoundcloudLink($soundcloudLink)
    {
        $this->soundcloudLink = $soundcloudLink;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFacebookPage()
    {
        return $this->facebookPage;
    }

    /**
     * @param string|null $facebookPage
     * @return UserCertification
     */
    public function setFacebookPage($facebookPage)
    {
        $this->facebookPage = $facebookPage;
        return $this;
    }

    /**
     * @return UserInfo|null
     */
    public function getUserInfo()
    {
        return $this->userInfo;
    }

    /**
     * @param UserInfo|null $userInfo
     * @return UserCertification
     */
    public function setUserInfo($userInfo)
    {
        $this->userInfo = $userInfo;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime|null $createdAt
     * @return UserCertification
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getValidatedAt()
    {
        return $this->validatedAt;
    }

    /**
     * @param DateTime|null $validatedAt
     * @return UserCertification
     */
    public function setValidatedAt($validatedAt)
    {
        $this->validatedAt = $validatedAt;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getSucceed()
    {
        return $this->succeed;
    }

    /**
     * @param bool|null $succeed
     * @return UserCertification
     */
    public function setSucceed($succeed)
    {
        $this->succeed = $succeed;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function getPaid()
    {
        return $this->paid;
    }

    /**
     * @param bool|null $paid
     * @return UserCertification
     */
    public function setPaid($paid)
    {
        $this->paid = $paid;
        return $this;
    }
}
