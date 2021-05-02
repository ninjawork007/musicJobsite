<?php


namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class UserStripeIdentity
 * @package App\Entity
 *
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="user_stripe_identity")
 * @ORM\Entity()
 */
class UserStripeIdentity
{

    /**
     * @var int|null
     *
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $custom = false;

    /**
     * Stripe Verification Intent Id
     *
     * @var string|null
     *
     * @ORM\Column(name="vi_id",type="string", nullable=true)
     */
    private $verificationIntentId;


    /**
     * Stripe Verification Report Id
     *
     * @var string|null
     *
     * @ORM\Column(name="vr_id", type="string", nullable=true)
     */
    private $verificationReportId;

    /**
     * @var string|null
     *
     * @ORM\Column(name="url", type="string", nullable=true)
     */
    private $verificationUrl;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $verified = false;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $data;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $createdAt;

    /**
     * @var UserInfo|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\UserInfo", inversedBy="userIdentity")
     */
    private $user;

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->setCreatedAt(new \DateTime());
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     * @return UserStripeIdentity
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return bool
     */
    public function isCustom()
    {
        return $this->custom;
    }

    /**
     * @param bool $custom
     * @return UserStripeIdentity
     */
    public function setCustom($custom)
    {
        $this->custom = $custom;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getVerificationIntentId()
    {
        return $this->verificationIntentId;
    }

    /**
     * @param string|null $verificationIntentId
     * @return UserStripeIdentity
     */
    public function setVerificationIntentId($verificationIntentId)
    {
        $this->verificationIntentId = $verificationIntentId;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getVerificationReportId()
    {
        return $this->verificationReportId;
    }

    /**
     * @param string|null $verificationReportId
     * @return UserStripeIdentity
     */
    public function setVerificationReportId($verificationReportId)
    {
        $this->verificationReportId = $verificationReportId;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getVerificationUrl()
    {
        return $this->verificationUrl;
    }

    /**
     * @param string|null $verificationUrl
     * @return UserStripeIdentity
     */
    public function setVerificationUrl($verificationUrl)
    {
        $this->verificationUrl = $verificationUrl;
        return $this;
    }

    /**
     * @return bool
     */
    public function isVerified()
    {
        return $this->verified;
    }

    /**
     * @param bool $verified
     * @return UserStripeIdentity
     */
    public function setVerified($verified)
    {
        $this->verified = $verified;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string|null $data
     * @return UserStripeIdentity
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime|null $createdAt
     * @return UserStripeIdentity
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
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
     * @return UserStripeIdentity
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }
}