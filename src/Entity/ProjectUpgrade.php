<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ProjectUpgradeRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="project_upgrade")
 */
class ProjectUpgrade
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo", inversedBy="project_bids")
     */
    protected $user_info;

    /**
     * @ORM\ManyToOne(targetEntity="Project", inversedBy="project_bids")
     */
    protected $project;

    /**
     * @ORM\Column(type="string", length=20)
     */
    protected $upgrade;

    /**
     * @ORM\Column(type="integer", length=11)
     */
    protected $amount = 0;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $created_at;

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->created_at = new \DateTime();
    }

    /**
     * Get amount in dollars.
     * Converts cents to dollars
     *
     * @return float
     */
    public function getAmountDollars()
    {
        return number_format($this->amount / 100, 2);
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
     * Set upgrade
     *
     * @param string $upgrade
     *
     * @return ProjectUpgrade
     */
    public function setUpgrade($upgrade)
    {
        $this->upgrade = $upgrade;

        return $this;
    }

    /**
     * Get upgrade
     *
     * @return string
     */
    public function getUpgrade()
    {
        return $this->upgrade;
    }

    /**
     * Set amount
     *
     * @param int $amount
     *
     * @return ProjectUpgrade
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return int
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     *
     * @return ProjectUpgrade
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
     * @param \App\Entity\UserInfo $userInfo
     *
     * @return ProjectUpgrade
     */
    public function setUserInfo(\App\Entity\UserInfo $userInfo = null)
    {
        $this->user_info = $userInfo;

        return $this;
    }

    /**
     * Get user_info
     *
     * @return \App\Entity\UserInfo
     */
    public function getUserInfo()
    {
        return $this->user_info;
    }

    /**
     * Set project
     *
     * @param \App\Entity\Project $project
     *
     * @return ProjectUpgrade
     */
    public function setProject(\App\Entity\Project $project = null)
    {
        $this->project = $project;

        return $this;
    }

    /**
     * Get project
     *
     * @return \App\Entity\Project
     */
    public function getProject()
    {
        return $this->project;
    }
}