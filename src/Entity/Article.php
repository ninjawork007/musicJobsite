<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ArticleRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="article")
 * @UniqueEntity("slug")
 */
class Article
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="Required")
     */
    protected $title;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="Required")
     */
    protected $slug;

    /**
     * @ORM\Column(type="text")
     * @Assert\NotBlank(message="Required")
     */
    protected $short_desc;

    /**
     * @ORM\Column(type="text")
     * @Assert\NotBlank(message="Required")
     */
    protected $content;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $seo_title;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $seo_desc;

    /**
     * @ORM\ManyToOne(targetEntity="ArticleCategory")
     * @Assert\NotBlank(message="Required")
     */
    protected $article_category;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo")
     */
    protected $spotlight_user;

    /**
     * @ORM\ManyToOne(targetEntity="Author")
     */
    protected $author = null;

    /**
     * @ORM\Column(type="integer", length=11)
     */
    protected $read_count = 0;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    public $path;

    /**
     * @Assert\File(maxSize="10000000")
     */
    public $file;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $published_at;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $updated_at;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $created_at;

    /**
     * Artcile Images
     *
     * @ORM\OneToMany(targetEntity="ArticleImage", mappedBy="article")
     */
    protected $images;

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

    public function getFile()
    {
        return $this->file;
    }

    public function getAbsolutePath()
    {
        return null === $this->path
            ? null
            : $this->getUploadRootDir() . $this->path;
    }

    public function getWebPath()
    {
        return null === $this->path
            ? null
            : $this->getUploadDir() . $this->path;
    }

    public function getWebPathSmall()
    {
        return null === $this->path
            ? null
            : $this->getUploadDir() . 'small/' . $this->path;
    }

    public function getUploadRootDir()
    {
        // the absolute directory path where uploaded
        // documents should be saved
        return __DIR__ . '/../../public/' . $this->getUploadDir();
    }

    public function getUploadDir()
    {
        // get rid of the __DIR__ so it doesn't screw up
        // when displaying uploaded doc/image in the view.
        return 'uploads/article/';
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
     * Constructor
     */
    public function __construct()
    {
        $this->images = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return Article
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
     * Set slug
     *
     * @param string $slug
     *
     * @return Article
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
     * Set content
     *
     * @param string $content
     *
     * @return Article
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set seo_title
     *
     * @param string $seoTitle
     *
     * @return Article
     */
    public function setSeoTitle($seoTitle)
    {
        $this->seo_title = $seoTitle;

        return $this;
    }

    /**
     * Get seo_title
     *
     * @return string
     */
    public function getSeoTitle()
    {
        return $this->seo_title;
    }

    /**
     * Set seo_desc
     *
     * @param string $seoDesc
     *
     * @return Article
     */
    public function setSeoDesc($seoDesc)
    {
        $this->seo_desc = $seoDesc;

        return $this;
    }

    /**
     * Get seo_desc
     *
     * @return string
     */
    public function getSeoDesc()
    {
        return $this->seo_desc;
    }

    /**
     * Set status
     *
     * @param bool $status
     *
     * @return Article
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return bool
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set read_count
     *
     * @param int $readCount
     *
     * @return Article
     */
    public function setReadCount($readCount)
    {
        $this->read_count = $readCount;

        return $this;
    }

    /**
     * Get read_count
     *
     * @return int
     */
    public function getReadCount()
    {
        return $this->read_count;
    }

    /**
     * Set path
     *
     * @param string $path
     *
     * @return Article
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
     * Set updated_at
     *
     * @param \DateTime $updatedAt
     *
     * @return Article
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
     * Set created_at
     *
     * @param \DateTime $createdAt
     *
     * @return Article
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
     * Set article_category
     *
     * @param \App\Entity\ArticleCategory $articleCategory
     *
     * @return Article
     */
    public function setArticleCategory(\App\Entity\ArticleCategory $articleCategory = null)
    {
        $this->article_category = $articleCategory;

        return $this;
    }

    /**
     * Get article_category
     *
     * @return \App\Entity\ArticleCategory
     */
    public function getArticleCategory()
    {
        return $this->article_category;
    }

    /**
     * Set spotlight_user
     *
     * @param \App\Entity\UserInfo $spotlightUser
     *
     * @return Article
     */
    public function setSpotlightUser(\App\Entity\UserInfo $spotlightUser = null)
    {
        $this->spotlight_user = $spotlightUser;

        return $this;
    }

    /**
     * Get spotlight_user
     *
     * @return \App\Entity\UserInfo
     */
    public function getSpotlightUser()
    {
        return $this->spotlight_user;
    }

    /**
     * Add images
     *
     * @param \App\Entity\ArticleImage $images
     *
     * @return Article
     */
    public function addImage(\App\Entity\ArticleImage $images)
    {
        $this->images[] = $images;

        return $this;
    }

    /**
     * Remove images
     *
     * @param \App\Entity\ArticleImage $images
     */
    public function removeImage(\App\Entity\ArticleImage $images)
    {
        $this->images->removeElement($images);
    }

    /**
     * Get images
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * Set published_at
     *
     * @param \DateTime $publishedAt
     *
     * @return Article
     */
    public function setPublishedAt($publishedAt)
    {
        $this->published_at = $publishedAt;

        return $this;
    }

    /**
     * Get published_at
     *
     * @return \DateTime
     */
    public function getPublishedAt()
    {
        return $this->published_at;
    }

    /**
     * Set short_desc
     *
     * @param string $shortDesc
     *
     * @return Article
     */
    public function setShortDesc($shortDesc)
    {
        $this->short_desc = $shortDesc;

        return $this;
    }

    /**
     * Get short_desc
     *
     * @return string
     */
    public function getShortDesc()
    {
        return $this->short_desc;
    }

    /**
     * Set author
     *
     * @param \App\Entity\Author $author
     *
     * @return Article
     */
    public function setAuthor(\App\Entity\Author $author = null)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * Get author
     *
     * @return \App\Entity\Author
     */
    public function getAuthor()
    {
        return $this->author;
    }
}