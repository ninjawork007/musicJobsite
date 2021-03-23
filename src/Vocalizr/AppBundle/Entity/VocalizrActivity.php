<?php

namespace Vocalizr\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Vocalizr\AppBundle\Repository\VocalizrActivityRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="vocalizr_activity")
 */
class VocalizrActivity
{
    const ACTIVITY_TYPE_MESSAGE = 'message';

    const ACTIVITY_TYPE_NEW_PROJECT = 'new_project';

    const ACTIVITY_TYPE_NEW_CONTEST = 'new_project';

    const ACTIVITY_TYPE_NEW_MEMBER = 'new_member';

    const ACTIVITY_TYPE_PROJECT_AWARDED = 'project_awarded';

    const ACTIVITY_TYPE_PROJECT_COMPLETED = 'project_completed';

    const ACTIVITY_TYPE_ANNOUNCEMENT = 'announcement';

    const ACTIVITY_TYPE_TAG_VOTE = 'tag_vote';

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
    protected $actioned_user_info = null;

    /**
     * @ORM\ManyToOne(targetEntity="Project")
     */
    protected $project = null;

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
     * @ORM\Column(type="datetime")
     */
    protected $created_at;

    /**
     * If the user has read this item
     *
     * @ORM\Column(type="boolean")
     */
    protected $activity_read = false;

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
     * @return VocalizrActivity
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
     * @return VocalizrActivity
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
     * @return VocalizrActivity
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
     * @param \Vocalizr\AppBundle\Entity\UserInfo $userInfo
     *
     * @return VocalizrActivity
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
     * Set activity_read
     *
     * @param bool $activityRead
     *
     * @return VocalizrActivity
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
     * @param \Vocalizr\AppBundle\Entity\UserInfo $actionedUserInfo
     *
     * @return VocalizrActivity
     */
    public function setActionedUserInfo(\Vocalizr\AppBundle\Entity\UserInfo $actionedUserInfo = null)
    {
        $this->actioned_user_info = $actionedUserInfo;

        return $this;
    }

    /**
     * Get actioned_user_info
     *
     * @return \Vocalizr\AppBundle\Entity\UserInfo
     */
    public function getActionedUserInfo()
    {
        return $this->actioned_user_info;
    }

    /**
     * Set project
     *
     * @param \Vocalizr\AppBundle\Entity\Project $project
     *
     * @return VocalizrActivity
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
}