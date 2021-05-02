<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ProjectActivityRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="project_activity")
 */
class ProjectActivity
{
    const ACTIVITY_TYPE_MESSAGE = 'message';

    const ACTIVITY_TYPE_START = 'commence';

    const ACTIVITY_TYPE_ASSET = 'asset';

    const ACTIVITY_TYPE_PROMPT_ASSET = 'prompt';

    const ACTIVITY_TYPE_LYRICS = 'lyrics';

    const ACTIVITY_TYPE_BID = 'bid';

    const ACTIVITY_TYPE_INVITE = 'invite';

    const ACTIVITY_TYPE_MASTER_AUDIO = 'master_audio';

    const ACTIVITY_TYPE_COMPLETED = 'completed';

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
     * @ORM\ManyToOne(targetEntity="Project")
     */
    protected $project;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo")
     */
    protected $actioned_user_info;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    protected $activity_type;

    /**
     * JSON data
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $data = null;

    /**
     * If the user has read this item
     *
     * @ORM\Column(type="boolean")
     */
    protected $activity_read = false;

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
     * Set activity_type
     *
     * @param string $activityType
     *
     * @return ProjectActivity
     */
    public function setActivityType($activityType)
    {
        $this->activity_type = $activityType;

        return $this;
    }

    /**
     * Get activity_type
     *
     * @return string
     */
    public function getActivityType()
    {
        return $this->activity_type;
    }

    /**
     * Set data
     *
     * @param string $data
     *
     * @return ProjectActivity
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get data
     *
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     *
     * @return ProjectActivity
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
     * @return ProjectActivity
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
     * Set project
     *
     * @param \App\Entity\Project $project
     *
     * @return ProjectActivity
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
     * Set activity_read
     *
     * @param bool $activityRead
     *
     * @return ProjectActivity
     */
    public function setActivityRead($activityRead)
    {
        $this->activity_read = $activityRead;

        return $this;
    }

    /**
     * Get activity_read
     *
     * @return bool
     */
    public function getActivityRead()
    {
        return $this->activity_read;
    }

    /**
     * Set actioned_user_info
     *
     * @param \App\Entity\UserInfo $actionedUserInfo
     *
     * @return ProjectActivity
     */
    public function setActionedUserInfo(\App\Entity\UserInfo $actionedUserInfo = null)
    {
        $this->actioned_user_info = $actionedUserInfo;

        return $this;
    }

    /**
     * Get actioned_user_info
     *
     * @return \App\Entity\UserInfo
     */
    public function getActionedUserInfo()
    {
        return $this->actioned_user_info;
    }
}