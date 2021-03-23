<?php

namespace Vocalizr\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="Vocalizr\AppBundle\Repository\UserWithdrawRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="user_withdraw")
 */
class UserWithdraw
{
    const WITHDRAW_STATUS_CANCEL_REQUESTED  = 'CANCELLATION REQUESTED';
    const WITHDRAW_STATUS_ERROR             = 'ERROR';
    const WITHDRAW_STATUS_PENDING           = 'PENDING';
    const WITHDRAW_STATUS_CANCELLED         = 'CANCELLED';
    const WITHDRAW_STATUS_PCOMPLETED        = 'COMPLETED';
    const WITHDRAW_STATUS_UNCLAIMED         = 'UNCLAIMED';
    const WITHDRAW_STATUS_IN_PROGRESS       = 'IN PROGRESS';
    const WITHDRAW_STATUS_WAITING_APPROVE   = 'WAITING APPROVE';

    private static $withdrawStatusStringMap = [
        self::WITHDRAW_STATUS_WAITING_APPROVE => 'Pending Approval',
        self::WITHDRAW_STATUS_UNCLAIMED => 'UNCLAIMED - Contact PayPal',
    ];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo", inversedBy="user_transactions")
     */
    protected $user_info;

    /**
     * @Assert\Email(
     *     message = "The email '{{ value }}' is not a valid email.",
     *     checkMX = false
     * )
     * @Assert\NotBlank()
     * @ORM\Column(type="string", length=150)
     */
    protected $paypal_email;

    /**
     * @ORM\Column(type="integer", length=11)
     */
    protected $amount = 0;

    /**
     * @ORM\Column(type="text")
     */
    protected $description;

    /**
     * @ORM\Column(type="string", length=28)
     */
    protected $status;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $status_reason = null;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $created_at;

    /**
     * @ORM\Column(name="user_ip", type="string", nullable=true)
     */
    protected $userIp;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=16, nullable=true)
     */
    protected $paypal_batch_id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=16, nullable=true)
     */
    protected $paypal_item_id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=36, nullable=true)
     */
    protected $paypal_status;

    /**
     * @ORM\Column(type="integer", length=11)
     */
    protected $fee = 0;

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->setUserIp(!empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']);
        $this->created_at = new \DateTime();
    }

    /**
     * Get amount in dollars.
     * Converts cents to dollars
     *
     * @return float
     */
    public function getAmountDollars()
    {
        return number_format($this->amount / 100, 2, '.', ',');
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
     * Set amount
     *
     * @param float $amount
     *
     * @return UserWithdraw
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
     * Set description
     *
     * @param string $description
     *
     * @return UserWithdraw
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
     * Set status
     *
     * @param string $status
     *
     * @return UserWithdraw
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
     * @return string
     */
    public function getStatusString()
    {
        if (isset(self::$withdrawStatusStringMap[$this->status])) {
            return self::$withdrawStatusStringMap[$this->status];
        } else {
            return $this->status;
        }
    }

    /**
     * Set status_reason
     *
     * @param string $statusReason
     *
     * @return UserWithdraw
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

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     *
     * @return UserWithdraw
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
     * @param \Vocalizr\AppBundle\Entity\UserInfo $userInfo
     *
     * @return UserWithdraw
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
     * Set paypal_email
     *
     * @param string $paypalEmail
     *
     * @return UserWithdraw
     */
    public function setPaypalEmail($paypalEmail)
    {
        $this->paypal_email = $paypalEmail;

        return $this;
    }

    /**
     * Get paypal_email
     *
     * @return string
     */
    public function getPaypalEmail()
    {
        return $this->paypal_email;
    }

    /**
     * @return mixed
     */
    public function getUserIp()
    {
        return $this->userIp;
    }

    /**
     * @param mixed $userIp
     */
    public function setUserIp($userIp)
    {
        $this->userIp = $userIp;
    }

    /**
     * @return string
     */
    public function getPaypalBatchId()
    {
        return $this->paypal_batch_id;
    }

    /**
     * @param string $paypal_batch_id
     *
     * @return UserWithdraw
     */
    public function setPaypalBatchId($paypal_batch_id)
    {
        $this->paypal_batch_id = $paypal_batch_id;

        return $this;
    }

    /**
     * @return string
     */
    public function getPaypalItemId()
    {
        return $this->paypal_item_id;
    }

    /**
     * @param string $paypal_item_id
     *
     * @return UserWithdraw
     */
    public function setPaypalItemId($paypal_item_id)
    {
        $this->paypal_item_id = $paypal_item_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getPaypalStatus()
    {
        return $this->paypal_status;
    }

    /**
     * @param string $paypal_status
     *
     * @return UserWithdraw
     */
    public function setPaypalStatus($paypal_status)
    {
        $this->paypal_status = $paypal_status;
        return $this;
    }

    /**
     * Set fee
     *
     * @param int $fee
     *
     * @return UserWithdraw
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
}