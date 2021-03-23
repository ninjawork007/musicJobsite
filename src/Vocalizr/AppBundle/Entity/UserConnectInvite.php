<?php

namespace Vocalizr\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Vocalizr\AppBundle\Repository\UserConnectInviteRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="user_connect_invite")
 */
class UserConnectInvite
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo", inversedBy="user_connect_invites")
     */
    protected $to;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo", inversedBy="user_connect_invites_sent")
     */
    protected $from;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $status = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $message = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $connected_at = null;

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

    public function getConnectedUser($user)
    {
        if ($this->getTo()->getId() == $user->getId()) {
            return $this->getFrom();
        }
        return $this->getTo();
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
     * Set content
     *
     * @param string $content
     *
     * @return UserMessage
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set read_at
     *
     * @param \DateTime $readAt
     *
     * @return UserMessage
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
     * Set created_at
     *
     * @param \DateTime $createdAt
     *
     * @return UserMessage
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
     * @return UserMessage
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
     * Set from
     *
     * @param UserInfo $from
     *
     * @return UserMessage
     */
    public function setFrom(\Vocalizr\AppBundle\Entity\UserInfo $from = null)
    {
        $this->from = $from;

        return $this;
    }

    /**
     * Get from
     *
     * @return \Vocalizr\AppBundle\Entity\UserInfo
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Set to
     *
     * @param UserInfo $to
     *
     * @return UserMessage
     */
    public function setTo(\Vocalizr\AppBundle\Entity\UserInfo $to = null)
    {
        $this->to = $to;

        return $this;
    }

    /**
     * Get to
     *
     * @return \Vocalizr\AppBundle\Entity\UserInfo
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * Set status
     *
     * @param bool $status
     *
     * @return UserConnect
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return bool
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set message
     *
     * @param string $message
     *
     * @return UserConnect
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set connected_at
     *
     * @param \DateTime $connectedAt
     *
     * @return UserConnect
     */
    public function setConnectedAt($connectedAt)
    {
        $this->connected_at = $connectedAt;

        return $this;
    }

    /**
     * Get connected_at
     *
     * @return \DateTime
     */
    public function getConnectedAt()
    {
        return $this->connected_at;
    }
}