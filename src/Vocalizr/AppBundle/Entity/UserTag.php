<?php

namespace Vocalizr\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Vocalizr\AppBundle\Repository\UserTagRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="user_tag", indexes={
 *          @ORM\Index(name="tag_idx", columns={"tag"})
 * })
 */
class UserTag
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo", inversedBy="user_tags")
     */
    protected $user_info;

    /**
     * @ORM\Column(type="string", length=16)
     */
    protected $tag;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo", inversedBy="tagged_users")
     */
    protected $tagged_by;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $created_at;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $updated_at;

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->created_at = new \DateTime();
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
     * Set tag
     *
     * @param string $tag
     *
     * @return UserTag
     */
    public function setTag($tag)
    {
        $this->tag = $tag;

        return $this;
    }

    /**
     * Get tag
     *
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     *
     * @return UserTag
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
     * Set updated_at
     *
     * @param \DateTime $updatedAt
     *
     * @return UserTag
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
     * Set user_info
     *
     * @param UserInfo $userInfo
     *
     * @return UserTag
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
     * Set tagged_by
     *
     * @param UserInfo $taggedBy
     *
     * @return UserTag
     */
    public function setTaggedBy(\Vocalizr\AppBundle\Entity\UserInfo $taggedBy = null)
    {
        $this->tagged_by = $taggedBy;

        return $this;
    }

    /**
     * Get tagged_by
     *
     * @return UserInfo
     */
    public function getTaggedBy()
    {
        return $this->tagged_by;
    }
}