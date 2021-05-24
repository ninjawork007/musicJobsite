<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ProjectBidRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="project_bid")
 */
class ProjectBid
{
    const HIGHLIGHT_OPTION_NONE = 0;
    const HIGHLIGHT_OPTION_1    = 1;
    const HIGHLIGHT_OPTION_2    = 2;
    const HIGHLIGHT_OPTION_3    = 3;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @var UserInfo
     *
     * @ORM\ManyToOne(targetEntity="UserInfo", inversedBy="project_bids")
     */
    protected $user_info;

    /**
     * @ORM\ManyToOne(targetEntity="Project", inversedBy="project_bids")
     */
    protected $project;

    /**
     * @var UserAudio|null
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\UserAudio")
     * @ORM\JoinColumn(nullable=true, onDelete="SET NULL")
     */
    protected $title_audio;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    protected $uuid;

    /**
     * @ORM\Column(type="string", length=200, nullable=true)
     */
    protected $message = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    public $path;

    /**
     * @ORM\Column(type="integer", length=11)
     */
    protected $amount = 0;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $hidden = false;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $shortlist = false;

    /**
     * A = Awarded, D = Declined, user declined project
     * If project is awarded, set project_bid in project table
     *
     * @ORM\Column(type="string", length=1, nullable=true)
     */
    protected $flag = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    protected $flag_comment = null;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $created_at;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $updated_at = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $read_at = null;

    /**
     * Overrite percent taken
     *
     * @var int
     * @ORM\Column(type="integer", length=3, nullable=true)
     */
    protected $payment_percent_taken = null;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $wave_generated = false;

    /**
     * @ORM\Column(type="integer", length=5)
     */
    protected $vote_count = 0;

    /**
     * @ORM\Column(name="user_ip", type="string", nullable=true)
     */
    protected $userIp;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_deleted", type="boolean", options={"default": false})
     */
    protected $deleted = false;

    /**
     * @var int|null - null if no decision was made.
     *
     * @ORM\Column(type="smallint", nullable=true)
     */
    protected $highlightOption;

    /**
     * @var DateTime|null
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $highlightedAt;

    /**
     * Relationships
     */

    /**
     * @var ProjectBidLog
     * @ORM\OneToOne(targetEntity="App\Entity\ProjectBidLog", mappedBy="bid", cascade={"persist", "remove"})
     */
    protected $logEntry;

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->setUserIp(!empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']);
        $this->created_at = new DateTime();
        $this->uuid       = uniqid('pb');
        $this->logEntry   = (new ProjectBidLog())
            ->setBid($this)
            ->setUser($this->user_info)
            ->setPro($this->user_info->isSubscribed())
        ;
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updated_at = new DateTime();
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

    public function getAbsolutePath()
    {
        return null === $this->path
            ? null
            : $this->getUploadRootDir() . '/' . $this->path;
    }

    public function getUploadRootDir()
    {
        // the absolute directory path where uploaded
        // documents should be saved
        return __DIR__ . '/../../../../' . $this->getUploadDir();
    }

    protected function getUploadDir()
    {
        $dir = 'uploads/audio/project/' . $this->project->getId() . '/bids/';

        return $dir;
    }

    /**
     * @ORM\PostRemove()
     */
    public function removeUpload()
    {
        if ($file = $this->getAbsolutePath()) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->project_audio_comments = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set message
     *
     * @param string $message
     *
     * @return ProjectBid
     */
    public function setMessage($message)
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Get message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Set amount
     *
     * @param string $amount
     *
     * @return ProjectBid
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return string
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set created_at
     *
     * @param DateTime $createdAt
     *
     * @return ProjectBid
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get created_at
     *
     * @return DateTime
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set updated_at
     *
     * @param DateTime $updatedAt
     *
     * @return ProjectBid
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    /**
     * Get updated_at
     *
     * @return DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * Set user_info
     *
     * @param \App\Entity\UserInfo $userInfo
     *
     * @return ProjectBid
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
     * @return ProjectBid
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

    /**
     * @return UserAudio|null
     */
    public function getTitleAudio()
    {
        return $this->title_audio;
    }

    /**
     * @param UserAudio|null $title_audio
     *
     * @return ProjectBid
     */
    public function setTitleAudio($title_audio)
    {
        $this->title_audio = $title_audio;
        return $this;
    }

    /**
     * Set hidden
     *
     * @param bool $hidden
     *
     * @return ProjectBid
     */
    public function setHidden($hidden)
    {
        $this->hidden = $hidden;

        return $this;
    }

    /**
     * Get hidden
     *
     * @return bool
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * Set path
     *
     * @param string $path
     *
     * @return ProjectBid
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set flag
     *
     * @param string $flag
     *
     * @return ProjectBid
     */
    public function setFlag($flag)
    {
        $this->flag = $flag;

        return $this;
    }

    /**
     * Get flag
     *
     * @return string
     */
    public function getFlag()
    {
        return $this->flag;
    }

    /**
     * Set flag_comment
     *
     * @param string $flagComment
     *
     * @return ProjectBid
     */
    public function setFlagComment($flagComment)
    {
        $this->flag_comment = $flagComment;

        return $this;
    }

    /**
     * Get flag_comment
     *
     * @return string
     */
    public function getFlagComment()
    {
        return $this->flag_comment;
    }

    /**
     * Set uuid
     *
     * @param string $uuid
     *
     * @return ProjectBid
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * Get uuid
     *
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * Set read_at
     *
     * @param DateTime $readAt
     *
     * @return ProjectBid
     */
    public function setReadAt($readAt)
    {
        $this->read_at = $readAt;

        return $this;
    }

    /**
     * Get read_at
     *
     * @return DateTime
     */
    public function getReadAt()
    {
        return $this->read_at;
    }

    /**
     * Set payment_percent_taken
     *
     * @param int $paymentPercentTaken
     *
     * @return ProjectBid
     */
    public function setPaymentPercentTaken($paymentPercentTaken)
    {
        $this->payment_percent_taken = $paymentPercentTaken;

        return $this;
    }

    /**
     * Get payment_percent_taken
     *
     * @return int
     */
    public function getPaymentPercentTaken()
    {
        return $this->payment_percent_taken;
    }

    /**
     * Set wave_generated
     *
     * @param bool $waveGenerated
     *
     * @return ProjectBid
     */
    public function setWaveGenerated($waveGenerated)
    {
        $this->wave_generated = $waveGenerated;

        return $this;
    }

    /**
     * Get wave_generated
     *
     * @return bool
     */
    public function getWaveGenerated()
    {
        return $this->wave_generated;
    }

    /**
     * Set vote_count
     *
     * @param int $voteCount
     *
     * @return ProjectBid
     */
    public function setVoteCount($voteCount)
    {
        $this->vote_count = $voteCount;

        return $this;
    }

    /**
     * Get vote_count
     *
     * @return int
     */
    public function getVoteCount()
    {
        return $this->vote_count;
    }

    /**
     * Set shortlist
     *
     * @param bool $shortlist
     *
     * @return ProjectBid
     */
    public function setShortlist($shortlist)
    {
        $this->shortlist = $shortlist;

        return $this;
    }

    /**
     * Get shortlist
     *
     * @return bool
     */
    public function getShortlist()
    {
        return $this->shortlist;
    }

    /**
     * @return mixed
     */
    public function getUserIp()
    {
        return $this->userIp;
    }

    /**
     * @param mixed $userIp
     */
    public function setUserIp($userIp)
    {
        $this->userIp = $userIp;
    }

    /**
     * @return bool
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * @param bool $deleted
     *
     * @return ProjectBid
     */
    public function setDeleted($deleted)
    {
        $this->deleted = $deleted;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getHighlightOption()
    {
        return $this->highlightOption;
    }

    /**
     * @param int|null $highlightOption
     * @return ProjectBid
     */
    public function setHighlightOption($highlightOption)
    {
        $this->highlightOption = $highlightOption;
        return $this;
    }

    /**
     * @return DateTime|null
     */
    public function getHighlightedAt()
    {
        return $this->highlightedAt;
    }

    /**
     * @param DateTime|null $highlightedAt
     * @return ProjectBid
     */
    public function setHighlightedAt($highlightedAt)
    {
        $this->highlightedAt = $highlightedAt;
        return $this;
    }
}