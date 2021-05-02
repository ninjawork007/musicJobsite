<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\SubscriptionPlanRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="subscription_plan")
 */
class SubscriptionPlan
{
    const PLAN_FREE = 'FREE';

    const PLAN_PRO = 'PRO';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Column(type="string", length=32)
     */
    protected $title;

    /**
     * @ORM\Column(type="text")
     */
    protected $description;

    /**
     * @ORM\Column(type="integer", length=9)
     */
    protected $price;

    /**
     * Limit of audio files assigned to profile
     *
     * @ORM\Column(type="integer", length=2, nullable=true)
     */
    protected $user_audio_limit = null;

    /**
     * Percent of project amount on which project will cost bigger for project owner.
     *
     * @ORM\Column(type="integer", length=9)
     */
    protected $project_percent_added = null;

    /**
     * Percent of project amount which will be restricted from bidder right after the project will be competed.
     *
     * @ORM\Column(type="integer", length=9)
     */
    protected $payment_percent_taken = null;

    /**
     * @ORM\Column(type="integer", length=9)
     */
    protected $project_private_fee = 5;

    /**
     * @ORM\Column(type="integer", length=9)
     */
    protected $project_highlight_fee = 10;

    /**
     * @ORM\Column(type="integer", length=9)
     */
    protected $project_feature_fee = 10;

    /**
     * @ORM\Column(type="integer", length=9)
     */
    protected $project_announce_fee = 10;

    //TODO: write migration for prices
    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", length=9, nullable=true)
     */
    protected $project_restrict_fee = 5;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", length=9, nullable=true)
     */
    protected $project_favorites_fee = 5;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", length=9, nullable=true)
     */
    protected $project_messaging_fee;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", length=9, nullable=true)
     */
    protected $project_lock_to_cert_fee = 9;

    /**
     * Connect limit
     *
     * @ORM\Column(type="integer", length=3, options={"default":5})
     */
    protected $connect_month_limit = 5;

    /**
     * Message limit
     *
     * @ORM\Column(type="integer", length=3, options={"default":5}, nullable=true)
     */
    protected $message_month_limit = 5;

    /**
     * @ORM\Column(type="string", length=32)
     */
    protected $static_key = null;

    /**
     * @ORM\Column(type="string", length=32)
     */
    protected $unique_key = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $updated_at = null;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $hidden = false;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $created_at;

    /**
     * Relationships
     */

    /**
     * @ORM\OneToMany(targetEntity="UserSubscription", mappedBy="subscription_plan")
     */
    protected $user_subscription;

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->created_at = new \DateTime();
        $this->unique_key = md5(time() + rand(9, 999999));

        $this->connect_daily_limit = 3;
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
     * Set title
     *
     * @param string $title
     *
     * @return SubscriptionPlan
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
     * Set content
     *
     * @param string $content
     *
     * @return SubscriptionPlan
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
     * Set price
     *
     * @param int $price
     *
     * @return SubscriptionPlan
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return int
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Get price
     *
     * @return float
     */
    public function getFloatPrice()
    {
        return (float) $this->price / 100;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->user_subscription = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return SubscriptionPlan
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
     * Set user_audio_limit
     *
     * @param int $userAudioLimit
     *
     * @return SubscriptionPlan
     */
    public function setUserAudioLimit($userAudioLimit)
    {
        $this->user_audio_limit = $userAudioLimit;

        return $this;
    }

    /**
     * Get user_audio_limit
     *
     * @return int
     */
    public function getUserAudioLimit()
    {
        return $this->user_audio_limit;
    }

    /**
     * Set payment_percent_taken
     *
     * @param int $paymentPercentTaken
     *
     * @return SubscriptionPlan
     */
    public function setPaymentPercentTaken($paymentPercentTaken)
    {
        $this->payment_percent_taken = $paymentPercentTaken;

        return $this;
    }

    /**
     * Get payment_percent_taken
     *
     * @return int
     */
    public function getPaymentPercentTaken()
    {
        return $this->payment_percent_taken;
    }

    /**
     * Set static_key
     *
     * @param string $staticKey
     *
     * @return SubscriptionPlan
     */
    public function setStaticKey($staticKey)
    {
        $this->static_key = $staticKey;

        return $this;
    }

    /**
     * Get static_key
     *
     * @return string
     */
    public function getStaticKey()
    {
        return $this->static_key;
    }

    /**
     * Add user_subscription
     *
     * @param \App\Entity\UserSubscription $userSubscription
     *
     * @return SubscriptionPlan
     */
    public function addUserSubscription(\App\Entity\UserSubscription $userSubscription)
    {
        $this->user_subscription[] = $userSubscription;

        return $this;
    }

    /**
     * Remove user_subscription
     *
     * @param \App\Entity\UserSubscription $userSubscription
     */
    public function removeUserSubscription(\App\Entity\UserSubscription $userSubscription)
    {
        $this->user_subscription->removeElement($userSubscription);
    }

    /**
     * Get user_subscription
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserSubscription()
    {
        return $this->user_subscription;
    }

    /**
     * Set unique_key
     *
     * @param string $uniqueKey
     *
     * @return SubscriptionPlan
     */
    public function setUniqueKey($uniqueKey)
    {
        $this->unique_key = $uniqueKey;

        return $this;
    }

    /**
     * Get unique_key
     *
     * @return string
     */
    public function getUniqueKey()
    {
        return $this->unique_key;
    }

    /**
     * Set updated_at
     *
     * @param \DateTime $updatedAt
     *
     * @return SubscriptionPlan
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
     * @return SubscriptionPlan
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
     * Set hidden
     *
     * @param bool $hidden
     *
     * @return SubscriptionPlan
     */
    public function setHidden($hidden)
    {
        $this->hidden = $hidden;

        return $this;
    }

    /**
     * Get hidden
     *
     * @return bool
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * Set project_percent_added
     *
     * @param int $projectPercentAdded
     *
     * @return SubscriptionPlan
     */
    public function setProjectPercentAdded($projectPercentAdded)
    {
        $this->project_percent_added = $projectPercentAdded;

        return $this;
    }

    /**
     * Get project_percent_added
     *
     * @return int
     */
    public function getProjectPercentAdded()
    {
        return $this->project_percent_added;
    }

    /**
     * Set project_private_fee
     *
     * @param int $projectPrivateFee
     *
     * @return SubscriptionPlan
     */
    public function setProjectPrivateFee($projectPrivateFee)
    {
        $this->project_private_fee = $projectPrivateFee;

        return $this;
    }

    /**
     * Get project_private_fee
     *
     * @return int
     */
    public function getProjectPrivateFee()
    {
        return $this->project_private_fee;
    }

    /**
     * Set project_highlight_fee
     *
     * @param int $projectHighlightFee
     *
     * @return SubscriptionPlan
     */
    public function setProjectHighlightFee($projectHighlightFee)
    {
        $this->project_highlight_fee = $projectHighlightFee;

        return $this;
    }

    /**
     * Get project_highlight_fee
     *
     * @return int
     */
    public function getProjectHighlightFee()
    {
        return $this->project_highlight_fee;
    }

    /**
     * Set project_feature_fee
     *
     * @param int $projectFeatureFee
     *
     * @return SubscriptionPlan
     */
    public function setProjectFeatureFee($projectFeatureFee)
    {
        $this->project_feature_fee = $projectFeatureFee;

        return $this;
    }

    /**
     * Get project_feature_fee
     *
     * @return int
     */
    public function getProjectFeatureFee()
    {
        return $this->project_feature_fee;
    }

    /**
     * Set project_announce_fee
     *
     * @param int $projectAnnounceFee
     *
     * @return SubscriptionPlan
     */
    public function setProjectAnnounceFee($projectAnnounceFee)
    {
        $this->project_announce_fee = $projectAnnounceFee;

        return $this;
    }

    /**
     * Get project_announce_fee
     *
     * @return int
     */
    public function getProjectAnnounceFee()
    {
        return $this->project_announce_fee;
    }

    /**
     * @return int|null
     */
    public function getProjectRestrictFee()
    {
        return $this->project_restrict_fee;
    }

    /**
     * @param int|null $project_restrict_fee
     * @return SubscriptionPlan
     */
    public function setProjectRestrictFee($project_restrict_fee)
    {
        $this->project_restrict_fee = $project_restrict_fee;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getProjectFavoritesFee()
    {
        return $this->project_favorites_fee;
    }

    /**
     * @param int|null $project_favorites_fee
     * @return SubscriptionPlan
     */
    public function setProjectFavoritesFee($project_favorites_fee)
    {
        $this->project_favorites_fee = $project_favorites_fee;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getProjectMessagingFee()
    {
        return $this->project_messaging_fee;
    }

    /**
     * @param int|null $project_messaging_fee
     * @return SubscriptionPlan
     */
    public function setProjectMessagingFee($project_messaging_fee)
    {
        $this->project_messaging_fee = $project_messaging_fee;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getProjectLockToCertFee()
    {
        return $this->project_lock_to_cert_fee;
    }

    /**
     * @param int|null $project_lock_to_cert_fee
     * @return SubscriptionPlan
     */
    public function setProjectLockToCertFee($project_lock_to_cert_fee)
    {
        $this->project_lock_to_cert_fee = $project_lock_to_cert_fee;
        return $this;
    }

    /**
     * Set message_month_limit
     *
     * @param int $messageMonthLimit
     *
     * @return SubscriptionPlan
     */
    public function setMessageMonthLimit($messageMonthLimit)
    {
        $this->message_month_limit = $messageMonthLimit;

        return $this;
    }

    /**
     * Get message_month_limit
     *
     * @return int
     */
    public function getMessageMonthLimit()
    {
        return $this->message_month_limit;
    }

    /**
     * Set connect_month_limit
     *
     * @param int $connectMonthLimit
     *
     * @return SubscriptionPlan
     */
    public function setConnectMonthLimit($connectMonthLimit)
    {
        $this->connect_month_limit = $connectMonthLimit;

        return $this;
    }

    /**
     * Get connect_month_limit
     *
     * @return int
     */
    public function getConnectMonthLimit()
    {
        return $this->connect_month_limit;
    }
}