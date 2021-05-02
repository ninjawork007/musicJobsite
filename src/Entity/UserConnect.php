<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserConnectRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="user_connect")
 */
class UserConnect
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo", inversedBy="connections")
     */
    protected $to;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo")
     */
    protected $from;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $engaged = false;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $created_at;

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
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
     * Set engaged
     *
     * @param bool $engaged
     *
     * @return UserConnect
     */
    public function setEngaged($engaged)
    {
        $this->engaged = $engaged;

        return $this;
    }

    /**
     * Get engaged
     *
     * @return bool
     */
    public function getEngaged()
    {
        return $this->engaged;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     *
     * @return UserConnect
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
     * Set to
     *
     * @param \App\Entity\UserInfo $to
     *
     * @return UserConnect
     */
    public function setTo(\App\Entity\UserInfo $to = null)
    {
        $this->to = $to;

        return $this;
    }

    /**
     * Get to
     *
     * @return \App\Entity\UserInfo
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * Set from
     *
     * @param \App\Entity\UserInfo $from
     *
     * @return UserConnect
     */
    public function setFrom(\App\Entity\UserInfo $from = null)
    {
        $this->from = $from;

        return $this;
    }

    /**
     * @param UserInfo $currentUser
     * @return UserInfo
     */
    public function getOtherUser(UserInfo $currentUser)
    {
        return ($this->from === $currentUser ? $this->to : $this->from);
    }

    /**
     * Get from
     *
     * @return \App\Entity\UserInfo
     */
    public function getFrom()
    {
        return $this->from;
    }
}