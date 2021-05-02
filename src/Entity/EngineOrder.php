<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EngineOrderRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="engine_order")
 */
class EngineOrder
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * Uid
     *
     * @ORM\Column(type="string", length=255)
     */
    protected $uid = null;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="Required")
     */
    protected $title = null;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo")
     */
    protected $user_info = null;

    /**
     * Contact email
     *
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="Required")
     */
    protected $email = null;

    /**
     * @ORM\ManyToOne(targetEntity="EngineProduct")
     */
    protected $engine_product = null;

    /**
     * In cents
     *
     * @ORM\Column(type="integer", length=11)
     */
    protected $amount = 0;

    /**
     * In cents
     *
     * @ORM\Column(type="integer", length=11)
     */
    protected $fee = 0;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $notes = null;

    /**
     * @ORM\Column(type="string")
     */
    protected $status;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $created_at;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $updated_at;

    /**
     * Assets
     *
     * @ORM\OneToMany(targetEntity="EngineOrderAsset", mappedBy="engine_order")
     */
    protected $assets;

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
     * Constructor
     */
    public function __construct()
    {
        $this->uid    = uniqid();
        $this->assets = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set product_code
     *
     * @param string $productCode
     *
     * @return EngineOrder
     */
    public function setProductCode($productCode)
    {
        $this->product_code = $productCode;

        return $this;
    }

    /**
     * Get product_code
     *
     * @return string
     */
    public function getProductCode()
    {
        return $this->product_code;
    }

    /**
     * Set amount
     *
     * @param int $amount
     *
     * @return EngineOrder
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
     * Set fee
     *
     * @param int $fee
     *
     * @return EngineOrder
     */
    public function setFee($fee)
    {
        $this->fee = $fee;

        return $this;
    }

    /**
     * Get fee
     *
     * @return int
     */
    public function getFee()
    {
        return $this->fee;
    }

    /**
     * Set notes
     *
     * @param string $notes
     *
     * @return EngineOrder
     */
    public function setNotes($notes)
    {
        $this->notes = $notes;

        return $this;
    }

    /**
     * Get notes
     *
     * @return string
     */
    public function getNotes()
    {
        return $this->notes;
    }

    /**
     * Set status
     *
     * @param string $status
     *
     * @return EngineOrder
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     *
     * @return EngineOrder
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
     * @return EngineOrder
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
     * Add assets
     *
     * @param \App\Entity\EngineOrderAsset $assets
     *
     * @return EngineOrder
     */
    public function addAsset(\App\Entity\EngineOrderAsset $assets)
    {
        $this->assets[] = $assets;

        return $this;
    }

    /**
     * Remove assets
     *
     * @param \App\Entity\EngineOrderAsset $assets
     */
    public function removeAsset(\App\Entity\EngineOrderAsset $assets)
    {
        $this->assets->removeElement($assets);
    }

    /**
     * Get assets
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getAssets()
    {
        return $this->assets;
    }

    /**
     * Set updated_at
     *
     * @param \DateTime $updatedAt
     *
     * @return EngineOrder
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
     * Set engine_product
     *
     * @param \App\Entity\EngineProduct $engineProduct
     *
     * @return EngineOrder
     */
    public function setEngineProduct(\App\Entity\EngineProduct $engineProduct = null)
    {
        $this->engine_product = $engineProduct;

        return $this;
    }

    /**
     * Get engine_product
     *
     * @return \App\Entity\EngineProduct
     */
    public function getEngineProduct()
    {
        return $this->engine_product;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return EngineOrder
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set uid
     *
     * @param string $uid
     *
     * @return EngineOrder
     */
    public function setUid($uid)
    {
        $this->uid = $uid;

        return $this;
    }

    /**
     * Get uid
     *
     * @return string
     */
    public function getUid()
    {
        return $this->uid;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return EngineOrder
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
}