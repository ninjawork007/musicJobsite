<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ArticleCategoryRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="article_category")
 */
class ArticleCategory
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    public $title;

    /**
     * @ORM\Column(type="string", length=255)
     */
    public $slug;

    /**
     * @ORM\Column(type="integer", length=3)
     */
    public $sort_order = 0;

    /**
     * @ORM\Column(type="boolean")
     */
    public $display = 1;

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

    public function __toString()
    {
        return $this->title;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return ArticleCategory
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
     * @return ArticleCategory
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
     * Set sort_order
     *
     * @param int $sortOrder
     *
     * @return ArticleCategory
     */
    public function setSortOrder($sortOrder)
    {
        $this->sort_order = $sortOrder;

        return $this;
    }

    /**
     * Get sort_order
     *
     * @return \intger
     */
    public function getSortOrder()
    {
        return $this->sort_order;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     *
     * @return ArticleCategory
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
     * Set display
     *
     * @param bool $display
     *
     * @return ArticleCategory
     */
    public function setDisplay($display)
    {
        $this->display = $display;

        return $this;
    }

    /**
     * Get display
     *
     * @return bool
     */
    public function getDisplay()
    {
        return $this->display;
    }
}