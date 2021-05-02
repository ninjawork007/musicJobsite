<?php


namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class UserTotalOnline
 * @package App\Entity
 *
 * @ORM\Entity()
 * @ORM\Table(name="user_total_online")
 */
class UserTotalOnline
{

    /**
     * @var int|null
     *
     * @ORM\Id()
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastActionAt;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $totalTime;

    /**
     * @var UserInfo|null
     *
     * @ORM\OneToOne(targetEntity="App\Entity\UserInfo", mappedBy="userOnline")
     * @ORM\JoinColumn(name="user_id", onDelete="CASCADE")
     */
    private $user;

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int|null $id
     * @return UserTotalOnline
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return \DateTime|null
     */
    public function getLastActionAt()
    {
        return $this->lastActionAt;
    }

    /**
     * @param \DateTime|null $lastActionAt
     * @return UserTotalOnline
     */
    public function setLastActionAt($lastActionAt)
    {
        $this->lastActionAt = $lastActionAt;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getTotalTime()
    {
        return $this->totalTime;
    }

    /**
     * @param string|null $totalTime
     * @return UserTotalOnline
     */
    public function setTotalTime($totalTime)
    {
        $this->totalTime = $totalTime;
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
     * @return UserTotalOnline
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return string
     */
    public function getSimpleTotalTime()
    {
        if ($this->totalTime > 3600) {
            $h = floor($this->totalTime / 3600);
            $m = floor(($this->totalTime / 60) % 60);
            return $h . 'h ' . $m . 'm';
        }
        return ceil($this->totalTime / 60) . 'm';
    }

}