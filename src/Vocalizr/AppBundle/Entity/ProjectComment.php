<?php

namespace Vocalizr\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Vocalizr\AppBundle\Repository\ProjectCommentRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="project_comment")
 */
class ProjectComment
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="Project", inversedBy="project_comments")
     */
    protected $project;

    /**
     * @ORM\ManyToOne(targetEntity="ProjectAudio")
     */
    protected $project_audio = null;

    /**
     * @ORM\OneToMany(targetEntity="ProjectFile", mappedBy="project_comment")
     */
    protected $project_files;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $content;

    /**
     * Comment from
     *
     * @ORM\ManyToOne(targetEntity="UserInfo")
     */
    protected $from;

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
     * @return ProjectComment
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
     * Set created_at
     *
     * @param \DateTime $createdAt
     *
     * @return ProjectComment
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
     * @return ProjectComment
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
     * Set project
     *
     * @param \Vocalizr\AppBundle\Entity\Project $project
     *
     * @return ProjectComment
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
     * Set from
     *
     * @param \Vocalizr\AppBundle\Entity\UserInfo $from
     *
     * @return ProjectComment
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
     * Set project_audio
     *
     * @param \Vocalizr\AppBundle\Entity\ProjectAudio $projectAudio
     *
     * @return ProjectComment
     */
    public function setProjectAudio(\Vocalizr\AppBundle\Entity\ProjectAudio $projectAudio = null)
    {
        $this->project_audio = $projectAudio;

        return $this;
    }

    /**
     * Get project_audio
     *
     * @return \Vocalizr\AppBundle\Entity\ProjectAudio
     */
    public function getProjectAudio()
    {
        return $this->project_audio;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->project_files = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add project_files
     *
     * @param \Vocalizr\AppBundle\Entity\ProjectFile $projectFiles
     *
     * @return ProjectComment
     */
    public function addProjectFile(\Vocalizr\AppBundle\Entity\ProjectFile $projectFiles)
    {
        $this->project_files[] = $projectFiles;

        return $this;
    }

    /**
     * Remove project_files
     *
     * @param \Vocalizr\AppBundle\Entity\ProjectFile $projectFiles
     */
    public function removeProjectFile(\Vocalizr\AppBundle\Entity\ProjectFile $projectFiles)
    {
        $this->project_files->removeElement($projectFiles);
    }

    /**
     * Get project_files
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProjectFiles()
    {
        return $this->project_files;
    }
}