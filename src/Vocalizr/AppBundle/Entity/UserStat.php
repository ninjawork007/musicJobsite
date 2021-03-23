<?php

namespace Vocalizr\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Vocalizr\AppBundle\Repository\UserStatRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="user_stat")
 */
class UserStat
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\OneToOne(targetEntity="UserInfo", mappedBy="user_stat")
     */
    protected $user_info;

    /**
     * @ORM\Column(type="integer", length=11)
     */
    protected $profile_viewied = 0;

    /**
     * @ORM\Column(type="integer", length=11)
     */
    protected $in_search_results = 0;

    /**
     * @ORM\Column(type="integer", length=11)
     */
    protected $heard = 0;

    /**
     * @ORM\Column(type="integer", length=5)
     */
    protected $active_gigs = 0;

    /**
     * @ORM\Column(type="integer", length=5)
     */
    protected $completed_gigs = 0;

    /**
     * @ORM\Column(type="integer", length=11)
     */
    protected $rated = 0;

    /**
     * @ORM\Column(type="float")
     */
    protected $average_rating = 0;

    /**
     * @ORM\Column(type="integer", length=11)
     */
    protected $tagged = 0;

    /**
     * @ORM\Column(type="integer", length=11)
     */
    protected $followers = 0;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $updated_at;

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
     * Set profile_viewied
     *
     * @param int $profileViewied
     *
     * @return UserStat
     */
    public function setProfileViewied($profileViewied)
    {
        $this->profile_viewied = $profileViewied;

        return $this;
    }

    /**
     * Get profile_viewied
     *
     * @return int
     */
    public function getProfileViewied()
    {
        return $this->profile_viewied;
    }

    /**
     * Set in_search_results
     *
     * @param int $inSearchResults
     *
     * @return UserStat
     */
    public function setInSearchResults($inSearchResults)
    {
        $this->in_search_results = $inSearchResults;

        return $this;
    }

    /**
     * Get in_search_results
     *
     * @return int
     */
    public function getInSearchResults()
    {
        return $this->in_search_results;
    }

    /**
     * Set heard
     *
     * @param int $heard
     *
     * @return UserStat
     */
    public function setHeard($heard)
    {
        $this->heard = $heard;

        return $this;
    }

    /**
     * Get heard
     *
     * @return int
     */
    public function getHeard()
    {
        return $this->heard;
    }

    /**
     * Set active_gigs
     *
     * @param int $activeGigs
     *
     * @return UserStat
     */
    public function setActiveGigs($activeGigs)
    {
        $this->active_gigs = $activeGigs;

        return $this;
    }

    /**
     * Get active_gigs
     *
     * @return int
     */
    public function getActiveGigs()
    {
        return $this->active_gigs;
    }

    /**
     * Set completed_gigs
     *
     * @param int $completedGigs
     *
     * @return UserStat
     */
    public function setCompletedGigs($completedGigs)
    {
        $this->completed_gigs = $completedGigs;

        return $this;
    }

    /**
     * Get completed_gigs
     *
     * @return int
     */
    public function getCompletedGigs()
    {
        return $this->completed_gigs;
    }

    /**
     * Set rated
     *
     * @param int $rated
     *
     * @return UserStat
     */
    public function setRated($rated)
    {
        $this->rated = $rated;

        return $this;
    }

    /**
     * Get rated
     *
     * @return int
     */
    public function getRated()
    {
        return $this->rated;
    }

    /**
     * Set average_rating
     *
     * @param float $averageRating
     *
     * @return UserStat
     */
    public function setAverageRating($averageRating)
    {
        $this->average_rating = $averageRating;

        return $this;
    }

    /**
     * Get average_rating
     *
     * @return float
     */
    public function getAverageRating()
    {
        return $this->average_rating;
    }

    /**
     * Set tagged
     *
     * @param int $tagged
     *
     * @return UserStat
     */
    public function setTagged($tagged)
    {
        $this->tagged = $tagged;

        return $this;
    }

    /**
     * Get tagged
     *
     * @return int
     */
    public function getTagged()
    {
        return $this->tagged;
    }

    /**
     * Set followers
     *
     * @param int $followers
     *
     * @return UserStat
     */
    public function setFollowers($followers)
    {
        $this->followers = $followers;

        return $this;
    }

    /**
     * Get followers
     *
     * @return int
     */
    public function getFollowers()
    {
        return $this->followers;
    }

    /**
     * Set updated_at
     *
     * @param \DateTime $updatedAt
     *
     * @return UserStat
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
     * @return UserStat
     */
    public function setUserInfo(\Vocalizr\AppBundle\Entity\UserInfo $userInfo = null)
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
}