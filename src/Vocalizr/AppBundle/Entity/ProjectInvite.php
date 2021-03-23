<?php

namespace Vocalizr\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Vocalizr\AppBundle\Repository\ProjectInviteRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="project_invite")
 */
class ProjectInvite
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo", inversedBy="project_invites")
     */
    protected $user_info;

    /**
     * @ORM\ManyToOne(targetEntity="Project", inversedBy="project_invites")
     */
    protected $project;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $deleted = false;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $hireNow = false;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $read_at;

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
     * Set created_at
     *
     * @param \DateTime $createdAt
     *
     * @return ProjectInvite
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
     * @return ProjectInvite
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
     * Set project
     *
     * @param \Vocalizr\AppBundle\Entity\Project $project
     *
     * @return ProjectInvite
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
     * Set deleted
     *
     * @param bool $deleted
     *
     * @return ProjectInvite
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;

        return $this;
    }

    /**
     * Get deleted
     *
     * @return bool
     */
    public function getDeleted()
    {
        return $this->deleted;
    }

    /**
     * Set hireNow
     *
     * @param $hireNow
     * @return ProjectInvite
     */
    public function setHireNow($hireNow)
    {
        $this->hireNow = $hireNow;

        return $this;
    }

    /**
     * Get hireNow
     *
     * @return bool
     */
    public function getHireNow()
    {
        return $this->hireNow;
    }

    /**
     * Set read_at
     *
     * @param \DateTime $readAt
     *
     * @return ProjectInvite
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
}