<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserWalletTransactionRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="user_wallet_transaction", indexes={
 *     @ORM\Index(name="search_idx", columns={"user_info_id", "email", "type"})
 * })
 */
class UserWalletTransaction
{
    const PROJECT_PAYMENT       = 'PROJECT_PAYMENT';
    const TYPE_DEPOSIT          = 'DEPOSIT';
    const TYPE_WRONG_DEPOSIT    = 'WRONG_DEPOSIT';
    const TYPE_DEPOSIT_REFUND   = 'DEPOSIT_REFUND';
    const TYPE_TRANSACTION_FEE  = 'TRANSACTION_FEE';
    const TYPE_WITHDRAW         = 'WITHDRAW';
    const TYPE_WITHDRAW_REQUEST = 'WITHDRAW_REQUEST';
    const TYPE_WITHDRAW_REFUND  = 'WITHDRAW_REFUND';
    const USER_UPGRADE_DEPOSIT  = 'USER_UPGRADE_DEPOSIT';
    const USER_UPGRADE          = 'USER_UPGRADE';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=127, unique=true, nullable=true)
     */
    protected $custom_id;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo", inversedBy="user_wallet_transactions")
     */
    protected $user_info;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $description = null;

    /**
     * @ORM\Column(type="integer", length=11)
     */
    protected $amount = 0;

    /**
     * @ORM\Column(type="string", length=3, nullable=true)
     */
    protected $currency = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $data = null;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $created_at;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=35, nullable=true)
     */
    protected $type;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $email;

    /**
     * @var int|null
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $actual_balance;

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        if (!$this->created_at) {
            $this->created_at = new \DateTime();
        }
    }

    /**
     * Get amount in dollars.
     * Converts cents to dollars
     *
     * @return float
     */
    public function getAmountDollars()
    {
        return number_format($this->amount / 100, 2);
    }

    /**
     * @return float
     */
    public function getAmountDollarsFloat()
    {
        return $this->amount / 100;
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
     * @return string|null
     */
    public function getCustomId()
    {
        return $this->custom_id;
    }

    /**
     * @param string|null $customId
     *
     * @return UserWalletTransaction
     */
    public function setCustomId($customId)
    {
        $this->custom_id = $customId;
        return $this;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return UserWalletTransaction
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
     * Set amount
     *
     * @param float $amount
     *
     * @return UserWalletTransaction
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
     * Set currency
     *
     * @param string $currency
     *
     * @return UserWalletTransaction
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     *
     * @return UserWalletTransaction
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
     * @return UserWalletTransaction
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
     * Set data
     *
     * @param string $data
     *
     * @return UserWalletTransaction
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Get data
     *
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string|null $type
     *
     * @return UserWalletTransaction
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     *
     * @return UserWalletTransaction
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getActualBalance()
    {
        return $this->actual_balance;
    }

    /**
     * @param int|null $actualBalance
     *
     * @return UserWalletTransaction
     */
    public function setActualBalance($actualBalance)
    {
        $this->actual_balance = $actualBalance;
        return $this;
    }

    /**
     * @return float|null
     */
    public function getActualBalanceDollars()
    {
        return $this->getActualBalance() / 100;
    }
}