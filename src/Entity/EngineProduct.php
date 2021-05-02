<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EngineProductRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="engine_product")
 */
class EngineProduct
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Column(type="string", length=200)
     */
    protected $code;

    /**
     * In cents
     *
     * @ORM\Column(type="integer", length=11)
     */
    protected $amount = 0;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $title;

    /**
     * @ORM\Column(type="text")
     */
    protected $description;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $active = 1;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $pro_only = 0;

    /**
     * @ORM\Column(type="integer", length=2)
     */
    protected $sort_order;

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
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set code
     *
     * @param string $code
     *
     * @return EngineProduct
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set amount
     *
     * @param int $amount
     *
     * @return EngineProduct
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return EngineProduct
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
     * @return EngineProduct
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
     * Set active
     *
     * @param bool $active
     *
     * @return EngineProduct
     */
    public function setActive($active)
    {
        $this->active = $active;

        return $this;
    }

    /**
     * Get active
     *
     * @return bool
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * Set sort_order
     *
     * @param int $sortOrder
     *
     * @return EngineProduct
     */
    public function setSortOrder($sortOrder)
    {
        $this->sort_order = $sortOrder;

        return $this;
    }

    /**
     * Get sort_order
     *
     * @return int
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
     * @return EngineProduct
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
     * @return EngineProduct
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
     * Set pro_only
     *
     * @param bool $proOnly
     *
     * @return EngineProduct
     */
    public function setProOnly($proOnly)
    {
        $this->pro_only = $proOnly;

        return $this;
    }

    /**
     * Get pro_only
     *
     * @return bool
     */
    public function getProOnly()
    {
        return $this->pro_only;
    }
}