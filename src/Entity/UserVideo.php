<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserVideoRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="user_videos")
 */
class UserVideo
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo", inversedBy="userVideos")
     * @ORM\JoinColumn(name="user_info_id", referencedColumnName="id")
     */
    private $userInfo;

    /**
     * @ORM\Column(type="string")
     */
    private $link;

    /**
     * @ORM\Column(type="string")
     */
    private $provider;

    /**
     * @ORM\Column(type="integer", options={"default":100})
     */
    private $sortNumber = 100;

    /**
     * @ORM\Column(type="datetime", name="created_at")
     */
    private $createdAt;

    /**
     * @var int
     *
     * @ORM\Column(type="integer", name="views_count", options={"default": 0})
     */
    private $viewsCount = 0;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", name="external_views_count", nullable=true)
     */
    private $externalViewsCount;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(type="datetime", name="external_data_updated_at", nullable=true)
     */
    private $externalDataUpdatedAt;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $title;

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
     */
    public function setUserInfo($userInfo)
    {
        $this->userInfo = $userInfo;
    }

    /**
     * @return string
     */
    public function getLink()
    {
        return $this->link;
    }

    /**
     * @param string $link
     */
    public function setLink($link)
    {
        $this->link = $link;
    }

    /**
     * @return string
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * @param string $provider
     */
    public function setProvider($provider)
    {
        $this->provider = $provider;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->setCreatedAt(new DateTime());
    }

    /**
     * @return mixed
     */
    public function getSortNumber()
    {
        return $this->sortNumber;
    }

    /**
     * @param mixed $sortNumber
     */
    public function setSortNumber($sortNumber)
    {
        $this->sortNumber = $sortNumber;
    }

    /**
     * @return int
     */
    public function getViewsCount()
    {
        return $this->viewsCount;
    }

    /**
     * @param int $viewsCount
     * @return UserVideo
     */
    public function setViewsCount($viewsCount)
    {
        $this->viewsCount = $viewsCount;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getExternalViewsCount()
    {
        return $this->externalViewsCount;
    }

    /**
     * @param int|null $externalViewsCount
     * @return UserVideo
     */
    public function setExternalViewsCount($externalViewsCount)
    {
        $this->externalViewsCount = $externalViewsCount;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getExternalDataUpdatedAt()
    {
        return $this->externalDataUpdatedAt;
    }

    /**
     * @param DateTime|null $externalDataUpdatedAt
     * @return UserVideo
     */
    public function setExternalDataUpdatedAt($externalDataUpdatedAt)
    {
        $this->externalDataUpdatedAt = $externalDataUpdatedAt;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string|null $title
     * @return UserVideo
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }
}