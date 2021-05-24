<?php

namespace Vocalizr\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Vocalizr\AppBundle\Repository\MessageRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="message")
 */
class Message
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
     * @ORM\ManyToOne(targetEntity="MessageThread", inversedBy="messages")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $message_thread;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo", inversedBy="messages_sent")
     */
    protected $user_info;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo", inversedBy="messages_received")
     */
    protected $to_user_info;

    /**
     * @ORM\Column(type="text")
     */
    protected $content = null;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $created_at;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $updated_at = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $read_at = null;

    /**
     * @ORM\Column(name="user_ip", type="string", nullable=true)
     */
    protected $userIp;

    /**
     * Relationships
     */

    /**
     * Message files
     *
     * @ORM\OneToMany(targetEntity="MessageFile", mappedBy="message")
     */
    protected $message_files;

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->setUserIp(!empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']);
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
     * @return Message
     */
    public function setContent($content)
    {
        $this->content = json_encode($content);

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return json_decode($this->content) ? json_decode($this->content) : $this->content;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     *
     * @return Message
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
     * @return Message
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
     * @return Message
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
     * Set message_thread
     *
     * @param \Vocalizr\AppBundle\Entity\MessageThread $messageThread
     *
     * @return Message
     */
    public function setMessageThread(\Vocalizr\AppBundle\Entity\MessageThread $messageThread = null)
    {
        $this->message_thread = $messageThread;

        return $this;
    }

    /**
     * Get message_thread
     *
     * @return \Vocalizr\AppBundle\Entity\MessageThread
     */
    public function getMessageThread()
    {
        return $this->message_thread;
    }

    /**
     * Set user_info
     *
     * @param \Vocalizr\AppBundle\Entity\UserInfo $userInfo
     *
     * @return Message
     */
    public function setUserInfo(\Vocalizr\AppBundle\Entity\UserInfo $userInfo = null)
    {
        $this->user_info = $userInfo;

        return $this;
    }

    /**
     * Get user_info
     *
     * @return \Vocalizr\AppBundle\Entity\UserInfo
     */
    public function getUserInfo()
    {
        return $this->user_info;
    }

    /**
     * Set to_user_info
     *
     * @param \Vocalizr\AppBundle\Entity\UserInfo $toUserInfo
     *
     * @return Message
     */
    public function setToUserInfo(\Vocalizr\AppBundle\Entity\UserInfo $toUserInfo = null)
    {
        $this->to_user_info = $toUserInfo;

        return $this;
    }

    /**
     * Get to_user_info
     *
     * @return \Vocalizr\AppBundle\Entity\UserInfo
     */
    public function getToUserInfo()
    {
        return $this->to_user_info;
    }

    /**
     * Set uuid
     *
     * @param string $uuid
     *
     * @return Message
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
     * Set message_files
     *
     * @param \Vocalizr\AppBundle\Entity\MessageFile $messageFiles
     *
     * @return Message
     */
    public function setMessageFiles(\Vocalizr\AppBundle\Entity\MessageFile $messageFiles = null)
    {
        $this->message_files = $messageFiles;

        return $this;
    }

    /**
     * Get message_files
     *
     * @return \Vocalizr\AppBundle\Entity\MessageFile
     */
    public function getMessageFiles()
    {
        return $this->message_files;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->message_files = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add message_files
     *
     * @param \Vocalizr\AppBundle\Entity\MessageFile $messageFiles
     *
     * @return Message
     */
    public function addMessageFile(\Vocalizr\AppBundle\Entity\MessageFile $messageFiles)
    {
        $this->message_files[] = $messageFiles;

        return $this;
    }

    /**
     * Remove message_files
     *
     * @param \Vocalizr\AppBundle\Entity\MessageFile $messageFiles
     */
    public function removeMessageFile(\Vocalizr\AppBundle\Entity\MessageFile $messageFiles)
    {
        $this->message_files->removeElement($messageFiles);
    }

    /**
     * @return mixed
     */
    public function getUserIp()
    {
        return $this->userIp;
    }

    /**
     * @param mixed $userIp
     */
    public function setUserIp($userIp)
    {
        $this->userIp = $userIp;
    }
}