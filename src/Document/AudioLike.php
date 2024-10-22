<?php

namespace App\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Index as MongoIndex;
use Doctrine\ODM\MongoDB\Mapping\Annotations\Field as MongoField;

/**
 * @MongoDB\Document(repositoryClass="App\Repository\AudioLikeRepository")
 */
class AudioLike
{
    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @MongoField(type="integer")
     */
    protected $user_id;

    /**
     * @MongoField(type="integer")
     * @MongoIndex
     */
    protected $from_user_id;

    /**
     * @MongoField(type="integer")
     * @MongoIndex
     */
    protected $audio_id;

    /**
     * @MongoField(type="string")
     */
    protected $date;

    /**
     * Get id
     *
     * @return int $id
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
     * Set fromUserId
     *
     * @param int $fromUserId
     *
     * @return self
     */
    public function setFromUserId($fromUserId)
    {
        $this->from_user_id = $fromUserId;
        return $this;
    }

    /**
     * Get fromUserId
     *
     * @return int $fromUserId
     */
    public function getFromUserId()
    {
        return $this->from_user_id;
    }

    public function toArray()
    {
        return [
            'id'            => $this->id,
            'user_id'       => $this->user_id,
            'audio_id'      => $this->audio_id,
            'date'          => $this->date,
            'from_user_id'  => $this->from_user_id
        ];
    }
}
