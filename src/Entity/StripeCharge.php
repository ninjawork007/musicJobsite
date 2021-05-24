<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\StripeChargeRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="stripe_charge")
 * })
 */
class StripeCharge
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Column(type="integer")
     */
    public $amount = 0;

    /**
     * @ORM\Column(type="text")
     */
    public $data = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    public $balanceTransaction = null;

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

    /**
     * Set amount
     *
     * @param int $amount
     *
     * @return StripeCharge
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
     * Set data
     *
     * @param string $data
     *
     * @return StripeCharge
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
     *
     * @param $balance_transaction
     * @return StripeCharge
     */
    public function setBalanceTransaction($balance_transaction)
    {
        $this->balanceTransaction = $balance_transaction;

        return $this;
    }

    /**
     *
     * @return string
     */
    public function getBalanceTransaction()
    {
        return $this->balanceTransaction;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     *
     * @return StripeCharge
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