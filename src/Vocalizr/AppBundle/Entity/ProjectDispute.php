<?php

namespace Vocalizr\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Vocalizr\AppBundle\Repository\ProjectDisputeRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="project_dispute")
 */
class ProjectDispute
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
     * @ORM\ManyToOne(targetEntity="UserInfo")
     */
    protected $from_user_info;

    /**
     * @ORM\ManyToOne(targetEntity="Project")
     */
    protected $project;

    /**
     * @ORM\Column(type="integer", length=11)
     */
    protected $amount = 0;

    /**
     * @ORM\Column(type="text")
     */
    protected $reason;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $accepted = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $updated_at = null;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $created_at;

    /**
     * @var int
     *
     * @ORM\Column(name="reminders_sent_count", options={"default": 0})
     */
    protected $remindersSentCount = 0;

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
     * Get amount in dollars.
     * Converts cents to dollars
     *
     * @return float
     */
    public function getAmountDollars()
    {
        return number_format($this->amount / 100, 2, '.', ',');
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
     * Set amount
     *
     * @param float $amount
     *
     * @return ProjectDispute
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set reason
     *
     * @param string $reason
     *
     * @return ProjectDispute
     */
    public function setReason($reason)
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * Get reason
     *
     * @return string
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Set accepted
     *
     * @param bool $accepted
     *
     * @return ProjectDispute
     */
    public function setAccepted($accepted)
    {
        $this->accepted = $accepted;

        return $this;
    }

    /**
     * Get accepted
     *
     * @return bool
     */
    public function getAccepted()
    {
        return $this->accepted;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     *
     * @return ProjectDispute
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
     * @param UserInfo $userInfo
     *
     * @return ProjectDispute
     */
    public function setUserInfo(UserInfo $userInfo = null)
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
     * Set from_user_info
     *
     * @param UserInfo $fromUserInfo
     *
     * @return ProjectDispute
     */
    public function setFromUserInfo(UserInfo $fromUserInfo = null)
    {
        $this->from_user_info = $fromUserInfo;

        return $this;
    }

    /**
     * Get from_user_info
     *
     * @return UserInfo
     */
    public function getFromUserInfo()
    {
        return $this->from_user_info;
    }

    /**
     * Set project
     *
     * @param \Vocalizr\AppBundle\Entity\Project $project
     *
     * @return ProjectDispute
     */
    public function setProject(\Vocalizr\AppBundle\Entity\Project $project = null)
    {
        $this->project = $project;

        return $this;
    }

    /**
     * Get project
     *
     * @return \Vocalizr\AppBundle\Entity\Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * Set updated_at
     *
     * @param \DateTime $updatedAt
     *
     * @return ProjectDispute
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
     * @return int
     */
    public function getRemindersSentCount()
    {
        return $this->remindersSentCount;
    }

    /**
     * @param int $remindersSentCount
     * @return ProjectDispute
     */
    public function setRemindersSentCount($remindersSentCount)
    {
        $this->remindersSentCount = $remindersSentCount;
        return $this;
    }
}