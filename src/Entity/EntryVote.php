<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EntryVoteRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="entry_vote")
 */
class EntryVote
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
    protected $user_info = null;

    /**
     * @ORM\ManyToOne(targetEntity="ProjectBid")
     */
    protected $project_bid;

    /**
     * @ORM\Column(type="string", length=40)
     */
    protected $ip_addr;

    /**
     * @ORM\Column(type="text")
     */
    protected $browser;

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
     * @param \App\Entity\UserInfo $userInfo
     *
     * @return ProjectAudioDownload
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
     * Set project_audio
     *
     * @param \App\Entity\ProjectAudio $projectAudio
     *
     * @return ProjectAudioDownload
     */
    public function setProjectAudio(\App\Entity\ProjectAudio $projectAudio = null)
    {
        $this->project_audio = $projectAudio;

        return $this;
    }

    /**
     * Get project_audio
     *
     * @return \App\Entity\ProjectAudio
     */
    public function getProjectAudio()
    {
        return $this->project_audio;
    }

    /**
     * Set ip_addr
     *
     * @param string $ipAddr
     *
     * @return EntryVote
     */
    public function setIpAddr($ipAddr)
    {
        $this->ip_addr = $ipAddr;

        return $this;
    }

    /**
     * Get ip_addr
     *
     * @return string
     */
    public function getIpAddr()
    {
        return $this->ip_addr;
    }

    /**
     * Set browser
     *
     * @param string $browser
     *
     * @return EntryVote
     */
    public function setBrowser($browser)
    {
        $this->browser = $browser;

        return $this;
    }

    /**
     * Get browser
     *
     * @return string
     */
    public function getBrowser()
    {
        return $this->browser;
    }

    /**
     * Set project_bid
     *
     * @param \App\Entity\ProjectBid $projectBid
     *
     * @return EntryVote
     */
    public function setProjectBid(\App\Entity\ProjectBid $projectBid = null)
    {
        $this->project_bid = $projectBid;

        return $this;
    }

    /**
     * Get project_bid
     *
     * @return \App\Entity\ProjectBid
     */
    public function getProjectBid()
    {
        return $this->project_bid;
    }
}