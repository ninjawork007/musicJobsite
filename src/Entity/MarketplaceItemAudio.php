<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MarketplaceItemAudioRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="marketplace_item_audio")
 */
class MarketplaceItemAudio
{
    const FLAG_FEATURED = 'F';

    const FLAG_MASTER = 'M';

    const FLAG_COMMENT = 'C';

    const FLAG_WORKING = 'W';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo", inversedBy="marketplace_item_audio")
     */
    protected $user_info;

    /**
     * @ORM\ManyToOne(targetEntity="MarketplaceItem", inversedBy="marketplace_item_audio")
     */
    protected $marketplace_item;

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
     * Slug
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $slug;

    /**
     * Audio flag
     * F = Featured on gig view
     * M = Master audio
     * W = Working audio
     * C = Comment audio
     *
     * @ORM\Column(type="string", length=2)
     */
    protected $flag;

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
     * Set user_info
     *
     * @param UserInfo $userInfo
     *
     * @return UserAudio
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
        $dir = 'uploads/audio/marketplace/' . $this->getId() . '/';

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
     * Set flag
     *
     * @param string $flag
     *
     * @return MarketplaceItemAudio
     */
    public function setFlag($flag)
    {
        $this->flag = $flag;

        return $this;
    }

    /**
     * Get flag
     *
     * @return string
     */
    public function getFlag()
    {
        return $this->flag;
    }

    /**
     * Set MarketplaceItem
     *
     * @param \App\Entity\MarketplaceItem $marketplaceItem
     *
     * @return MarketplaceItemAudio
     */
    public function setMarketplaceItem(\App\Entity\MarketplaceItem $marketplaceItem = null)
    {
        $this->marketplace_item = $marketplaceItem;

        return $this;
    }

    /**
     * Get MarketplaceItem
     *
     * @return \App\Entity\MarketplaceItem
     */
    public function getMarketplaceItem()
    {
        return $this->marketplace_item;
    }

    /**
     * Set wave_generated
     *
     * @param bool $waveGenerated
     *
     * @return MarketplaceItemAudio
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
     * Constructor
     */
    public function __construct()
    {
    }
}