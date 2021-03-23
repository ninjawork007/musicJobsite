<?php

namespace Vocalizr\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table(name="reset_pass_request")
 * @ORM\HasLifecycleCallbacks()
 */
class ResetPassRequest
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\OneToOne(targetEntity="UserInfo")
     * @ORM\JoinColumn(name="user_info_id", referencedColumnName="id")
     */
    protected $user_info;

    /**
     * @ORM\Column(length=40)
     */
    protected $unique_key;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $created_at;

    /**
     * @ORM\PrePersist
     */
    public function setCreatedAtValue()
    {
        $this->created_at = new \DateTime();
    }

    public function generateUniqueKey()
    {
        return sha1(time() . rand(9, 999999));
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
     * Set unique_key
     *
     * @param string $uniqueKey
     *
     * @return ResetPassRequest
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
     * Set created_at
     *
     * @param \DateTime $createdAt
     *
     * @return ResetPassRequest
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
     * @return ResetPassRequest
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
}