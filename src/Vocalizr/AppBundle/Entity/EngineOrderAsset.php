<?php

namespace Vocalizr\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="Vocalizr\AppBundle\Repository\EngineOrderAssetRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="engine_order_asset")
 */
class EngineOrderAsset
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
     * @ORM\ManyToOne(targetEntity="EngineOrder", inversedBy="assets")
     */
    protected $engine_order;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $title;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    public $path;

    /**
     * @ORM\Column(type="string", length=1000, nullable=true)
     */
    public $dropbox_link;

    /**
     * @Assert\File(maxSize="2000000")
     */
    public $file;

    /**
     * File mime type
     *
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $mime_type = null;

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
     * @ORM\PreUpdate
     * @ORM\PrePersist
     */
    public function updateSlug()
    {
        $path       = pathinfo($this->title);
        $this->slug = $this->_getSlug($path['filename'] . ' ' . $this->_uniqueId()) . '.' . $path['extension'];
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

    public function getAbsolutePath()
    {
        return null === $this->path
            ? null
            : $this->getUploadRootDir() . '/' . $this->path;
    }

    public function getAbsolutePreviewPath()
    {
        return null === $this->path
            ? null
            : $this->getUploadRootDir() . '/' . $this->preview_path;
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
        $dir = 'uploads/engine/' . $this->engine_order->getId() . '/assets/';
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
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return EngineOrderAsset
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
     * @return EngineOrderAsset
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
     * Set dropbox_link
     *
     * @param string $dropboxLink
     *
     * @return EngineOrderAsset
     */
    public function setDropboxLink($dropboxLink)
    {
        $this->dropbox_link = $dropboxLink;

        return $this;
    }

    /**
     * Get dropbox_link
     *
     * @return string
     */
    public function getDropboxLink()
    {
        return $this->dropbox_link;
    }

    /**
     * Set mime_type
     *
     * @param string $mimeType
     *
     * @return EngineOrderAsset
     */
    public function setMimeType($mimeType)
    {
        $this->mime_type = $mimeType;

        return $this;
    }

    /**
     * Get mime_type
     *
     * @return string
     */
    public function getMimeType()
    {
        return $this->mime_type;
    }

    /**
     * Set duration
     *
     * @param int $duration
     *
     * @return EngineOrderAsset
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
     * @return EngineOrderAsset
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
     * Set slug
     *
     * @param string $slug
     *
     * @return EngineOrderAsset
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
     * Set created_at
     *
     * @param \DateTime $createdAt
     *
     * @return EngineOrderAsset
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
     * @return EngineOrderAsset
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
     * @param \Vocalizr\AppBundle\Entity\UserInfo $userInfo
     *
     * @return EngineOrderAsset
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
     * Set engine_order
     *
     * @param \Vocalizr\AppBundle\Entity\EngineOrder $engineOrder
     *
     * @return EngineOrderAsset
     */
    public function setEngineOrder(\Vocalizr\AppBundle\Entity\EngineOrder $engineOrder = null)
    {
        $this->engine_order = $engineOrder;

        return $this;
    }

    /**
     * Get engine_order
     *
     * @return \Vocalizr\AppBundle\Entity\EngineOrder
     */
    public function getEngineOrder()
    {
        return $this->engine_order;
    }
}