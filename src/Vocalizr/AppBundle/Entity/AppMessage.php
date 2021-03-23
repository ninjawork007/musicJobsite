<?php

namespace Vocalizr\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="Vocalizr\AppBundle\Repository\AppMessageRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="app_message")
 */
class AppMessage
{
    const MESSAGE_TYPE_NEW = 'NEW';

    const MESSAGE_TYPE_ALERT = 'ALERT';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @Assert\NotBlank(message="Required")
     * @ORM\Column(type="string", length=16)
     */
    protected $message_type = AppMessage::MESSAGE_TYPE_NEW;

    /**
     * @ORM\Column(type="text")
     */
    protected $message;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $learn_more_link;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $expire_at;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $created_at;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $updated_at;

    /**
     * Users who have read the message
     *
     * @ORM\OneToMany(targetEntity="AppMessageRead", mappedBy="app_message")
     */
    protected $read_by;

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->created_at = new \DateTime();
        $this->updated_at = new \DateTime();
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
        $this->read_by = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set message
     *
     * @param string $message
     *
     * @return AppMessage
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
     * Set expire_at
     *
     * @param \DateTime $expireAt
     *
     * @return AppMessage
     */
    public function setExpireAt($expireAt)
    {
        $this->expire_at = $expireAt;

        return $this;
    }

    /**
     * Get expire_at
     *
     * @return \DateTime
     */
    public function getExpireAt()
    {
        return $this->expire_at;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     *
     * @return AppMessage
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
     * @return AppMessage
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
     * Add read_by
     *
     * @param \Vocalizr\AppBundle\Entity\AppMessageRead $readBy
     *
     * @return AppMessage
     */
    public function addReadBy(\Vocalizr\AppBundle\Entity\AppMessageRead $readBy)
    {
        $this->read_by[] = $readBy;

        return $this;
    }

    /**
     * Remove read_by
     *
     * @param \Vocalizr\AppBundle\Entity\AppMessageRead $readBy
     */
    public function removeReadBy(\Vocalizr\AppBundle\Entity\AppMessageRead $readBy)
    {
        $this->read_by->removeElement($readBy);
    }

    /**
     * Get read_by
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getReadBy()
    {
        return $this->read_by;
    }

    /**
     * Set message_type
     *
     * @param string $messageType
     *
     * @return AppMessage
     */
    public function setMessageType($messageType)
    {
        $this->message_type = $messageType;

        return $this;
    }

    /**
     * Get message_type
     *
     * @return string
     */
    public function getMessageType()
    {
        return $this->message_type;
    }

    /**
     * Set learn_more_link
     *
     * @param string $learnMoreLink
     *
     * @return AppMessage
     */
    public function setLearnMoreLink($learnMoreLink)
    {
        $this->learn_more_link = $learnMoreLink;

        return $this;
    }

    /**
     * Get learn_more_link
     *
     * @return string
     */
    public function getLearnMoreLink()
    {
        return $this->learn_more_link;
    }
}