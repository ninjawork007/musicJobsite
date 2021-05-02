<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserVoiceTagRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="user_voice_tag")
 */
class UserVoiceTag
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo", inversedBy="user_voice_tags")
     */
    protected $user_info;

    /**
     * @ORM\ManyToOne(targetEntity="VoiceTag", inversedBy="user_voice_tags")
     */
    protected $voice_tag;

    /**
     * @ORM\Column(type="integer")
     */
    protected $agree = 0;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $created_at;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $updated_at;

    /**
     * Relationships
     */

    /**
     * @ORM\OneToMany(targetEntity="UserVoiceTagVote", mappedBy="user_voice_tag", cascade={"remove"})
     */
    protected $user_voice_tag_votes;

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
     * Set tag
     *
     * @param string $tag
     *
     * @return UserTag
     */
    public function setTag($tag)
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * Get tag
     *
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     *
     * @return UserTag
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
     * @return UserTag
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
     * Set user_info
     *
     * @param UserInfo $userInfo
     *
     * @return UserTag
     */
    public function setUserInfo(\App\Entity\UserInfo $userInfo = null)
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
     * Set tagged_by
     *
     * @param UserInfo $taggedBy
     *
     * @return UserTag
     */
    public function setTaggedBy(\App\Entity\UserInfo $taggedBy = null)
    {
        $this->tagged_by = $taggedBy;

        return $this;
    }

    /**
     * Get tagged_by
     *
     * @return UserInfo
     */
    public function getTaggedBy()
    {
        return $this->tagged_by;
    }

    /**
     * Set agree
     *
     * @param int $agree
     *
     * @return UserVoiceTag
     */
    public function setAgree($agree)
    {
        $this->agree = $agree;

        return $this;
    }

    /**
     * Get agree
     *
     * @return int
     */
    public function getAgree()
    {
        return $this->agree;
    }

    /**
     * Set voice_tag
     *
     * @param \App\Entity\VoiceTag $voiceTag
     *
     * @return UserVoiceTag
     */
    public function setVoiceTag(\App\Entity\VoiceTag $voiceTag = null)
    {
        $this->voice_tag = $voiceTag;

        return $this;
    }

    /**
     * Get voice_tag
     *
     * @return \App\Entity\VoiceTag
     */
    public function getVoiceTag()
    {
        return $this->voice_tag;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->user_voice_tag_votes = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add user_voice_tag_votes
     *
     * @param \App\Entity\UserVoiceTagVote $userVoiceTagVotes
     *
     * @return UserVoiceTag
     */
    public function addUserVoiceTagVote(\App\Entity\UserVoiceTagVote $userVoiceTagVotes)
    {
        $this->user_voice_tag_votes[] = $userVoiceTagVotes;

        return $this;
    }

    /**
     * Remove user_voice_tag_votes
     *
     * @param \App\Entity\UserVoiceTagVote $userVoiceTagVotes
     */
    public function removeUserVoiceTagVote(\App\Entity\UserVoiceTagVote $userVoiceTagVotes)
    {
        $this->user_voice_tag_votes->removeElement($userVoiceTagVotes);
    }

    /**
     * Get user_voice_tag_votes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserVoiceTagVotes()
    {
        return $this->user_voice_tag_votes;
    }
}