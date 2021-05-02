<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\StatisticsRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="statistics", indexes={
 *          @ORM\Index(name="type_idx", columns={"statistics_type"})
 * })
 */
class Statistics
{
    const TYPE_DAY = 'day';

    const TYPE_WEEK = 'week';

    const TYPE_MONTH = 'month';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @Assert\NotBlank(message="Required")
     * @ORM\Column(type="string", length=8)
     */
    protected $statistics_type;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $start_date = null;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $end_date = null;

    /**
     * @ORM\Column(type="integer", length=11)
     */
    protected $users = 0;

    /**
     * @ORM\Column(type="integer", length=11)
     */
    protected $vocalists = 0;

    /**
     * @ORM\Column(type="integer", length=11)
     */
    protected $producers = 0;

    /**
     * @ORM\Column(type="integer", length=11)
     */
    protected $gigs = 0;

    /**
     * @ORM\Column(type="integer", length=11)
     */
    protected $published_gigs = 0;

    /**
     * @ORM\Column(type="integer", length=11)
     */
    protected $public_published_gigs = 0;

    /**
     * @ORM\Column(type="integer", length=11)
     */
    protected $private_published_gigs = 0;

    /**
     * @ORM\Column(type="integer", length=11)
     */
    protected $awarded_gigs = 0;

    /**
     * @ORM\Column(type="integer", length=11)
     */
    protected $public_awarded_gigs = 0;

    /**
     * @ORM\Column(type="integer", length=11)
     */
    protected $private_awarded_gigs = 0;

    /**
     * @ORM\Column(type="integer", length=11)
     */
    protected $completed_gigs = 0;

    /**
     * @ORM\Column(type="integer", length=11)
     */
    protected $public_completed_gigs = 0;

    /**
     * @ORM\Column(type="integer", length=11)
     */
    protected $private_completed_gigs = 0;

    /**
     * @ORM\Column(type="integer", length=11)
     */
    protected $revenue = 0;

    /**
     * @ORM\Column(type="integer", length=11)
     */
    protected $bids = 0;

    /**
     * @ORM\Column(type="integer", length=11)
     */
    protected $messages = 0;

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
     * Set statistics_type
     *
     * @param string $statisticsType
     *
     * @return Statistics
     */
    public function setStatisticsType($statisticsType)
    {
        $this->statistics_type = $statisticsType;

        return $this;
    }

    /**
     * Get statistics_type
     *
     * @return string
     */
    public function getStatisticsType()
    {
        return $this->statistics_type;
    }

    /**
     * Set start_date
     *
     * @param \DateTime $startDate
     *
     * @return Statistics
     */
    public function setStartDate($startDate)
    {
        $this->start_date = $startDate;

        return $this;
    }

    /**
     * Get start_date
     *
     * @return \DateTime
     */
    public function getStartDate()
    {
        return $this->start_date;
    }

    /**
     * Set end_date
     *
     * @param \DateTime $endDate
     *
     * @return Statistics
     */
    public function setEndDate($endDate)
    {
        $this->end_date = $endDate;

        return $this;
    }

    /**
     * Get end_date
     *
     * @return \DateTime
     */
    public function getEndDate()
    {
        return $this->end_date;
    }

    /**
     * Set users
     *
     * @param int $users
     *
     * @return Statistics
     */
    public function setUsers($users)
    {
        $this->users = $users;

        return $this;
    }

    /**
     * Get users
     *
     * @return int
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Set vocalists
     *
     * @param int $vocalists
     *
     * @return Statistics
     */
    public function setVocalists($vocalists)
    {
        $this->vocalists = $vocalists;

        return $this;
    }

    /**
     * Get vocalists
     *
     * @return int
     */
    public function getVocalists()
    {
        return $this->vocalists;
    }

    /**
     * Set producers
     *
     * @param int $producers
     *
     * @return Statistics
     */
    public function setProducers($producers)
    {
        $this->producers = $producers;

        return $this;
    }

    /**
     * Get producers
     *
     * @return int
     */
    public function getProducers()
    {
        return $this->producers;
    }

    /**
     * Set gigs
     *
     * @param int $gigs
     *
     * @return Statistics
     */
    public function setGigs($gigs)
    {
        $this->gigs = $gigs;

        return $this;
    }

    /**
     * Get gigs
     *
     * @return int
     */
    public function getGigs()
    {
        return $this->gigs;
    }

    /**
     * Set published_gigs
     *
     * @param int $publishedGigs
     *
     * @return Statistics
     */
    public function setPublishedGigs($publishedGigs)
    {
        $this->published_gigs = $publishedGigs;

        return $this;
    }

    /**
     * Get published_gigs
     *
     * @return int
     */
    public function getPublishedGigs()
    {
        return $this->published_gigs;
    }

    /**
     * Set public_published_gigs
     *
     * @param int $publicPublishedGigs
     *
     * @return Statistics
     */
    public function setPublicPublishedGigs($publicPublishedGigs)
    {
        $this->public_published_gigs = $publicPublishedGigs;

        return $this;
    }

    /**
     * Get public_published_gigs
     *
     * @return int
     */
    public function getPublicPublishedGigs()
    {
        return $this->public_published_gigs;
    }

    /**
     * Set private_published_gigs
     *
     * @param int $privatePublishedGigs
     *
     * @return Statistics
     */
    public function setPrivatePublishedGigs($privatePublishedGigs)
    {
        $this->private_published_gigs = $privatePublishedGigs;

        return $this;
    }

    /**
     * Get private_published_gigs
     *
     * @return int
     */
    public function getPrivatePublishedGigs()
    {
        return $this->private_published_gigs;
    }

    /**
     * Set awarded_gigs
     *
     * @param int $awardedGigs
     *
     * @return Statistics
     */
    public function setAwardedGigs($awardedGigs)
    {
        $this->awarded_gigs = $awardedGigs;

        return $this;
    }

    /**
     * Get awarded_gigs
     *
     * @return int
     */
    public function getAwardedGigs()
    {
        return $this->awarded_gigs;
    }

    /**
     * Set public_awarded_gigs
     *
     * @param int $publicAwardedGigs
     *
     * @return Statistics
     */
    public function setPublicAwardedGigs($publicAwardedGigs)
    {
        $this->public_awarded_gigs = $publicAwardedGigs;

        return $this;
    }

    /**
     * Get public_awarded_gigs
     *
     * @return int
     */
    public function getPublicAwardedGigs()
    {
        return $this->public_awarded_gigs;
    }

    /**
     * Set private_awarded_gigs
     *
     * @param int $privateAwardedGigs
     *
     * @return Statistics
     */
    public function setPrivateAwardedGigs($privateAwardedGigs)
    {
        $this->private_awarded_gigs = $privateAwardedGigs;

        return $this;
    }

    /**
     * Get private_awarded_gigs
     *
     * @return int
     */
    public function getPrivateAwardedGigs()
    {
        return $this->private_awarded_gigs;
    }

    /**
     * Set completed_gigs
     *
     * @param int $completedGigs
     *
     * @return Statistics
     */
    public function setCompletedGigs($completedGigs)
    {
        $this->completed_gigs = $completedGigs;

        return $this;
    }

    /**
     * Get completed_gigs
     *
     * @return int
     */
    public function getCompletedGigs()
    {
        return $this->completed_gigs;
    }

    /**
     * Set public_completed_gigs
     *
     * @param int $publicCompletedGigs
     *
     * @return Statistics
     */
    public function setPublicCompletedGigs($publicCompletedGigs)
    {
        $this->public_completed_gigs = $publicCompletedGigs;

        return $this;
    }

    /**
     * Get public_completed_gigs
     *
     * @return int
     */
    public function getPublicCompletedGigs()
    {
        return $this->public_completed_gigs;
    }

    /**
     * Set private_completed_gigs
     *
     * @param int $privateCompletedGigs
     *
     * @return Statistics
     */
    public function setPrivateCompletedGigs($privateCompletedGigs)
    {
        $this->private_completed_gigs = $privateCompletedGigs;

        return $this;
    }

    /**
     * Get private_completed_gigs
     *
     * @return int
     */
    public function getPrivateCompletedGigs()
    {
        return $this->private_completed_gigs;
    }

    /**
     * Set revenue
     *
     * @param int $revenue
     *
     * @return Statistics
     */
    public function setRevenue($revenue)
    {
        $this->revenue = $revenue;

        return $this;
    }

    /**
     * Get revenue
     *
     * @return int
     */
    public function getRevenue()
    {
        return $this->revenue;
    }

    /**
     * Set bids
     *
     * @param int $bids
     *
     * @return Statistics
     */
    public function setBids($bids)
    {
        $this->bids = $bids;

        return $this;
    }

    /**
     * Get bids
     *
     * @return int
     */
    public function getBids()
    {
        return $this->bids;
    }

    /**
     * Set messages
     *
     * @param int $messages
     *
     * @return Statistics
     */
    public function setMessages($messages)
    {
        $this->messages = $messages;

        return $this;
    }

    /**
     * Get messages
     *
     * @return int
     */
    public function getMessages()
    {
        return $this->messages;
    }
}