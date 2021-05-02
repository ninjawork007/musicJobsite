<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ProjectFeedRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="project_feed")
 */
class ProjectFeed
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo", inversedBy="project_feeds")
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
     * @ORM\Column(name="object_type", type="string", length=45, nullable=true)
     */
    private $object_type = null;

    /**
     * @ORM\Column(name="object_id", type="integer", nullable=true)
     */
    private $object_id = null;

    /**
     * JSON data
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $data = null;

    /**
     * If other project member has read feed item
     *
     * @ORM\Column(type="boolean")
     */
    protected $feed_read = false;

    /**
     * Notified by email
     *
     * @ORM\Column(type="boolean")
     */
    protected $notified = false;

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
     * Set object_type
     *
     * @param string $objectType
     *
     * @return ProjectFeed
     */
    public function setObjectType($objectType)
    {
        $this->object_type = $objectType;

        return $this;
    }

    /**
     * Get object_type
     *
     * @return string
     */
    public function getObjectType()
    {
        return $this->object_type;
    }

    /**
     * Set object_id
     *
     * @param int $objectId
     *
     * @return ProjectFeed
     */
    public function setObjectId($objectId)
    {
        $this->object_id = $objectId;

        return $this;
    }

    /**
     * Get object_id
     *
     * @return int
     */
    public function getObjectId()
    {
        return $this->object_id;
    }

    /**
     * Set data
     *
     * @param string $data
     *
     * @return ProjectFeed
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
     * @return ProjectFeed
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
     * Set project
     *
     * @param \App\Entity\Project $project
     *
     * @return ProjectFeed
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
     * Set feed_read
     *
     * @param bool $feedRead
     *
     * @return ProjectFeed
     */
    public function setFeedRead($feedRead)
    {
        $this->feed_read = $feedRead;

        return $this;
    }

    /**
     * Get feed_read
     *
     * @return bool
     */
    public function getFeedRead()
    {
        return $this->feed_read;
    }

    /**
     * Set from_user_info
     *
     * @param \App\Entity\UserInfo $fromUserInfo
     *
     * @return ProjectFeed
     */
    public function setFromUserInfo(\App\Entity\UserInfo $fromUserInfo = null)
    {
        $this->from_user_info = $fromUserInfo;

        return $this;
    }

    /**
     * Get from_user_info
     *
     * @return \App\Entity\UserInfo
     */
    public function getFromUserInfo()
    {
        return $this->from_user_info;
    }

    /**
     * Set user_info
     *
     * @param \App\Entity\UserInfo $userInfo
     *
     * @return ProjectFeed
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
     * Set notified
     *
     * @param bool $notified
     *
     * @return ProjectFeed
     */
    public function setNotified($notified)
    {
        $this->notified = $notified;

        return $this;
    }

    /**
     * Get notified
     *
     * @return bool
     */
    public function getNotified()
    {
        return $this->notified;
    }
}