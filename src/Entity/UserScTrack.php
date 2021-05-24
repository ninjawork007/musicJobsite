<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserScTrackRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="user_sc_track", indexes={
 *          @ORM\Index(name="sc_idx", columns={"sc_id"})
 * })
 */
class UserScTrack
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
     * @ORM\Column(type="integer")
     */
    protected $sc_id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $title;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $description = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $permalink_url;

    /**
     * @ORM\Column(type="string", length=500)
     */
    protected $stream_url;

    /**
     * Duration in milliseconds
     *
     * @ORM\Column(type="integer")
     */
    protected $duration = 0;

    /**
     * Duration string
     *
     * @ORM\Column(type="string", length=10)
     */
    protected $duration_string = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $genre = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $bpm = null;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $user_favorite = false;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $raw_api_result = null;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $created_at;

    /**
     * Relationships
     */

    /**
     * @ORM\OneToMany(targetEntity="UserAudio", mappedBy="sc_user_track")
     * @ORM\JoinTable(name="UserAudio", joinColumns={@ORM\JoinColumn(name="sc_id", referencedColumnName="sc_id")})
     */
    protected $user_audio = null;

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->created_at = new \DateTime();
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->user_audio = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set sc_id
     *
     * @param int $scId
     *
     * @return UserScTrack
     */
    public function setScId($scId)
    {
        $this->sc_id = $scId;

        return $this;
    }

    /**
     * Get sc_id
     *
     * @return int
     */
    public function getScId()
    {
        return $this->sc_id;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return UserScTrack
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return UserScTrack
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set permalink_url
     *
     * @param string $permalinkUrl
     *
     * @return UserScTrack
     */
    public function setPermalinkUrl($permalinkUrl)
    {
        $this->permalink_url = $permalinkUrl;

        return $this;
    }

    /**
     * Get permalink_url
     *
     * @return string
     */
    public function getPermalinkUrl()
    {
        return $this->permalink_url;
    }

    /**
     * Set stream_url
     *
     * @param string $streamUrl
     *
     * @return UserScTrack
     */
    public function setStreamUrl($streamUrl)
    {
        $this->stream_url = $streamUrl;

        return $this;
    }

    /**
     * Get stream_url
     *
     * @return string
     */
    public function getStreamUrl()
    {
        return $this->stream_url;
    }

    /**
     * Set duration
     *
     * @param int $duration
     *
     * @return UserScTrack
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;

        return $this;
    }

    /**
     * Get duration
     *
     * @return int
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * Set genre
     *
     * @param int $genre
     *
     * @return UserScTrack
     */
    public function setGenre($genre)
    {
        $this->genre = $genre;

        return $this;
    }

    /**
     * Get genre
     *
     * @return int
     */
    public function getGenre()
    {
        return $this->genre;
    }

    /**
     * Set bpm
     *
     * @param int $bpm
     *
     * @return UserScTrack
     */
    public function setBpm($bpm)
    {
        $this->bpm = $bpm;

        return $this;
    }

    /**
     * Get bpm
     *
     * @return int
     */
    public function getBpm()
    {
        return $this->bpm;
    }

    /**
     * Set user_favorite
     *
     * @param bool $userFavorite
     *
     * @return UserScTrack
     */
    public function setUserFavorite($userFavorite)
    {
        $this->user_favorite = $userFavorite;

        return $this;
    }

    /**
     * Get user_favorite
     *
     * @return bool
     */
    public function getUserFavorite()
    {
        return $this->user_favorite;
    }

    /**
     * Set raw_api_result
     *
     * @param string $rawApiResult
     *
     * @return UserScTrack
     */
    public function setRawApiResult($rawApiResult)
    {
        $this->raw_api_result = $rawApiResult;

        return $this;
    }

    /**
     * Get raw_api_result
     *
     * @return string
     */
    public function getRawApiResult()
    {
        return $this->raw_api_result;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     *
     * @return UserScTrack
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
     * @return UserScTrack
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
     * Add user_audio
     *
     * @param \App\Entity\UserAudio $userAudio
     *
     * @return UserScTrack
     */
    public function addUserAudio(\App\Entity\UserAudio $userAudio)
    {
        $this->user_audio[] = $userAudio;

        return $this;
    }

    /**
     * Remove user_audio
     *
     * @param \App\Entity\UserAudio $userAudio
     */
    public function removeUserAudio(\App\Entity\UserAudio $userAudio)
    {
        $this->user_audio->removeElement($userAudio);
    }

    /**
     * Get user_audio
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserAudio()
    {
        return $this->user_audio;
    }

    /**
     * Set duration_string
     *
     * @param string $durationString
     *
     * @return UserScTrack
     */
    public function setDurationString($durationString)
    {
        $this->duration_string = $durationString;

        return $this;
    }

    /**
     * Get duration_string
     *
     * @return string
     */
    public function getDurationString()
    {
        return $this->duration_string;
    }
}