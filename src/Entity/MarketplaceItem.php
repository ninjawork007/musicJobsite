<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MarketplaceItemRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="marketplace_item", indexes={
 *          @ORM\Index(name="marketplace_item_title_idx", columns={"title"}),
 *          @ORM\Index(name="marketplace_item_published_at_idx", columns={"published_at"})
 * })
 */
class MarketplaceItem
{
    const STATUS_DRAFT = 'draft';

    const STATUS_PUBLISHED = 'published';

    const STATUS_REVIEW = 'review';

    const STATUS_REJECTED = 'rejected';

    const STATUS_SOLD = 'sold';

    const ITEM_TYPE_VOCAL = 'vocal';

    const ITEM_TYPE_MUSIC = 'music';

    const ITEM_TYPE_SONG = 'song';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    protected $uuid;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo", inversedBy="marketplace_items")
     */
    protected $user_info;

    /**
     * @Assert\NotBlank(message="Required")
     * @ORM\Column(type="string", length=64)
     */
    protected $title;

    /**
     * @Assert\NotBlank(message="Required")
     * @ORM\Column(type="string", length=16)
     */
    protected $status = MarketplaceItem::STATUS_DRAFT;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $status_reason;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $additional_info = null;

    /**
     * @Assert\NotBlank(message="Required")
     * @ORM\Column(type="string", length=16)
     */
    protected $item_type = MarketplaceItem::ITEM_TYPE_VOCAL;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $bpm = null;

    /**
     * @Assert\NotBlank(message="Required")
     * @ORM\Column(type="string", length=32, nullable=false)
     */
    protected $audio_key = null;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $is_auction = false;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $has_assets = false;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    protected $bids_due = null;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $num_bids = 0;

    /**
     * @Assert\Range(
     *      min = 1,
     *      minMessage = "You must set a Sale Price"
     * )
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $buyout_price = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $reserve_price = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $royalty_master = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $royalty_publishing = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $royalty_mechanical = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $royalty_performance = 0;

    /**
     * @ORM\Column(type="string", length=6, nullable=true)
     */
    protected $gender = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $published_at = null;

    /**
     * Whether or not the gig has been approved
     *
     * @ORM\Column(type="boolean")
     */
    protected $approved = false;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo")
     */
    protected $approved_by;

    /**
     * The date and time the item was approved
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $approved_at = null;

    /**
     * The date and time the item was updated
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $updated_at = null;

    /**
     * The date and time the item was created
     *
     * @ORM\Column(type="datetime")
     */
    protected $created_at = null;

    /**
     * @Assert\Count(
     *      min = "1",
     *      max = "5",
     *      minMessage = "Required",
     *      maxMessage = "You cannot specify more than {{ limit }} genres|You cannot specify more than {{ limit }} genres"
     * )
     *
     * @ORM\ManyToMany(targetEntity="Genre")
     * @ORM\JoinTable(name="marketplace_item_genre",
     *      joinColumns={@ORM\JoinColumn(name="marketplace_item_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="genre_id", referencedColumnName="id")}
     *      )
     */
    protected $genres;

    /**
     * Marketplace Item assets
     *
     * @ORM\OneToMany(targetEntity="MarketplaceItemAsset", mappedBy="marketplace_item")
     */
    protected $marketplace_item_assets;

    /**
     * Marketplace Item audio
     *
     * @ORM\OneToMany(targetEntity="MarketplaceItemAudio", mappedBy="marketplace_item")
     */
    protected $item_audio;

    /**
     * Marketplace Item assets
     *
     * @ORM\OneToMany(targetEntity="MarketplaceItemAsset", mappedBy="marketplace_item")
     */
    protected $item_assets;

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

    public function getTypeName()
    {
        $names = [
            MarketplaceItem::ITEM_TYPE_VOCAL => 'Vocal (Acapella)',
            MarketplaceItem::ITEM_TYPE_MUSIC => 'Music (Full backing track)',
            MarketplaceItem::ITEM_TYPE_SONG  => 'Song (Lyrics and melody)',
        ];

        return $names[$this->item_type];
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->genres = new \Doctrine\Common\Collections\ArrayCollection();
        $this->uuid   = uniqid();
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
     * Set uuid
     *
     * @param string $uuid
     *
     * @return MarketplaceItem
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * Get uuid
     *
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return MarketplaceItem
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
     * Set additional_info
     *
     * @param string $additionalInfo
     *
     * @return MarketplaceItem
     */
    public function setAdditionalInfo($additionalInfo)
    {
        $this->additional_info = $additionalInfo;

        return $this;
    }

    /**
     * Get additional_info
     *
     * @return string
     */
    public function getAdditionalInfo()
    {
        return $this->additional_info;
    }

    /**
     * Set item_type
     *
     * @param string $itemType
     *
     * @return MarketplaceItem
     */
    public function setItemType($itemType)
    {
        $this->item_type = $itemType;

        return $this;
    }

    /**
     * Get item_type
     *
     * @return string
     */
    public function getItemType()
    {
        return $this->item_type;
    }

    /**
     * Set bpm
     *
     * @param int $bpm
     *
     * @return MarketplaceItem
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
     * Set key
     *
     * @param string $key
     *
     * @return MarketplaceItem
     */
    public function setKey($key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Get key
     *
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set is_auction
     *
     * @param bool $isAuction
     *
     * @return MarketplaceItem
     */
    public function setIsAuction($isAuction)
    {
        $this->is_auction = $isAuction;

        return $this;
    }

    /**
     * Get is_auction
     *
     * @return bool
     */
    public function getIsAuction()
    {
        return $this->is_auction;
    }

    /**
     * Set bids_due
     *
     * @param \DateTime $bidsDue
     *
     * @return MarketplaceItem
     */
    public function setBidsDue($bidsDue)
    {
        $this->bids_due = $bidsDue;

        return $this;
    }

    /**
     * Get bids_due
     *
     * @return \DateTime
     */
    public function getBidsDue()
    {
        return $this->bids_due;
    }

    /**
     * Set num_bids
     *
     * @param int $numBids
     *
     * @return MarketplaceItem
     */
    public function setNumBids($numBids)
    {
        $this->num_bids = $numBids;

        return $this;
    }

    /**
     * Get num_bids
     *
     * @return int
     */
    public function getNumBids()
    {
        return $this->num_bids;
    }

    /**
     * Set buyout_price
     *
     * @param int $buyoutPrice
     *
     * @return MarketplaceItem
     */
    public function setBuyoutPrice($buyoutPrice)
    {
        $this->buyout_price = $buyoutPrice;

        return $this;
    }

    /**
     * Get buyout_price
     *
     * @return int
     */
    public function getBuyoutPrice()
    {
        return $this->buyout_price;
    }

    /**
     * Set reserve_price
     *
     * @param int $reservePrice
     *
     * @return MarketplaceItem
     */
    public function setReservePrice($reservePrice)
    {
        $this->reserve_price = $reservePrice;

        return $this;
    }

    /**
     * Get reserve_price
     *
     * @return int
     */
    public function getReservePrice()
    {
        return $this->reserve_price;
    }

    /**
     * Set royalty_master
     *
     * @param int $royaltyMaster
     *
     * @return MarketplaceItem
     */
    public function setRoyaltyMaster($royaltyMaster)
    {
        $this->royalty_master = $royaltyMaster;

        return $this;
    }

    /**
     * Get royalty_master
     *
     * @return int
     */
    public function getRoyaltyMaster()
    {
        return $this->royalty_master;
    }

    /**
     * Set royalty_publishing
     *
     * @param int $royaltyPublishing
     *
     * @return MarketplaceItem
     */
    public function setRoyaltyPublishing($royaltyPublishing)
    {
        $this->royalty_publishing = $royaltyPublishing;

        return $this;
    }

    /**
     * Get royalty_publishing
     *
     * @return int
     */
    public function getRoyaltyPublishing()
    {
        return $this->royalty_publishing;
    }

    /**
     * Set royalty_mechanical
     *
     * @param int $royaltyMechanical
     *
     * @return MarketplaceItem
     */
    public function setRoyaltyMechanical($royaltyMechanical)
    {
        $this->royalty_mechanical = $royaltyMechanical;

        return $this;
    }

    /**
     * Get royalty_mechanical
     *
     * @return int
     */
    public function getRoyaltyMechanical()
    {
        return $this->royalty_mechanical;
    }

    /**
     * Set royalty_performance
     *
     * @param int $royaltyPerformance
     *
     * @return MarketplaceItem
     */
    public function setRoyaltyPerformance($royaltyPerformance)
    {
        $this->royalty_performance = $royaltyPerformance;

        return $this;
    }

    /**
     * Get royalty_performance
     *
     * @return int
     */
    public function getRoyaltyPerformance()
    {
        return $this->royalty_performance;
    }

    /**
     * Set gender
     *
     * @param string $gender
     *
     * @return MarketplaceItem
     */
    public function setGender($gender)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * Get gender
     *
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * Set published_at
     *
     * @param \DateTime $publishedAt
     *
     * @return MarketplaceItem
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
     * Set approved
     *
     * @param bool $approved
     *
     * @return MarketplaceItem
     */
    public function setApproved($approved)
    {
        $this->approved = $approved;

        return $this;
    }

    /**
     * Get approved
     *
     * @return bool
     */
    public function getApproved()
    {
        return $this->approved;
    }

    /**
     * Set approved_at
     *
     * @param \DateTime $approvedAt
     *
     * @return MarketplaceItem
     */
    public function setApprovedAt($approvedAt)
    {
        $this->approved_at = $approvedAt;

        return $this;
    }

    /**
     * Get approved_at
     *
     * @return \DateTime
     */
    public function getApprovedAt()
    {
        return $this->approved_at;
    }

    /**
     * Set user_info
     *
     * @param \App\Entity\UserInfo $userInfo
     *
     * @return MarketplaceItem
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
     * Set approved_by
     *
     * @param \App\Entity\UserInfo $approvedBy
     *
     * @return MarketplaceItem
     */
    public function setApprovedBy(\App\Entity\UserInfo $approvedBy = null)
    {
        $this->approved_by = $approvedBy;

        return $this;
    }

    /**
     * Get approved_by
     *
     * @return \App\Entity\UserInfo
     */
    public function getApprovedBy()
    {
        return $this->approved_by;
    }

    /**
     * Add genres
     *
     * @param \App\Entity\Genre $genres
     *
     * @return MarketplaceItem
     */
    public function addGenre(\App\Entity\Genre $genres)
    {
        $this->genres[] = $genres;

        return $this;
    }

    /**
     * Remove genres
     *
     * @param \App\Entity\Genre $genres
     */
    public function removeGenre(\App\Entity\Genre $genres)
    {
        $this->genres->removeElement($genres);
    }

    /**
     * Get genres
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGenres()
    {
        return $this->genres;
    }

    /**
     * Set audio_key
     *
     * @param string $audioKey
     *
     * @return MarketplaceItem
     */
    public function setAudioKey($audioKey)
    {
        $this->audio_key = $audioKey;

        return $this;
    }

    /**
     * Get audio_key
     *
     * @return string
     */
    public function getAudioKey()
    {
        return $this->audio_key;
    }

    /**
     * Set has_assets
     *
     * @param bool $hasAssets
     *
     * @return MarketplaceItem
     */
    public function setHasAssets($hasAssets)
    {
        $this->has_assets = $hasAssets;

        return $this;
    }

    /**
     * Get has_assets
     *
     * @return bool
     */
    public function getHasAssets()
    {
        return $this->has_assets;
    }

    /**
     * Add marketplace_item_assets
     *
     * @param \App\Entity\MarketplaceItemAsset $marketplaceItemAssets
     *
     * @return MarketplaceItem
     */
    public function addMarketplaceItemAsset(\App\Entity\MarketplaceItemAsset $marketplaceItemAssets)
    {
        $this->marketplace_item_assets[] = $marketplaceItemAssets;

        return $this;
    }

    /**
     * Remove marketplace_item_assets
     *
     * @param \App\Entity\MarketplaceItemAsset $marketplaceItemAssets
     */
    public function removeMarketplaceItemAsset(\App\Entity\MarketplaceItemAsset $marketplaceItemAssets)
    {
        $this->marketplace_item_assets->removeElement($marketplaceItemAssets);
    }

    /**
     * Get marketplace_item_assets
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMarketplaceItemAssets()
    {
        return $this->marketplace_item_assets;
    }

    /**
     * Set status
     *
     * @param string $status
     *
     * @return MarketplaceItem
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
     * Add item_audio
     *
     * @param \App\Entity\MarketplaceItemAudio $itemAudio
     *
     * @return MarketplaceItem
     */
    public function addItemAudio(\App\Entity\MarketplaceItemAudio $itemAudio)
    {
        $this->item_audio[] = $itemAudio;

        return $this;
    }

    /**
     * Remove item_audio
     *
     * @param \App\Entity\MarketplaceItemAudio $itemAudio
     */
    public function removeItemAudio(\App\Entity\MarketplaceItemAudio $itemAudio)
    {
        $this->item_audio->removeElement($itemAudio);
    }

    /**
     * Get item_audio
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getItemAudio()
    {
        return $this->item_audio;
    }

    /**
     * Add item_assets
     *
     * @param \App\Entity\MarketplaceItemAsset $itemAssets
     *
     * @return MarketplaceItem
     */
    public function addItemAsset(\App\Entity\MarketplaceItemAsset $itemAssets)
    {
        $this->item_assets[] = $itemAssets;

        return $this;
    }

    /**
     * Remove item_assets
     *
     * @param \App\Entity\MarketplaceItemAsset $itemAssets
     */
    public function removeItemAsset(\App\Entity\MarketplaceItemAsset $itemAssets)
    {
        $this->item_assets->removeElement($itemAssets);
    }

    /**
     * Get item_assets
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getItemAssets()
    {
        return $this->item_assets;
    }

    /**
     * Set updated_at
     *
     * @param \DateTime $updatedAt
     *
     * @return MarketplaceItem
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
     * @return MarketplaceItem
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
     * Set status_reason
     *
     * @param string $statusReason
     *
     * @return MarketplaceItem
     */
    public function setStatusReason($statusReason)
    {
        $this->status_reason = $statusReason;

        return $this;
    }

    /**
     * Get status_reason
     *
     * @return string
     */
    public function getStatusReason()
    {
        return $this->status_reason;
    }
}