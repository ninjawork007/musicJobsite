<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserReviewRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="user_review")
 */
class UserReview
{
    const REVIEW_TYPE_VOCALIST = 'vocalist';
    const REVIEW_TYPE_PRODUCER = 'producer';
    const REVIEW_TYPE_EMPLOYER = 'employer';

    public static $reviewTypes = [
        self::REVIEW_TYPE_VOCALIST,
        self::REVIEW_TYPE_PRODUCER,
        self::REVIEW_TYPE_EMPLOYER,
    ];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo", inversedBy="user_reviews")
     */
    protected $user_info;

    /**
     * @ORM\ManyToOne(targetEntity="Project")
     */
    protected $project = null;

    /**
     * @ORM\Column(type="float", length=3)
     */
    protected $rating;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=15, nullable=true)
     */
    protected $type;

    /**
     * @ORM\Column(type="integer", length=2)
     */
    protected $quality_of_work = 1;

    /**
     * @ORM\Column(type="integer", length=2)
     */
    protected $communication = 1;

    /**
     * @ORM\Column(type="integer", length=2)
     */
    protected $professionalism = 1;

    /**
     * @ORM\Column(type="integer", length=2)
     */
    protected $work_with_again = 1;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $on_time = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Assert\NotBlank(message="Please add more detail to your review")
     */
    protected $content = null;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo", inversedBy="reviewed_users")
     */
    protected $reviewed_by;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $created_at;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $updated_at;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $hide = false;

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->created_at = new \DateTime();
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updated_at = new \DateTime();
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
     * Set rating
     *
     * @param int $rating
     *
     * @return UserReview
     */
    public function setRating($rating)
    {
        $this->rating = $rating;

        return $this;
    }

    /**
     * Get rating
     *
     * @return int
     */
    public function getRating()
    {
        return $this->rating;
    }

    /**
     * Set content
     *
     * @param string $content
     *
     * @return UserReview
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     *
     * @return UserReview
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
     * Set updated_at
     *
     * @param \DateTime $updatedAt
     *
     * @return UserReview
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    /**
     * Get updated_at
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * Set user_info
     *
     * @param UserInfo $userInfo
     *
     * @return UserReview
     */
    public function setUserInfo(UserInfo $userInfo = null)
    {
        $this->user_info = $userInfo;

        return $this;
    }

    /**
     * Get user_info
     *
     * @return UserInfo
     */
    public function getUserInfo()
    {
        return $this->user_info;
    }

    /**
     * Set reviewed_by
     *
     * @param UserInfo $reviewedBy
     *
     * @return UserReview
     */
    public function setReviewedBy(UserInfo $reviewedBy = null)
    {
        $this->reviewed_by = $reviewedBy;

        return $this;
    }

    /**
     * Get reviewed_by
     *
     * @return UserInfo
     */
    public function getReviewedBy()
    {
        return $this->reviewed_by;
    }

    /**
     * Set project
     *
     * @param \App\Entity\Project $project
     *
     * @return UserReview
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
     * Set quality_of_work
     *
     * @param int $qualityOfWork
     *
     * @return UserReview
     */
    public function setQualityOfWork($qualityOfWork)
    {
        $this->quality_of_work = $qualityOfWork;

        return $this;
    }

    /**
     * Get quality_of_work
     *
     * @return int
     */
    public function getQualityOfWork()
    {
        return $this->quality_of_work;
    }

    /**
     * Set professionalism
     *
     * @param int $professionalism
     *
     * @return UserReview
     */
    public function setProfessionalism($professionalism)
    {
        $this->professionalism = $professionalism;

        return $this;
    }

    /**
     * Get professionalism
     *
     * @return int
     */
    public function getProfessionalism()
    {
        return $this->professionalism;
    }

    /**
     * Set work_with_again
     *
     * @param int $workWithAgain
     *
     * @return UserReview
     */
    public function setWorkWithAgain($workWithAgain)
    {
        $this->work_with_again = $workWithAgain;

        return $this;
    }

    /**
     * Get work_with_again
     *
     * @return int
     */
    public function getWorkWithAgain()
    {
        return $this->work_with_again;
    }

    /**
     * Set communication
     *
     * @param int $communication
     *
     * @return UserReview
     */
    public function setCommunication($communication)
    {
        $this->communication = $communication;

        return $this;
    }

    /**
     * Get communication
     *
     * @return int
     */
    public function getCommunication()
    {
        return $this->communication;
    }

    /**
     * Set on_time
     *
     * @param bool $onTime
     *
     * @return UserReview
     */
    public function setOnTime($onTime)
    {
        $this->on_time = $onTime;

        return $this;
    }

    /**
     * Get on_time
     *
     * @return bool
     */
    public function getOnTime()
    {
        return $this->on_time;
    }

    /**
     * Set hide
     *
     * @param bool $hide
     *
     * @return UserReview
     */
    public function setHide($hide)
    {
        $this->hide = $hide;

        return $this;
    }

    /**
     * Get hide
     *
     * @return bool
     */
    public function getHide()
    {
        return $this->hide;
    }

    /**
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string|null $type
     * @return UserReview
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }
}