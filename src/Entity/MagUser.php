<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="mag_user")
 */
class MagUser
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Column(type="string", length=40)
     */
    protected $uid;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo")
     */
    protected $user_info = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $email;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $unsubscribe_at;

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
        $this->uid        = md5($this->email . rand(99, 9999999));
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
     * Set email
     *
     * @param string $email
     *
     * @return MagUser
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set unsubscribe_at
     *
     * @param \DateTime $unsubscribeAt
     *
     * @return MagUser
     */
    public function setUnsubscribeAt($unsubscribeAt)
    {
        $this->unsubscribe_at = $unsubscribeAt;

        return $this;
    }

    /**
     * Get unsubscribe_at
     *
     * @return \DateTime
     */
    public function getUnsubscribeAt()
    {
        return $this->unsubscribe_at;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     *
     * @return MagUser
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
     * @return MagUser
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
     * Set userInfo
     *
     * @param \App\Entity\UserInfo $userInfo
     *
     * @return MagUser
     */
    public function setUserInfo(\App\Entity\UserInfo $userInfo = null)
    {
        $this->userInfo = $userInfo;

        return $this;
    }

    /**
     * Get userInfo
     *
     * @return \App\Entity\UserInfo
     */
    public function getUserInfo()
    {
        return $this->userInfo;
    }

    /**
     * Set uid
     *
     * @param string $uid
     *
     * @return MagUser
     */
    public function setUid($uid)
    {
        $this->uid = $uid;

        return $this;
    }

    /**
     * Get uid
     *
     * @return string
     */
    public function getUid()
    {
        return $this->uid;
    }
}