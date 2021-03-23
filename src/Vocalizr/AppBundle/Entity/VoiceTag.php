<?php

namespace Vocalizr\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Vocalizr\AppBundle\Repository\VoiceTagRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="voice_tag")
 */
class VoiceTag
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $name;

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
     * Voice tags assigned to user
     *
     * @ORM\OneToMany(targetEntity="UserVoiceTag", mappedBy="voice_tag")
     */
    protected $user_voice_tags;

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

    public function getTitle()
    {
        return $this->name;
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
     * @return UserFollow
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
     * @return UserFollow
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
     * Constructor
     */
    public function __construct()
    {
        $this->user_voice_tags = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return VoiceTag
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Add user_voice_tags
     *
     * @param \Vocalizr\AppBundle\Entity\UserVoiceTag $userVoiceTags
     *
     * @return VoiceTag
     */
    public function addUserVoiceTag(\Vocalizr\AppBundle\Entity\UserVoiceTag $userVoiceTags)
    {
        $this->user_voice_tags[] = $userVoiceTags;

        return $this;
    }

    /**
     * Remove user_voice_tags
     *
     * @param \Vocalizr\AppBundle\Entity\UserVoiceTag $userVoiceTags
     */
    public function removeUserVoiceTag(\Vocalizr\AppBundle\Entity\UserVoiceTag $userVoiceTags)
    {
        $this->user_voice_tags->removeElement($userVoiceTags);
    }

    /**
     * Get user_voice_tags
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserVoiceTags()
    {
        return $this->user_voice_tags;
    }
}