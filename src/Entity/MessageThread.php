<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MessageThreadRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="message_thread")
 */
class MessageThread
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    protected $uuid;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo")
     */
    protected $employer;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $num_employer_unread = 0;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $employer_last_read = null;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo")
     */
    protected $bidder;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $num_bidder_unread = 0;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $bidder_last_read = null;

    /**
     * @ORM\ManyToOne(targetEntity="Project", inversedBy="message_threads")
     */
    protected $project;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $is_open = true;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $created_at;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $updated_at = null;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $last_message_at = null;

    /**
     * Relationships
     */

    /**
     * Messages for this thread
     *
     * @ORM\OneToMany(targetEntity="Message", mappedBy="message_thread")
     * @ORM\OrderBy({"created_at" = "ASC"})
     */
    protected $messages;

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->created_at = new \DateTime();
        $this->updated_at = new \DateTime();
        $this->uuid       = uniqid();
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updated_at = new \DateTime();
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->messages = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return MessageThread
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
     * @return MessageThread
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
     * Set read_at
     *
     * @param \DateTime $readAt
     *
     * @return MessageThread
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
     * Set employer
     *
     * @param \App\Entity\UserInfo $employer
     *
     * @return MessageThread
     */
    public function setEmployer(\App\Entity\UserInfo $employer = null)
    {
        $this->employer = $employer;

        return $this;
    }

    /**
     * Get employer
     *
     * @return \App\Entity\UserInfo
     */
    public function getEmployer()
    {
        return $this->employer;
    }

    /**
     * Set bidder
     *
     * @param \App\Entity\UserInfo $bidder
     *
     * @return MessageThread
     */
    public function setBidder(\App\Entity\UserInfo $bidder = null)
    {
        $this->bidder = $bidder;

        return $this;
    }

    /**
     * Get bidder
     *
     * @return \App\Entity\UserInfo
     */
    public function getBidder()
    {
        return $this->bidder;
    }

    /**
     * Set project
     *
     * @param \App\Entity\Project $project
     *
     * @return MessageThread
     */
    public function setProject(\App\Entity\Project $project = null)
    {
        $this->project = $project;

        return $this;
    }

    /**
     * Get project
     *
     * @return \App\Entity\Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * Add messages
     *
     * @param \App\Entity\Message $messages
     *
     * @return MessageThread
     */
    public function addMessage(\App\Entity\Message $messages)
    {
        $this->messages[] = $messages;

        return $this;
    }

    /**
     * Remove messages
     *
     * @param \App\Entity\Message $messages
     */
    public function removeMessage(\App\Entity\Message $messages)
    {
        $this->messages->removeElement($messages);
    }

    /**
     * Get messages
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Set uuid
     *
     * @param string $uuid
     *
     * @return MessageThread
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * Get uuid
     *
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * Set num_employer_unread
     *
     * @param int $numEmployerUnread
     *
     * @return MessageThread
     */
    public function setNumEmployerUnread($numEmployerUnread)
    {
        $this->num_employer_unread = $numEmployerUnread;

        return $this;
    }

    /**
     * Get num_employer_unread
     *
     * @return int
     */
    public function getNumEmployerUnread()
    {
        return $this->num_employer_unread;
    }

    /**
     * Set num_bidder_unread
     *
     * @param int $numBidderUnread
     *
     * @return MessageThread
     */
    public function setNumBidderUnread($numBidderUnread)
    {
        $this->num_bidder_unread = $numBidderUnread;

        return $this;
    }

    /**
     * Get num_bidder_unread
     *
     * @return int
     */
    public function getNumBidderUnread()
    {
        return $this->num_bidder_unread;
    }

    /**
     * Set employer_last_read
     *
     * @param \DateTime $employerLastRead
     *
     * @return MessageThread
     */
    public function setEmployerLastRead($employerLastRead)
    {
        $this->employer_last_read = $employerLastRead;

        return $this;
    }

    /**
     * Get employer_last_read
     *
     * @return \DateTime
     */
    public function getEmployerLastRead()
    {
        return $this->employer_last_read;
    }

    /**
     * Set bidder_last_read
     *
     * @param \DateTime $bidderLastRead
     *
     * @return MessageThread
     */
    public function setBidderLastRead($bidderLastRead)
    {
        $this->bidder_last_read = $bidderLastRead;

        return $this;
    }

    /**
     * Get bidder_last_read
     *
     * @return \DateTime
     */
    public function getBidderLastRead()
    {
        return $this->bidder_last_read;
    }

    /**
     * Set last_message_at
     *
     * @param \DateTime $lastMessageAt
     *
     * @return MessageThread
     */
    public function setLastMessageAt($lastMessageAt)
    {
        $this->last_message_at = $lastMessageAt;

        return $this;
    }

    /**
     * Get last_message_at
     *
     * @return \DateTime
     */
    public function getLastMessageAt()
    {
        return $this->last_message_at;
    }

    /**
     * Set is_open
     *
     * @param bool $isOpen
     *
     * @return MessageThread
     */
    public function setIsOpen($isOpen)
    {
        $this->is_open = $isOpen;

        return $this;
    }

    /**
     * Get is_open
     *
     * @return bool
     */
    public function getIsOpen()
    {
        return $this->is_open;
    }
}