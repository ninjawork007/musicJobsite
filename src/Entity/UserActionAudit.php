<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class UserActionAudit
 *
 * @package App\Entity
 *
 * @ORM\Entity(repositoryClass="App\Repository\UserActionAuditRepository")
 * @ORM\Table(name="user_actions_audit")
 */
class UserActionAudit
{
    const ACTION_LOGIN = 'login';

    const ACTION_DEPOSIT = 'deposit';

    const ACTION_WITHDRAW = 'withdraw';

    const ACTION_ADD_BID = 'add_bid';

    const ACTION_REMOVE_BID = 'remove_bid';

    const ACTION_PROJECT_RELEASE_ESCROW = 'release_escrow';

    /**
     * @var int|null
     *
     * @ORM\Id()
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var UserInfo
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\UserInfo", inversedBy="audits")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $user;

    /**
     * @var Project|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\Project")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $project;

    /**
     * @var UserWithdraw|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\UserWithdraw")
     * @ORM\JoinColumn(onDelete="SET NULL")
     */
    private $withdraw;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=15)
     */
    private $action;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=16)
     */
    private $ip_address;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @var array|null
     *
     * @ORM\Column(type="json_array", nullable=true)
     */
    private $data = [];

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    /**
     * @return int|null
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
     * @return UserActionAudit
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return string
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * @param string $action
     *
     * @return UserActionAudit
     */
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * @return string
     */
    public function getIpAddress()
    {
        return $this->ip_address;
    }

    /**
     * @param string $ip_address
     *
     * @return UserActionAudit
     */
    public function setIpAddress($ip_address)
    {
        $this->ip_address = $ip_address;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param \DateTime $createdAt
     *
     * @return UserActionAudit
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array|null $data
     * @return UserActionAudit
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @param $key
     * @param $value
     * @return $this
     */
    public function addData($key, $value)
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * @return Project|null
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @param Project|null $project
     * @return UserActionAudit
     */
    public function setProject($project)
    {
        $this->project = $project;
        return $this;
    }

    /**
     * @return UserWithdraw|null
     */
    public function getWithdraw()
    {
        return $this->withdraw;
    }

    /**
     * @param UserWithdraw|null $withdraw
     * @return UserActionAudit
     */
    public function setWithdraw($withdraw)
    {
        $this->withdraw = $withdraw;
        return $this;
    }
}