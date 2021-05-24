<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\AppMessageReadRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="app_message_read")
 */
class AppMessageRead
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo")
     */
    protected $user_info;

    /**
     * @ORM\ManyToOne(targetEntity="AppMessage", inversedBy="read_by")
     */
    protected $app_message;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $read_at;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $closed_at;

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->read_at = new \DateTime();
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
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
     * Set read_at
     *
     * @param \DateTime $readAt
     *
     * @return AppMessageRead
     */
    public function setReadAt($readAt)
    {
        $this->read_at = $readAt;

        return $this;
    }

    /**
     * Get read_at
     *
     * @return \DateTime
     */
    public function getReadAt()
    {
        return $this->read_at;
    }

    /**
     * Set closed_at
     *
     * @param \DateTime $closedAt
     *
     * @return AppMessageRead
     */
    public function setClosedAt($closedAt)
    {
        $this->closed_at = $closedAt;

        return $this;
    }

    /**
     * Get closed_at
     *
     * @return \DateTime
     */
    public function getClosedAt()
    {
        return $this->closed_at;
    }

    /**
     * Set user_info
     *
     * @param \App\Entity\UserInfo $userInfo
     *
     * @return AppMessageRead
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

    /**
     * Set app_message
     *
     * @param \App\Entity\AppMessage $appMessage
     *
     * @return AppMessageRead
     */
    public function setAppMessage(\App\Entity\AppMessage $appMessage = null)
    {
        $this->app_message = $appMessage;

        return $this;
    }

    /**
     * Get app_message
     *
     * @return \App\Entity\AppMessage
     */
    public function getAppMessage()
    {
        return $this->app_message;
    }
}