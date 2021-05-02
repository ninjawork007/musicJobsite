<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserTransactionRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="user_transaction")
 */
class UserTransaction
{
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
     * @ORM\Column(type="boolean")
     */
    protected $success;

    /**
     * @ORM\Column(type="decimal", precision=9, scale=3)
     */
    protected $amount = 0;

    /**
     * @ORM\Column(type="text")
     */
    protected $response;

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
     * Set success
     *
     * @param bool $success
     *
     * @return UserTransaction
     */
    public function setSuccess($success)
    {
        $this->success = $success;

        return $this;
    }

    /**
     * Get success
     *
     * @return bool
     */
    public function getSuccess()
    {
        return $this->success;
    }

    /**
     * Set amount
     *
     * @param float $amount
     *
     * @return UserTransaction
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
     * Set response
     *
     * @param string $response
     *
     * @return UserTransaction
     */
    public function setResponse($response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * Get response
     *
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     *
     * @return UserTransaction
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
     * @return UserTransaction
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
}