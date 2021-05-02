<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserSubscriptionRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="user_subscription", indexes={@ORM\Index(name="pp_idx", columns={"user_info_id", "paypal_subscr_id"})})
 */
class UserSubscription
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo", inversedBy="user_subscriptions")
     */
    protected $user_info;

    /**
     * @ORM\ManyToOne(targetEntity="SubscriptionPlan")
     */
    protected $subscription_plan;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    protected $stripe_subscr_id = null;

    /**
     * @ORM\Column(type="string", length=15, nullable=true)
     */
    protected $paypal_subscr_id = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $date_commenced = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $date_ended = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $last_payment_date = null;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    protected $next_payment_date = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $cancel_date = null;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $is_active = true;

    /**
     * @var string|null
     *
     * @ORM\Column(name="paypal_account", type="string", options={"default":"payments@vocalizr.com"}, nullable=true )
     */
    protected $paypalAccount;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $source;

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->date_commenced = new \DateTime();
    }

    /**
     * Relationships
     */

    /**
     * @ORM\OneToMany(targetEntity="UserTransaction", mappedBy="user_subscription")
     */
    protected $user_transactions;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->paypalAccount = 'subscriptions@vocalizr.com';
        $this->user_transactions = new ArrayCollection();
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
     * Set date_commenced
     *
     * @param \DateTime $dateCommenced
     *
     * @return UserSubscription
     */
    public function setDateCommenced($dateCommenced)
    {
        $this->date_commenced = $dateCommenced;

        return $this;
    }

    /**
     * Get date_commenced
     *
     * @return \DateTime
     */
    public function getDateCommenced()
    {
        return $this->date_commenced;
    }

    /**
     * Set date_ended
     *
     * @param \DateTime $dateEnded
     *
     * @return UserSubscription
     */
    public function setDateEnded($dateEnded)
    {
        $this->date_ended = $dateEnded;

        return $this;
    }

    /**
     * Get date_ended
     *
     * @return \DateTime
     */
    public function getDateEnded()
    {
        return $this->date_ended;
    }

    /**
     * Set is_active
     *
     * @param bool $isActive
     *
     * @return UserSubscription
     */
    public function setIsActive($isActive)
    {
        $this->is_active = $isActive;

        return $this;
    }

    /**
     * Get is_active
     *
     * @return bool
     */
    public function getIsActive()
    {
        return $this->is_active;
    }

    /**
     * Set user_info
     *
     * @param UserInfo $userInfo
     *
     * @return UserSubscription
     */
    public function setUserInfo(UserInfo $userInfo = null)
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
     * Set subscription_plan
     *
     * @param SubscriptionPlan $subscriptionPlan
     *
     * @return UserSubscription
     */
    public function setSubscriptionPlan(SubscriptionPlan $subscriptionPlan = null)
    {
        $this->subscription_plan = $subscriptionPlan;

        return $this;
    }

    /**
     * Get subscription_plan
     *
     * @return SubscriptionPlan
     */
    public function getSubscriptionPlan()
    {
        return $this->subscription_plan;
    }

    /**
     * Set last_payment
     *
     * @param \DateTime $lastPayment
     *
     * @return UserSubscription
     */
    public function setLastPayment($lastPayment)
    {
        $this->last_payment_date = $lastPayment;

        return $this;
    }

    /**
     * Get last_payment
     *
     * @return \DateTime
     */
    public function getLastPayment()
    {
        return $this->last_payment_date;
    }

    /**
     * Add user_transactions
     *
     * @param UserPayment $userTransactions
     *
     * @return UserSubscription
     */
    public function addUserTransaction(UserPayment $userTransactions)
    {
        $this->user_transactions[] = $userTransactions;

        return $this;
    }

    /**
     * Remove user_transactions
     *
     * @param UserPayment $userTransactions
     */
    public function removeUserTransaction(UserPayment $userTransactions)
    {
        $this->user_transactions->removeElement($userTransactions);
    }

    /**
     * Get user_transactions
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserTransactions()
    {
        return $this->user_transactions;
    }

    /**
     * Set cancel_date
     *
     * @param \DateTime $cancelDate
     *
     * @return UserSubscription
     */
    public function setCancelDate($cancelDate)
    {
        $this->cancel_date = $cancelDate;

        return $this;
    }

    /**
     * Get cancel_date
     *
     * @return \DateTime
     */
    public function getCancelDate()
    {
        return $this->cancel_date;
    }

    /**
     * Set paypal_transaction
     *
     * @deprecated
     *
     * @param PayPalTransaction $paypalTransaction
     *
     * @return UserSubscription
     */
    public function setPaypalTransaction(PayPalTransaction $paypalTransaction = null)
    {
        $this->paypal_transaction = $paypalTransaction;

        return $this;
    }

    /**
     * Get paypal_transaction
     *
     * @deprecated
     *
     * @return PayPalTransaction
     */
    public function getPaypalTransaction()
    {
        return $this->paypal_transaction;
    }

    /**
     * Set paypal_subscr_id
     *
     * @param string $paypalSubscrId
     *
     * @return UserSubscription
     */
    public function setPaypalSubscrId($paypalSubscrId)
    {
        $this->paypal_subscr_id = $paypalSubscrId;

        return $this;
    }

    /**
     * Get paypal_subscr_id
     *
     * @return string
     */
    public function getPaypalSubscrId()
    {
        return $this->paypal_subscr_id;
    }

    /**
     * Set last_payment_date
     *
     * @param \DateTime $lastPaymentDate
     *
     * @return UserSubscription
     */
    public function setLastPaymentDate($lastPaymentDate)
    {
        $this->last_payment_date = $lastPaymentDate;

        return $this;
    }

    /**
     * Get last_payment_date
     *
     * @return \DateTime
     */
    public function getLastPaymentDate()
    {
        return $this->last_payment_date;
    }

    /**
     * Set next_payment_date
     *
     * @param \DateTime $nextPaymentDate
     *
     * @return UserSubscription
     */
    public function setNextPaymentDate($nextPaymentDate)
    {
        $this->next_payment_date = $nextPaymentDate;

        return $this;
    }

    /**
     * Get next_payment_date
     *
     * @return \DateTime
     */
    public function getNextPaymentDate()
    {
        return $this->next_payment_date;
    }

    /**
     * @deprecated use UserSubscription::getUserInfo::getStripeCustId()
     *
     * Get stripe_cust_id
     *
     * @return string
     */
    public function getStripeCustId()
    {
        return $this->getUserInfo() ? $this->getUserInfo()->getStripeCustId() : null;
    }

    /**
     * Set stripe_subscr_id
     *
     * @param string $stripeSubscrId
     *
     * @return UserSubscription
     */
    public function setStripeSubscrId($stripeSubscrId)
    {
        $this->stripe_subscr_id = $stripeSubscrId;

        return $this;
    }

    /**
     * Get stripe_subscr_id
     *
     * @return string
     */
    public function getStripeSubscrId()
    {
        return $this->stripe_subscr_id;
    }

    /**
     * @return string|null
     */
    public function getPaypalAccount()
    {
        return $this->paypalAccount;
    }

    /**
     * @param string|null $paypalAccount
     * @return UserSubscription
     */
    public function setPaypalAccount($paypalAccount)
    {
        $this->paypalAccount = $paypalAccount;
        return $this;
    }



    /**
     * @return string|null
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param string|null $source
     *
     * @return UserSubscription
     */
    public function setSource($source)
    {
        $this->source = $source;
        return $this;
    }
}