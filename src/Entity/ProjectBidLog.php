<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class ProjectBidLog
 *
 * @package App\Entity
 *
 * @ORM\Entity(repositoryClass="App\Repository\ProjectBidLogRepository")
 * @ORM\Table(name="project_bid_log")
 */
class ProjectBidLog
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var UserInfo
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\UserInfo")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $user;

    /**
     * @var ProjectBid|null
     *
     * @ORM\OneToOne(targetEntity="App\Entity\ProjectBid", inversedBy="logEntry")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $bid;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $created_at;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    private $pro = false;

    public function __construct()
    {
        $this->created_at = new \DateTime();
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
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
     * @return ProjectBidLog
     */
    public function setUser($user)
    {
        $this->user = $user;
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
     *
     * @return ProjectBidLog
     */
    public function setBid($bid)
    {
        $this->bid = $bid;
        return $this;
    }

    /**
     * @return bool
     */
    public function isPro()
    {
        return $this->pro;
    }

    /**
     * @param bool $pro
     *
     * @return ProjectBidLog
     */
    public function setPro($pro)
    {
        $this->pro = $pro;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * @param \DateTime $created_at
     *
     * @return ProjectBidLog
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;
        return $this;
    }
}