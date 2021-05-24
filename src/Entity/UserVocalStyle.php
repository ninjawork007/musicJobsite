<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserVocalStyleRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="user_vocal_style")
 */
class UserVocalStyle
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo", inversedBy="user_vocal_styles")
     */
    protected $user_info;

    /**
     * @ORM\ManyToOne(targetEntity="VocalStyle", inversedBy="user_vocal_styles")
     */
    protected $vocal_style;

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
     * @ORM\OneToMany(targetEntity="UserVocalStyleVote", mappedBy="user_vocal_style", cascade={"remove"})
     */
    protected $user_vocal_style_votes;

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
     * Set agree
     *
     * @param int $agree
     *
     * @return UserVocalStyle
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
     * Set created_at
     *
     * @param \DateTime $createdAt
     *
     * @return UserVocalStyle
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
     * @return UserVocalStyle
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
     * @param \App\Entity\UserInfo $userInfo
     *
     * @return UserVocalStyle
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
     * Set vocal_style
     *
     * @param \App\Entity\VocalStyle $vocalStyle
     *
     * @return UserVocalStyle
     */
    public function setVocalStyle(\App\Entity\VocalStyle $vocalStyle = null)
    {
        $this->vocal_style = $vocalStyle;

        return $this;
    }

    /**
     * Get vocal_style
     *
     * @return \App\Entity\VocalStyle
     */
    public function getVocalStyle()
    {
        return $this->vocal_style;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->user_vocal_style_votes = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Add user_vocal_style_votes
     *
     * @param \App\Entity\UserVocalStyleVote $userVocalStyleVotes
     *
     * @return UserVocalStyle
     */
    public function addUserVocalStyleVote(\App\Entity\UserVocalStyleVote $userVocalStyleVotes)
    {
        $this->user_vocal_style_votes[] = $userVocalStyleVotes;

        return $this;
    }

    /**
     * Remove user_vocal_style_votes
     *
     * @param \App\Entity\UserVocalStyleVote $userVocalStyleVotes
     */
    public function removeUserVocalStyleVote(\App\Entity\UserVocalStyleVote $userVocalStyleVotes)
    {
        $this->user_vocal_style_votes->removeElement($userVocalStyleVotes);
    }

    /**
     * Get user_vocal_style_votes
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserVocalStyleVotes()
    {
        return $this->user_vocal_style_votes;
    }
}