<?php

namespace Vocalizr\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="Vocalizr\AppBundle\Repository\UserAudioRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="user_audio")
 */
class UserAudio
{
    const ALBUM_ARTS_WEB_DIRECTORY = '/uploads/album_art';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo", inversedBy="user_audio")
     */
    protected $user_info;

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $title;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    public $path;

    /**
     * @Assert\File(maxSize="2000000")
     */
    public $file;

    /**
     * Duration in millseconds
     *
     * @ORM\Column(type="integer", length=10)
     */
    protected $duration = 0;

    /**
     * Duration string
     *
     * @ORM\Column(type="string", length=10)
     */
    protected $duration_string = 0;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $default_audio = false;

    /**
     * Sound Cloud - File ID
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $sc_id = null;

    /**
     * @ORM\ManyToOne(targetEntity="UserAudio", inversedBy="user_id")
     * @ORM\JoinTable(name="UserScTrack", joinColumns={@ORM\JoinColumn(name="sc_id", referencedColumnName="sc_id")})
     */
    protected $sc_user_track = null;

    /**
     * Is file synced with Sound Cloud
     *
     * @ORM\Column(type="boolean")
     */
    protected $sc_synced = false;

    /**
     * Sound Cloud - Start date for syncing
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $sc_sync_start = null;

    /**
     * Sound Cloud - End date for syncing
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $sc_sync_finished = null;

    /**
     * Sound Cloud - Permalink URL
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $sc_permalink_url = null;

    /**
     * Sound Cloud - Stream URL
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $sc_stream_url = null;

    /**
     * Sound Cloud - Download URL
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $sc_download_url = null;

    /**
     * Sound Cloud - Raw Data
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $sc_raw = null;

    /**
     * Play count
     *
     * @ORM\Column(type="integer")
     */
    protected $play_count = 0;

    /**
     * Total likes
     *
     * @ORM\Column(type="integer")
     */
    protected $total_likes = 0;

    /**
     * If audio is queued for uploading
     *
     * @ORM\Column(type="integer")
     */
    protected $sc_upload_queued = 0;

    /**
     * Result of upload to soundcloud
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $sc_upload_result = null;

    /**
     * Slug
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $slug;

    /**
     * Has wave form been generated
     *
     * @ORM\Column(type="boolean")
     */
    protected $wave_generated = false;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $created_at;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $updated_at;

    /**
     * @var int|null
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $sort_order;

    /**
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $albumArt;

    /**
     * @ORM\OneToMany(targetEntity="Notification", mappedBy="user_audio", cascade={"remove"})
     */
    protected $notifications;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->notifications = new \Doctrine\Common\Collections\ArrayCollection();
    }

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
     * @ORM\PreUpdate
     * @ORM\PrePersist
     */
    public function updateSlug()
    {
        $this->slug = $this->_getSlug($this->title . ' ' . $this->_uniqueId());
    }

    public function _getSlug($sVar)
    {
        $sDelimiter = '-';
        $sVar       = urldecode($sVar);
        $sVar       = iconv('UTF-8', 'ASCII//TRANSLIT', $sVar);
        $sVar       = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $sVar);
        $sVar       = strtolower(trim($sVar, '-'));
        $sVar       = preg_replace("/[\/_|+ -]+/", $sDelimiter, $sVar);

        return $sVar;
    }

    public function _uniqueId($l = 8)
    {
        return substr(md5($this->created_at->getTimestamp() . $this->title), 0, $l);
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
     * @return UserAudio
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
     * @return UserAudio
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
     * @return int|null
     */
    public function getSortOrder()
    {
        return $this->sort_order;
    }

    /**
     * @param int|null $sort_order
     * @return UserAudio
     */
    public function setSortOrder($sort_order)
    {
        $this->sort_order = $sort_order;
        return $this;
    }

    /**
     * Set user_info
     *
     * @param UserInfo $userInfo
     *
     * @return UserAudio
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

    /**
     * Set sc_id
     *
     * @param int $scId
     *
     * @return UserAudio
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
     * Set sc_synced
     *
     * @param bool $scSynced
     *
     * @return UserAudio
     */
    public function setScSynced($scSynced)
    {
        $this->sc_synced = $scSynced;

        return $this;
    }

    /**
     * Get sc_synced
     *
     * @return bool
     */
    public function getScSynced()
    {
        return $this->sc_synced;
    }

    /**
     * Set sc_sync_start
     *
     * @param \DateTime $scSyncStart
     *
     * @return UserAudio
     */
    public function setScSyncStart($scSyncStart)
    {
        $this->sc_sync_start = $scSyncStart;

        return $this;
    }

    /**
     * Get sc_sync_start
     *
     * @return \DateTime
     */
    public function getScSyncStart()
    {
        return $this->sc_sync_start;
    }

    /**
     * Set sc_sync_finished
     *
     * @param \DateTime $scSyncFinished
     *
     * @return UserAudio
     */
    public function setScSyncFinished($scSyncFinished)
    {
        $this->sc_sync_finished = $scSyncFinished;

        return $this;
    }

    /**
     * Get sc_sync_finished
     *
     * @return \DateTime
     */
    public function getScSyncFinished()
    {
        return $this->sc_sync_finished;
    }

    /**
     * Set sc_permalink_url
     *
     * @param string $scPermalinkUrl
     *
     * @return UserAudio
     */
    public function setScPermalinkUrl($scPermalinkUrl)
    {
        $this->sc_permalink_url = $scPermalinkUrl;

        return $this;
    }

    /**
     * Get sc_permalink_url
     *
     * @return string
     */
    public function getScPermalinkUrl()
    {
        return $this->sc_permalink_url;
    }

    /**
     * Set sc_stream_url
     *
     * @param string $scStreamUrl
     *
     * @return UserAudio
     */
    public function setScStreamUrl($scStreamUrl)
    {
        $this->sc_stream_url = $scStreamUrl;

        return $this;
    }

    /**
     * Get sc_stream_url
     *
     * @return string
     */
    public function getScStreamUrl()
    {
        return $this->sc_stream_url;
    }

    /**
     * Set sc_download_url
     *
     * @param string $scDownloadUrl
     *
     * @return UserAudio
     */
    public function setScDownloadUrl($scDownloadUrl)
    {
        $this->sc_download_url = $scDownloadUrl;

        return $this;
    }

    /**
     * Get sc_download_url
     *
     * @return string
     */
    public function getScDownloadUrl()
    {
        return $this->sc_download_url;
    }

    public function getAbsolutePath()
    {
        return null === $this->path
            ? null
            : $this->getUploadRootDir() . '/' . $this->path;
    }

    public function getWebPath()
    {
        return null === $this->path
            ? null
            : $this->getUploadDir() . '/' . $this->path;
    }

    public function getUploadRootDir()
    {
        // the absolute directory path where uploaded
        // documents should be saved
        return __DIR__ . '/../../../../' . $this->getUploadDir();
    }

    protected function getUploadDir()
    {
        // get rid of the __DIR__ so it doesn't screw up
        // when displaying uploaded doc/image in the view.
        $dir = 'uploads/audio/user/' . $this->user_info->getId() . '/';

        return $dir;
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preUpload()
    {
        if (null !== $this->file) {
            // do whatever you want to generate a unique name
            $filename   = sha1(uniqid(mt_rand(), true));
            $this->path = $filename . '.' . $this->file->getExtension();
        }
    }

    /**
     * @ORM\PostPersist()
     * @ORM\PostUpdate()
     */
    public function upload()
    {
        if (null === $this->file) {
            return;
        }

        // If directory doesn't exist, create if
        if (!is_dir($this->getUploadRootDir())) {
            mkdir($this->getUploadRootDir(), 0777, true);
        }

        // if there is an error when moving the file, an exception will
        // be automatically thrown by move(). This will properly prevent
        // the entity from being persisted to the database on error
        $this->file->move($this->getUploadRootDir(), $this->path);

        unset($this->file);
    }

    /**
     * @ORM\PostRemove()
     */
    public function removeUpload()
    {
        if ($file = $this->getAbsolutePath()) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * @ORM\PostRemove()
     * Remove waveform's generated when audio is uploaded
     */
    public function removeWaveform()
    {
        $dir   = __DIR__ . '/../../../../web/waveform/';
        $file  = $dir . $this->slug . '.png';
        $file2 = $dir . $this->slug . '-roll.png';
        if (file_exists($file)) {
            unlink($file);
            unlink($file2);
        }
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return UserAudio
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
     * Set path
     *
     * @param string $path
     *
     * @return UserAudio
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set sc_raw
     *
     * @param string $scRaw
     *
     * @return UserAudio
     */
    public function setScRaw($scRaw)
    {
        $this->sc_raw = $scRaw;

        return $this;
    }

    /**
     * Get sc_raw
     *
     * @return string
     */
    public function getScRaw()
    {
        return $this->sc_raw;
    }

    /**
     * Set defaultAudio
     *
     * @param bool $defaultAudio
     *
     * @return UserAudio
     */
    public function setDefaultAudio($defaultAudio)
    {
        $this->default_audio = $defaultAudio;

        return $this;
    }

    /**
     * Get defaultAudio
     *
     * @return bool
     */
    public function getDefaultAudio()
    {
        return $this->default_audio;
    }

    /**
     * Set sc_user_track
     *
     * @param \Vocalizr\AppBundle\Entity\UserAudio $scUserTrack
     *
     * @return UserAudio
     */
    public function setScUserTrack(\Vocalizr\AppBundle\Entity\UserAudio $scUserTrack = null)
    {
        $this->sc_user_track = $scUserTrack;

        return $this;
    }

    /**
     * Get sc_user_track
     *
     * @return \Vocalizr\AppBundle\Entity\UserAudio
     */
    public function getScUserTrack()
    {
        return $this->sc_user_track;
    }

    /**
     * Set play_count
     *
     * @param int $playCount
     *
     * @return UserAudio
     */
    public function setPlayCount($playCount)
    {
        $this->play_count = $playCount;

        return $this;
    }

    /**
     * Get play_count
     *
     * @return int
     */
    public function getPlayCount()
    {
        return $this->play_count;
    }

    /**
     * Set slug
     *
     * @param string $slug
     *
     * @return UserAudio
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get slug
     *
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set duration
     *
     * @param int $duration
     *
     * @return UserAudio
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
     * Set duration_string
     *
     * @param string $durationString
     *
     * @return UserAudio
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

    /**
     * Set sc_upload_queued
     *
     * @param int $scUploadQueued
     *
     * @return UserAudio
     */
    public function setScUploadQueued($scUploadQueued)
    {
        $this->sc_upload_queued = $scUploadQueued;

        return $this;
    }

    /**
     * Get sc_upload_queued
     *
     * @return int
     */
    public function getScUploadQueued()
    {
        return $this->sc_upload_queued;
    }

    /**
     * Set sc_upload_result
     *
     * @param int $scUploadResult
     *
     * @return UserAudio
     */
    public function setScUploadResult($scUploadResult)
    {
        $this->sc_upload_result = $scUploadResult;

        return $this;
    }

    /**
     * Get sc_upload_result
     *
     * @return int
     */
    public function getScUploadResult()
    {
        return $this->sc_upload_result;
    }

    /**
     * Set wave_generated
     *
     * @param bool $waveGenerated
     *
     * @return UserAudio
     */
    public function setWaveGenerated($waveGenerated)
    {
        $this->wave_generated = $waveGenerated;

        return $this;
    }

    /**
     * Get wave_generated
     *
     * @return bool
     */
    public function getWaveGenerated()
    {
        return $this->wave_generated;
    }

    /**
     * Set total_likes
     *
     * @param int $totalLikes
     *
     * @return UserAudio
     */
    public function setTotalLikes($totalLikes)
    {
        $this->total_likes = $totalLikes;

        return $this;
    }

    /**
     * Get total_likes
     *
     * @return int
     */
    public function getTotalLikes()
    {
        return $this->total_likes;
    }

    /**
     * @return string
     */
    public function getAlbumArt()
    {
        return $this->albumArt;
    }

    /**
     * @return string
     */
    public function getAlbumArtWebPath()
    {
        return $this->albumArt ? self::ALBUM_ARTS_WEB_DIRECTORY . DIRECTORY_SEPARATOR . $this->albumArt : null;
    }

    /**
     * @param string $albumArt
     * @return UserAudio
     */
    public function setAlbumArt($albumArt)
    {
        $this->albumArt = $albumArt;
        return $this;
    }

    /**
     * Add notifications
     *
     * @param \Vocalizr\AppBundle\Entity\Notification $notifications
     *
     * @return UserAudio
     */
    public function addNotification(\Vocalizr\AppBundle\Entity\Notification $notifications)
    {
        $this->notifications[] = $notifications;

        return $this;
    }

    /**
     * Remove notifications
     *
     * @param \Vocalizr\AppBundle\Entity\Notification $notifications
     */
    public function removeNotification(\Vocalizr\AppBundle\Entity\Notification $notifications)
    {
        $this->notifications->removeElement($notifications);
    }

    /**
     * Get notifications
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getNotifications()
    {
        return $this->notifications;
    }
}