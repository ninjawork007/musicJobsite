<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserFollowRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="user_follow")
 */
class UserFollow
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo", inversedBy="user_follow")
     */
    protected $user_info;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo", inversedBy="following_users")
     */
    protected $follow_user;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $created_at;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $updated_at;

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->created_at = new \DateTime();
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updated_at = new \DateTime();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     *
     * @return UserFollow
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get created_at
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set updated_at
     *
     * @param \DateTime $updatedAt
     *
     * @return UserFollow
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    /**
     * Get updated_at
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * Set user_info
     *
     * @param UserInfo $userInfo
     *
     * @return UserFollow
     */
    public function setUserInfo(\App\Entity\UserInfo $userInfo = null)
    {
        $this->user_info = $userInfo;

        return $this;
    }

    /**
     * Get user_info
     *
     * @return UserInfo
     */
    public function getUserInfo()
    {
        return $this->user_info;
    }

    /**
     * Set follow_user
     *
     * @param UserInfo $followUser
     *
     * @return UserFollow
     */
    public function setFollowUser(\App\Entity\UserInfo $followUser = null)
    {
        $this->follow_user = $followUser;

        return $this;
    }

    /**
     * Get follow_user
     *
     * @return UserInfo
     */
    public function getFollowUser()
    {
        return $this->follow_user;
    }
}