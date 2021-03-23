<?php

namespace Vocalizr\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Vocalizr\AppBundle\Repository\ProjectAudioRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="project_audio_download")
 */
class ProjectAudioDownload
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
     * @ORM\ManyToOne(targetEntity="ProjectAudio", inversedBy="project_audio_downloads")
     */
    protected $project_audio;

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
     * @return ProjectAudioDownload
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
     * @return ProjectAudioDownload
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
     * Set project_audio
     *
     * @param \Vocalizr\AppBundle\Entity\ProjectAudio $projectAudio
     *
     * @return ProjectAudioDownload
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
}