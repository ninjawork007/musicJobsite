<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class PaymentSessionData
 * @package App\Entity
 *
 * @ORM\Entity(repositoryClass="App\Repository\PaymentSessionDataRepository")
 * @ORM\Table("payment_session_data")
 */
class PaymentSessionData
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string|null - must be set
     *
     * @ORM\Column(type="string", unique=true)
     */
    private $sessionId;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $subscriptionId;

    /**
     * @var StripeCharge|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\StripeCharge")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $charge;

    /**
     * @var Project|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Project")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $project;

    /**
     * @var ProjectBid|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\ProjectBid")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $bid;

    /**
     * @var UserCertification|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\UserCertification")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $userCertification;

    /**
     * @var UserInfo|null - must be set in any case.
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\UserInfo")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $user;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $processed = false;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $contestExtension = false;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $connectionsExtend = false;

    /**
     * @var string[][] - array of product data.
     *
     * @ORM\Column(type="json_array", nullable=true)
     */
    private $products = [];

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * @param string|null $sessionId
     * @return PaymentSessionData
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSubscriptionId()
    {
        return $this->subscriptionId;
    }

    /**
     * @param string|null $subscriptionId
     * @return PaymentSessionData
     */
    public function setSubscriptionId($subscriptionId)
    {
        $this->subscriptionId = $subscriptionId;
        return $this;
    }

    /**
     * @return Project|null
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @param Project|null $project
     * @return PaymentSessionData
     */
    public function setProject($project)
    {
        $this->project = $project;
        return $this;
    }

    /**
     * @return ProjectBid|null
     */
    public function getBid()
    {
        return $this->bid;
    }

    /**
     * @param ProjectBid|null $bid
     * @return PaymentSessionData
     */
    public function setBid($bid)
    {
        $this->bid = $bid;
        return $this;
    }

    /**
     * @return UserCertification|null
     */
    public function getUserCertification()
    {
        return $this->userCertification;
    }

    /**
     * @param UserCertification|null $userCertification
     * @return PaymentSessionData
     */
    public function setUserCertification($userCertification)
    {
        $this->userCertification = $userCertification;
        return $this;
    }

    /**
     * @return StripeCharge|null
     */
    public function getStripeCharge()
    {
        return $this->charge;
    }

    /**
     * @param StripeCharge|null $charge
     * @return PaymentSessionData
     */
    public function setStripeCharge($charge)
    {
        $this->charge = $charge;
        return $this;
    }

    /**
     * @return UserInfo|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param UserInfo|null $user
     * @return PaymentSessionData
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return bool
     */
    public function isProcessed()
    {
        return $this->processed;
    }

    /**
     * @param bool $processed
     * @return PaymentSessionData
     */
    public function setProcessed($processed)
    {
        $this->processed = $processed;
        return $this;
    }

    /**
     * @return bool
     */
    public function isContestExtension()
    {
        return $this->contestExtension;
    }

    /**
     * @param $contestExtension
     * @return PaymentSessionData
     */
    public function setContestExtension($contestExtension)
    {
        $this->contestExtension = $contestExtension;
        return $this;
    }

    /**
     * @return bool
     */
    public function isConnectionsExtend()
    {
        return $this->connectionsExtend;
    }

    /**
     * @param $connectionsExtend
     * @return PaymentSessionData
     */
    public function setConnectionsExtend($connectionsExtend)
    {
        $this->connectionsExtend = $connectionsExtend;
        return $this;
    }

    /**
     * @return array
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * @param array $products
     * @return PaymentSessionData
     */
    public function setProducts($products)
    {
        $this->products = $products;
        return $this;
    }

    /**
     * @param string|null $productKey
     * @param string|null $priceKey
     * @param string|null $priceId
     * @param float|null $priceValue
     *
     * @return PaymentSessionData
     */
    public function addProduct($productKey = null, $priceKey = null, $priceId = null, $priceValue = null)
    {
        $this->products[] = [
            'product_key' => $productKey,
            'price_key'   => $priceKey,
            'price_id'    => $priceId,
            'price_value' => $priceValue,
        ];

        return $this;
    }

    /**
     * @param array $criteria
     * @return array|null
     */
    public function getProduct($criteria = [])
    {
        foreach ($this->products as $product) {
            $match = true;
            foreach ($criteria as $key => $value) {
                if ($product[$key] != $value) {
                    $match = false;
                    break;
                }
            }

            if ($match) {
                return $product;
            }
        }

        return null;
    }
}
