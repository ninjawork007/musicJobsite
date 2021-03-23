<?php

namespace Vocalizr\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Vocalizr\AppBundle\Repository\UserVoiceTagVoteRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="user_voice_tag_vote")
 */
class UserVoiceTagVote
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
    protected $from_user_info;

    /**
     * @ORM\ManyToOne(targetEntity="UserVoiceTag", inversedBy="user_voice_tag_votes")
     */
    protected $user_voice_tag;

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
     * @return UserVoiceTagVote
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
     * Set from_user_info
     *
     * @param \Vocalizr\AppBundle\Entity\UserInfo $fromUserInfo
     *
     * @return UserVoiceTagVote
     */
    public function setFromUserInfo(\Vocalizr\AppBundle\Entity\UserInfo $fromUserInfo = null)
    {
        $this->from_user_info = $fromUserInfo;

        return $this;
    }

    /**
     * Get from_user_info
     *
     * @return \Vocalizr\AppBundle\Entity\UserInfo
     */
    public function getFromUserInfo()
    {
        return $this->from_user_info;
    }

    /**
     * Set user_voice_tag
     *
     * @param \Vocalizr\AppBundle\Entity\UserVoiceTag $userVoiceTag
     *
     * @return UserVoiceTagVote
     */
    public function setUserVoiceTag(\Vocalizr\AppBundle\Entity\UserVoiceTag $userVoiceTag = null)
    {
        $this->user_voice_tag = $userVoiceTag;

        return $this;
    }

    /**
     * Get user_voice_tag
     *
     * @return \Vocalizr\AppBundle\Entity\UserVoiceTag
     */
    public function getUserVoiceTag()
    {
        return $this->user_voice_tag;
    }
}