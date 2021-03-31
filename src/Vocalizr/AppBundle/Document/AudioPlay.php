<?php

namespace Vocalizr\AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * @MongoDB\Document(repositoryClass="Vocalizr\AppBundle\Repository\AudioPlayRepository")
 */
class AudioPlay
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
     * @MongoDB\Field(type="int")

     * @MongoDB\Index
     */
    protected $audio_id;

    /**
     * @MongoDB\Field(type="string")

     * @MongoDB\Index
     */
    protected $date;

    /**
     * @MongoDB\Field(type="int")

     */
    protected $count;

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

    /**
     * Set audioId
     *
     * @param int $audioId
     *
     * @return self
     */
    public function setAudioId($audioId)
    {
        $this->audio_id = $audioId;
        return $this;
    }

    /**
     * Get audioId
     *
     * @return int $audioId
     */
    public function getAudioId()
    {
        return $this->audio_id;
    }

    /**
     * Set date
     *
     * @param string $date
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
     * @return string $date
     */
    public function getDate()
    {
        return $this->date;
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
}
