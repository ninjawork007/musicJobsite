<?php

namespace Vocalizr\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Vocalizr\AppBundle\Repository\UserPaymentRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="user_payment")
 */
class UserPayment
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
     * @ORM\OneToOne(targetEntity="UserSubscription", inversedBy="user_payment")
     */
    protected $user_subscription = null;

    /**
     * @ORM\Column(type="decimal", precision=9, scale=3)
     */
    protected $amount = 0;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $created_at;

    /**
     * Relationships
     */

    /**
     * @ORM\OneToOne(targetEntity="UserTransaction", mappedBy="user_payment")
     */
    protected $user_transaction;

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

    /**
     * Set date_commenced
     *
     * @param \DateTime $dateCommenced
     *
     * @return UserPayment
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
     * @return UserPayment
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
     * @return UserPayment
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
     * @return UserPayment
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
     * Set user_subscription
     *
     * @param UserSubscription $userSubscription
     *
     * @return UserPayment
     */
    public function setUserSubscription(\Vocalizr\AppBundle\Entity\UserSubscription $userSubscription = null)
    {
        $this->user_subscription = $userSubscription;

        return $this;
    }

    /**
     * Get user_subscription
     *
     * @return UserSubscription
     */
    public function getUserSubscription()
    {
        return $this->user_subscription;
    }

    /**
     * Set user_transaction
     *
     * @param UserTransaction $userTransaction
     *
     * @return UserPayment
     */
    public function setUserTransaction(\Vocalizr\AppBundle\Entity\UserTransaction $userTransaction = null)
    {
        $this->user_transaction = $userTransaction;

        return $this;
    }

    /**
     * Get user_transaction
     *
     * @return UserTransaction
     */
    public function getUserTransaction()
    {
        return $this->user_transaction;
    }

    /**
     * Set amount
     *
     * @param float $amount
     *
     * @return UserPayment
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return float
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     *
     * @return UserPayment
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
}