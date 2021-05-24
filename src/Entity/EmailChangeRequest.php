<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EmailChangeRequestRepository")
 * @ORM\Table(name="email_change_request")
 * @ORM\HasLifecycleCallbacks()
 */
class EmailChangeRequest
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\OneToOne(targetEntity="UserInfo")
     * @ORM\JoinColumn(name="user_info_id", referencedColumnName="id")
     */
    protected $user_info;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    protected $email;

    /**
     * @ORM\Column(length=40)
     */
    protected $unique_key;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $created_at;

    /**
     * @ORM\PrePersist
     */
    public function setCreatedAtValue()
    {
        $this->created_at = new \DateTime();
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
     * @return EmailChangeRequest
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
     * Set unique_key
     *
     * @param string $uniqueKey
     *
     * @return EmailChangeRequest
     */
    public function setUniqueKey($uniqueKey)
    {
        $this->unique_key = $uniqueKey;

        return $this;
    }

    /**
     * Get unique_key
     *
     * @return string
     */
    public function getUniqueKey()
    {
        return $this->unique_key;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     *
     * @return EmailChangeRequest
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
     * Set user_info
     *
     * @param \App\Entity\UserInfo $userInfo
     *
     * @return EmailChangeRequest
     */
    public function setUserInfo(\App\Entity\UserInfo $userInfo = null)
    {
        $this->user_info = $userInfo;

        return $this;
    }

    /**
     * Get user_info
     *
     * @return \App\Entity\UserInfo
     */
    public function getUserInfo()
    {
        return $this->user_info;
    }
}