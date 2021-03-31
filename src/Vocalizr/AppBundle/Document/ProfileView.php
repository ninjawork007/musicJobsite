<?php

namespace Vocalizr\AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document(repositoryClass="Vocalizr\AppBundle\Repository\ProfileViewRepository")
 */
class ProfileView
{
    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @MongoDB\Field(type="int")

     * @MongoDB\Index
     */
    protected $user_id;

    /**
     * @MongoDB\Field(type="string")

     * @MongoDB\Index
     */
    protected $date;

    /**
     * @MongoDB\Boolean
     */
    protected $unique;

    /**
     * @MongoDB\Field(type="int")

     */
    protected $count;

    public function fromArray($data)
    {
        foreach ($data as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * Get id
     *
     * @return id $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set date
     *
     * @param date $date
     *
     * @return self
     */
    public function setDate($date)
    {
        $this->date = $date;
        return $this;
    }

    /**
     * Get date
     *
     * @return date $date
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set unique
     *
     * @param bool $unique
     *
     * @return self
     */
    public function setUnique($unique)
    {
        $this->unique = $unique;
        return $this;
    }

    /**
     * Get unique
     *
     * @return bool $unique
     */
    public function getUnique()
    {
        return $this->unique;
    }

    /**
     * Set count
     *
     * @param int $count
     *
     * @return self
     */
    public function setCount($count)
    {
        $this->count = $count;
        return $this;
    }

    /**
     * Get count
     *
     * @return int $count
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * Set userId
     *
     * @param int $userId
     *
     * @return self
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;
        return $this;
    }

    /**
     * Get userId
     *
     * @return int $userId
     */
    public function getUserId()
    {
        return $this->user_id;
    }
}
