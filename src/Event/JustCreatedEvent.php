<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;
use App\Entity\Project;
use App\Entity\UserInfo;

/**
 * Class JustCreatedEvent
 *
 * @package App\Event
 */
class JustCreatedEvent extends Event
{
    const NAME = 'contest_or_gig.just_created';

    const TYPE_CONTEST = 'contest';

    const TYPE_GIG = 'gig';

    /** @var string */
    private $type;

    /** @var UserInfo */
    private $user;

    /** @var Project */
    private $createdEntity;

    /**
     * JustCreatedEvent constructor.
     *
     * @param string   $type
     * @param UserInfo $user
     * @param Project  $project
     */
    public function __construct($type, $user, $project)
    {
        $this->type = $type;
        $this
            ->setUser($user)
            ->setType($type)
            ->setCreatedEntity($project)
        ;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return JustCreatedEvent
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return UserInfo
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param UserInfo $user
     *
     * @return JustCreatedEvent
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return Project
     */
    public function getCreatedEntity()
    {
        return $this->createdEntity;
    }

    /**
     * @param Project $createdEntity
     *
     * @return JustCreatedEvent
     */
    public function setCreatedEntity($createdEntity)
    {
        $this->createdEntity = $createdEntity;
        return $this;
    }

    /**
     * @return bool
     */
    public function isGig()
    {
        return $this->type === self::TYPE_GIG;
    }
}