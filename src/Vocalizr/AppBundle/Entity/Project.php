<?php

namespace Vocalizr\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Vocalizr\AppBundle\Validator\Constraints\CustomRegex;

/**
 * @ORM\Entity(repositoryClass="Vocalizr\AppBundle\Repository\ProjectRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="project", indexes={
 *          @ORM\Index(name="title_idx", columns={"title"}),
 *          @ORM\Index(name="published_at_idx", columns={"published_at"})
 * })
 */
class Project
{
    const PUBLISH_PUBLIC = 'public';

    const PUBLISH_PRIVATE = 'private';

    const PROJECT_TYPE_PAID = 'paid';

    const PROJECT_TYPE_COLLABORATION = 'collaboration';

    const PROJECT_TYPE_CONTEST = 'contest';

    const LOOKING_FOR_VOCALIST = 'vocalist';
    const LOOKING_FOR_PRODUCER = 'producer';

    const PAYMENT_STATUS_PENDING = 'pending';
    const PAYMENT_STATUS_PAID    = 'paid';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    protected $uuid;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo", inversedBy="projects")
     */
    protected $user_info;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo")
     */
    protected $employee_user_info = null;

    /**
     * Winning project bid
     *
     * @ORM\ManyToOne(targetEntity="ProjectBid")
     */
    protected $project_bid;

    /**
     * The date and time the winning bid was awarded
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $awarded_at = null;

    /**
     * @ORM\ManyToOne(targetEntity="UserTransaction")
     */
    protected $user_transaction;

    /**
     * @Assert\NotBlank(message="Required")
     * @CustomRegex(
     *     pattern="/[^\x20-\x7e\p{P}]+/mu",
     *     match=false,
     *     message="Gig or Contest title can only contain default characters.  Invalid characters: {{ values }}",
     *     groups={"project", "project_create", "project_update"},
     * )
     *
     * @ORM\Column(type="string", length=64)
     */
    protected $title;

    /**
     * @Assert\NotBlank(message="Required")
     * @ORM\Column(type="string", length=16)
     */
    protected $project_type = Project::PROJECT_TYPE_PAID;

    /**
     * @Assert\NotBlank(message="Required")
     * @Assert\MinLength(
     *     limit=30,
     *     message="Please enter at least {{ limit }} characters."
     * )
     * @CustomRegex(
     *     pattern="/[^\x20-\x7e\s\p{P}]+/mu",
     *     match=false,
     *     message="Description can only contain default characters. Invalid characters: {{ values }}",
     *     groups={"project", "project_create", "project_update"},
     * )
     * @ORM\Column(type="text", nullable=true)
     */
    protected $description = null;

    /**
     * @Assert\NotBlank(groups={"project_update_lyrics"})
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $lyrics = null;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    protected $due_date = null;

    /**
     * @ORM\Column(type="date", nullable=true)
     */
    protected $bids_due = null;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $num_bids = 0;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    protected $bid_total = 0;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $last_bid_at = null;

    /**
     * @ORM\Column(type="string", length=6, nullable=true)
     */
    protected $gender = null;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $studio_access = false;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $pro_required = false;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $budget_from = 0;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $budget_to = 0;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $royalty_mechanical = false;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $royalty_performance = false;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $royalty = 0;

    /**
     * @ORM\Column(type="string", length=10, nullable=true)
     */
    protected $looking_for = null;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    protected $city;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    protected $state;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    protected $country;

    /**
     * @ORM\Column(type="float", precision=10, scale=6, nullable=true)
     */
    protected $location_lat = null;

    /**
     * @ORM\Column(type="float", precision=10, scale=6, nullable=true)
     */
    protected $location_lng = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $enable_gig_hunter = null;

    /**
     * @ORM\Column(type="string", length=8, nullable=true)
     */
    protected $publish_type = Project::PUBLISH_PUBLIC;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $published_at = null;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $to_favorites = false;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $show_in_news = true;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $restrict_to_preferences = null;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $highlight = false;

    /**
     * Message users & discuss their Gig bid or Contest entry
     *
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $messaging = false;

    /**
     * Certified&Pro users can bid to job.
     *
     * @var bool
     *
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $lock_to_cert = false;

    /**
     * Stripe Checkout Session Id
     *
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $ssid;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $featured = false;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $featured_at = null;

    /**
     * @ORM\Column(type="integer", length=11)
     */
    protected $fees = 0;

    /**
     * @ORM\Column(type="string", length=11, nullable=true)
     * @Assert\Min(limit = "0", invalidMessage = "Error")
     */
    protected $bpm = null;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo")
     */
    protected $hire_user = null;

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
    protected $is_active = false;

    /**
     * Whether or not the gig has been completed
     *
     * @ORM\Column(type="boolean")
     */
    protected $is_complete = false;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $completed_at;

    /**
     * @Assert\NotBlank(groups={"employer_sign"}, message="Required")
     *
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    protected $employer_name = null;

    /**
     * @Assert\NotBlank(groups={"employee_sign"}, message="Required")
     * @CustomRegex(
     *     pattern="/[^\x20-\x7e\s\p{P}]+/mu",
     *     match=false,
     *     message="Sign can only contain default characters. Invalid characters: {{ values }}",
     *     groups={"employee_sign"}
     * )
     *
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    protected $employee_name = null;

    /**
     * Prompt bidder to upload assets
     *
     * @ORM\Column(type="boolean")
     */
    protected $prompt_assets = false;

    /**
     * @ORM\OneToOne(targetEntity="ProjectEscrow", inversedBy="project")
     */
    protected $project_escrow = null;

    /**
     * JSON data
     * for now this just stores the date of the last activity in the project
     * eventually we may store information about what was in the last activity
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected $last_activity = '{}';

    /**
     * Stores the date the employer read the last activity
     * is null if the employer hasn't seen the last activity
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $employer_read_at = null;

    /**
     * Stores the date the employee read the last activity
     * is null if the employee hasn't seen the last activity
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $employee_read_at = null;

    /**
     * @Assert\Url(
     *    message = "Invalid url"
     * )
     * @Assert\Regex(
     *     pattern="/\byoutube\b|\bsoundcloud\b/",
     *     message="Url must be either Youtube or Soundcloud"
     * )
     * @Assert\Regex(
     *     pattern="/[^\x20-\x7e]/",
     *     match=false,
     *     message="Audio brief can only contain default characters",
     *     groups={"project", "project_create", "project_update"},
     * )
     * @ORM\Column(type="string", length=500, nullable=true)
     */
    protected $audio_brief = null;

    /**
     * @ORM\Column(type="integer", length=6)
     */
    protected $audio_brief_click = 0;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $sfs = 0;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $public_voting = 0;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": false})
     */
    protected $fullyRefunded = false;

    /**
     * Relationships
     */

    /**
     * @Assert\Count(
     *      min = "1",
     *      max = "5",
     *      minMessage = "Required",
     *      maxMessage = "You cannot specify more than {{ limit }} genres|You cannot specify more than {{ limit }} genres"
     * )
     *
     * @ORM\ManyToMany(targetEntity="Genre")
     * @ORM\JoinTable(name="project_genre",
     *      joinColumns={@ORM\JoinColumn(name="project_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="genre_id", referencedColumnName="id")}
     *      )
     */
    protected $genres;

    /**
     * @ORM\ManyToOne(targetEntity="Language")
     */
    protected $language;

    /**
     * @ORM\ManyToMany(targetEntity="VocalStyle")
     * @ORM\JoinTable(name="project_vocal_styles",
     *      joinColumns={@ORM\JoinColumn(name="project_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="vocal_style_id", referencedColumnName="id")}
     *      )
     */
    protected $vocalStyles;

    /**
     * @ORM\ManyToMany(targetEntity="VocalCharacteristic")
     * @ORM\JoinTable(name="project_vocal_characteristics",
     *      joinColumns={@ORM\JoinColumn(name="project_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="vocal_characteristic_id", referencedColumnName="id")}
     *      )
     */
    protected $vocalCharacteristics;

    /**
     * @ORM\OneToMany(targetEntity="ProjectComment", mappedBy="project")
     */
    protected $project_comments;

    /**
     * @ORM\OneToMany(targetEntity="ProjectInvite", mappedBy="project")
     */
    protected $project_invites;

    /**
     * Bids for a project
     *
     * @ORM\OneToMany(targetEntity="ProjectBid", mappedBy="project")
     */
    protected $project_bids;

    /**
     * Bid messages for a project
     *
     * @ORM\OneToMany(targetEntity="MessageThread", mappedBy="project")
     */
    protected $message_threads;

    /**
     * Project lyric history
     *
     * @ORM\OneToMany(targetEntity="ProjectLyrics", mappedBy="project")
     */
    protected $project_lyrics;

    /**
     * Project assets
     *
     * @ORM\OneToMany(targetEntity="ProjectAsset", mappedBy="project")
     */
    protected $project_assets;

    /**
     * Project contracts
     *
     * @ORM\OneToMany(targetEntity="ProjectContract", mappedBy="project")
     */
    protected $project_contracts;

    /**
     * Project audio
     *
     * @ORM\OneToMany(targetEntity="ProjectAudio", mappedBy="project")
     */
    protected $project_audio;

    /**
     * Project user reviews
     *
     * @ORM\OneToMany(targetEntity="UserReview", mappedBy="project")
     */
    protected $user_reviews;

    /**
     * @ORM\Column(name="user_ip", type="string", nullable=true)
     */
    protected $userIp;

    /**
     * If project needs payment, this field will be 'pending' until vocalizr receive stripe payment webhook.
     * Normally, stripe sends webhook before user come from checkout page. Then status will be 'paid'
     *
     * @var string|null
     *
     * @ORM\Column(type="string", length=22, nullable=true)
     */
    protected $payment_status = self::PAYMENT_STATUS_PENDING;

    /**
     * @var int|null
     *
     * @ORM\Column(type="smallint", nullable=true)
     */
    protected $days_extended;

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->setUserIp(!empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']);
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
     * Check if user is owner of project
     *
     * @param UserInfo $user
     *
     * @return bool
     */
    public function isOwner($user)
    {
        return $user && $this->user_info->id == $user->id;
    }

    public function getProjectTypeName()
    {
        return $this->project_type == 'paid' ? 'gig' : $this->project_type;
    }

    public function getUserDisplayNames()
    {
        $names = [];
        if ($employee = $this->getEmployeeUserInfo()) {
            $names[$employee->getId()] = $employee->getDisplayName();
        }
        $names[$this->getUserInfo()->getId()] = $this->getUserInfo()->getDisplayName();
        return $names;
    }

    /**
     * Check if user can bid on project
     *
     * @param UserInfo $user
     *
     * @return bool
     */
    public function isBiddingAllowed($user)
    {
        return $this->user_info->getId() != $user->getId() && $this->bids_due->getTimestamp() > time();
    }

    public function isVotingAllowed()
    {
        return $this->bids_due->getTimestamp() > time() && !$this->awarded_at;
    }

    /**
     * Get user info of project bidder
     *
     * @return UserInfo|null
     */
    public function getBidderUser()
    {
        if (!$this->project_bid) {
            return null;
        }
        return $this->project_bid->getUserInfo();
    }

    public function getUploadDir()
    {
        // get rid of the __DIR__ so it doesn't screw up
        // when displaying uploaded doc/image in the view.
        $dir = 'uploads/project/' . $this->getId();
        return $dir;
    }

    /**
     * Clean description of links and emails etc
     */
    public function cleanDescription()
    {
        $pattern = "/[^@\s]*@[^@\s]*\.[^@\s]*/";
        $this->setDescription(preg_replace($pattern, '', $this->description));
        $pattern = "/[a-zA-Z]*[:\/\/]*[A-Za-z0-9\-_]+\.+[A-Za-z0-9\.\/%&=\?\-_]+/i";
        $this->setDescription(preg_replace($pattern, '', $this->getDescription()));
        $this->setDescription(str_replace(['skype', 'facebook', 'gmail', 'twitter', 'dot com', 'dotcom', '(at)', '[at]'], '', $this->description));
    }

    public function getUploadRootDir()
    {
        // the absolute directory path where uploaded
        // documents should be saved
        $dir = __DIR__ . '/../../../../' . $this->getUploadDir();
        // If directory doesn't exist, create if
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        return $dir;
    }

    public function getAbsolutePdfPath()
    {
        return null === $this->uuid
            ? null
            : $this->getUploadRootDir() . '/' . $this->uuid . '-agreement.pdf';
    }

    public function getInvoicePdfPath()
    {
        return null === $this->uuid
            ? null
            : $this->getUploadRootDir() . '/' . $this->uuid . '-invoice.pdf';
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->genres = new \Doctrine\Common\Collections\ArrayCollection();
        $this->uuid   = uniqid();
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
     * Set title
     *
     * @param string $title
     *
     * @return Project
     */
    public function setTitle($title)
    {
        $this->title = ucfirst($title);

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set due_date
     *
     * @param \DateTime $dueDate
     *
     * @return Project
     */
    public function setDueDate($dueDate)
    {
        $this->due_date = $dueDate;

        return $this;
    }

    /**
     * Get due_date
     *
     * @return \DateTime
     */
    public function getDueDate()
    {
        return $this->due_date;
    }

    /**
     * @return string
     */
    public function getTimeLeft()
    {
        $now      = new \DateTime();
        $interval = $this->due_date->diff($now);

        return $interval->days . ' days';
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     *
     * @return Project
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
     * @return Project
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
     * Set is_active
     *
     * @param bool $isActive
     *
     * @return Project
     */
    public function setIsActive($isActive)
    {
        $this->is_active = $isActive;

        return $this;
    }

    /**
     * Get is_active
     *
     * @return bool
     */
    public function getIsActive()
    {
        return $this->is_active;
    }

    /**
     * @return bool
     */
    public function isContest()
    {
        return ($this->getProjectType() === self::PROJECT_TYPE_CONTEST);
    }

    /**
     * Set is_complete
     *
     * @param bool $isComplete
     *
     * @return Project
     */
    public function setIsComplete($isComplete)
    {
        $this->is_complete  = $isComplete;
        $this->completed_at = new \DateTime();

        return $this;
    }

    /**
     * Get is_complete
     *
     * @return bool
     */
    public function getIsComplete()
    {
        return $this->is_complete;
    }

    /**
     * Set user_info
     *
     * @param \Vocalizr\AppBundle\Entity\UserInfo $userInfo
     *
     * @return Project
     */
    public function setUserInfo(\Vocalizr\AppBundle\Entity\UserInfo $userInfo = null)
    {
        $this->user_info = $userInfo;

        return $this;
    }

    /**
     * Get user_info
     *
     * @return \Vocalizr\AppBundle\Entity\UserInfo
     */
    public function getUserInfo()
    {
        return $this->user_info;
    }

    /**
     * Set gender
     *
     * @param string $gender
     *
     * @return Project
     */
    public function setGender($gender)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * Get gender
     *
     * @return string
     */
    public function getGender()
    {
        return $this->gender;
    }

    /**
     * Set budget_from
     *
     * @param int $budgetFrom
     *
     * @return Project
     */
    public function setBudgetFrom($budgetFrom)
    {
        $this->budget_from = $budgetFrom;

        return $this;
    }

    /**
     * Get budget_from
     *
     * @return int
     */
    public function getBudgetFrom()
    {
        return $this->budget_from;
    }

    /**
     * Set budget_to
     *
     * @param int $budgetTo
     *
     * @return Project
     */
    public function setBudgetTo($budgetTo)
    {
        $this->budget_to = $budgetTo;

        return $this;
    }

    /**
     * Get budget_to
     *
     * @return int
     */
    public function getBudgetTo()
    {
        return $this->budget_to;
    }

    /**
     * Set city
     *
     * @param string $city
     *
     * @return Project
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set country
     *
     * @param string $country
     *
     * @return Project
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set to_favorites
     *
     * @param bool $toFavorites
     *
     * @return Project
     */
    public function setToFavorites($toFavorites)
    {
        $this->to_favorites = $toFavorites;

        return $this;
    }

    /**
     * Get to_favorites
     *
     * @return bool
     */
    public function getToFavorites()
    {
        return $this->to_favorites;
    }

    /**
     * Set show_in_news
     *
     * @param bool $showInNews
     *
     * @return Project
     */
    public function setShowInNews($showInNews)
    {
        $this->show_in_news = $showInNews;

        return $this;
    }

    /**
     * Get show_in_news
     *
     * @return bool
     */
    public function getShowInNews()
    {
        return $this->show_in_news;
    }

    /**
     * Add genres
     *
     * @param \Vocalizr\AppBundle\Entity\Genre $genres
     *
     * @return Project
     */
    public function addGenre(\Vocalizr\AppBundle\Entity\Genre $genres)
    {
        $this->genres[] = $genres;

        return $this;
    }

    /**
     * Remove genres
     *
     * @param \Vocalizr\AppBundle\Entity\Genre $genres
     */
    public function removeGenre(\Vocalizr\AppBundle\Entity\Genre $genres)
    {
        $this->genres->removeElement($genres);
    }

    /**
     * Get genres
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getGenres()
    {
        return $this->genres;
    }

    /**
     * Add languages
     *
     * @param \Vocalizr\AppBundle\Entity\Language $languages
     *
     * @return Project
     */
    public function addLanguage(\Vocalizr\AppBundle\Entity\Language $languages)
    {
        $this->languages[] = $languages;

        return $this;
    }

    /**
     * Remove language
     *
     * @param \Vocalizr\AppBundle\Entity\Language $languages
     */
    public function removeLanguage(\Vocalizr\AppBundle\Entity\Language $languages)
    {
        $this->languages->removeElement($languages);
    }

    /**
     * Get languages
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getLanguages()
    {
        return $this->languages;
    }

    /**
     * Set uuid
     *
     * @param string $uuid
     *
     * @return Project
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
     * Set description
     *
     * @param string $description
     *
     * @return Project
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set bids_due
     *
     * @param \DateTime $bidsDue
     *
     * @return Project
     */
    public function setBidsDue($bidsDue)
    {
        $this->bids_due = $bidsDue;

        return $this;
    }

    /**
     * Get bids_due
     *
     * @return \DateTime
     */
    public function getBidsDue()
    {
        return $this->bids_due;
    }

    /**
     * Set enable_gig_hunter
     *
     * @param bool $enableGigHunter
     *
     * @return Project
     */
    public function setEnableGigHunter($enableGigHunter)
    {
        $this->enable_gig_hunter = $enableGigHunter;

        return $this;
    }

    /**
     * Get enable_gig_hunter
     *
     * @return bool
     */
    public function getEnableGigHunter()
    {
        return $this->enable_gig_hunter;
    }

    /**
     * Set restrict_to_preferences
     *
     * @param bool $restrictToPreferences
     *
     * @return Project
     */
    public function setRestrictToPreferences($restrictToPreferences)
    {
        $this->restrict_to_preferences = $restrictToPreferences;

        return $this;
    }

    /**
     * Get restrict_to_preferences
     *
     * @return bool
     */
    public function getRestrictToPreferences()
    {
        return $this->restrict_to_preferences;
    }

    /**
     * Set studio_access
     *
     * @param bool $studioAccess
     *
     * @return Project
     */
    public function setStudioAccess($studioAccess)
    {
        $this->studio_access = $studioAccess;

        return $this;
    }

    /**
     * Get studio_access
     *
     * @return bool
     */
    public function getStudioAccess()
    {
        return $this->studio_access;
    }

    /**
     * Set language
     *
     * @param \Vocalizr\AppBundle\Entity\Language $language
     *
     * @return Project
     */
    public function setLanguage(\Vocalizr\AppBundle\Entity\Language $language = null)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Get language
     *
     * @return \Vocalizr\AppBundle\Entity\Language
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Add vocalCharacteristics
     *
     * @param \Vocalizr\AppBundle\Entity\VocalCharacteristic $vocalCharacteristics
     *
     * @return Project
     */
    public function addVocalCharacteristic(\Vocalizr\AppBundle\Entity\VocalCharacteristic $vocalCharacteristics)
    {
        $this->vocalCharacteristics[] = $vocalCharacteristics;

        return $this;
    }

    /**
     * Remove vocalCharacteristics
     *
     * @param \Vocalizr\AppBundle\Entity\VocalCharacteristic $vocalCharacteristics
     */
    public function removeVocalCharacteristic(\Vocalizr\AppBundle\Entity\VocalCharacteristic $vocalCharacteristics)
    {
        $this->vocalCharacteristics->removeElement($vocalCharacteristics);
    }

    /**
     * Get vocalCharacteristics
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getVocalCharacteristics()
    {
        return $this->vocalCharacteristics;
    }

    /**
     * Add project_bids
     *
     * @param \Vocalizr\AppBundle\Entity\ProjectBid $projectBids
     *
     * @return Project
     */
    public function addProjectBid(\Vocalizr\AppBundle\Entity\ProjectBid $projectBids)
    {
        $this->project_bids[] = $projectBids;

        return $this;
    }

    /**
     * Remove project_bids
     *
     * @param \Vocalizr\AppBundle\Entity\ProjectBid $projectBids
     */
    public function removeProjectBid(\Vocalizr\AppBundle\Entity\ProjectBid $projectBids)
    {
        $this->project_bids->removeElement($projectBids);
    }

    /**
     * Get project_bids
     *
     * @return \Doctrine\Common\Collections\Collection|ProjectBid[]
     */
    public function getProjectBids()
    {
        return $this->project_bids;
    }

    /**
     * Add vocalStyles
     *
     * @param \Vocalizr\AppBundle\Entity\VocalStyle $vocalStyles
     *
     * @return Project
     */
    public function addVocalStyle(\Vocalizr\AppBundle\Entity\VocalStyle $vocalStyles)
    {
        $this->vocalStyles[] = $vocalStyles;

        return $this;
    }

    /**
     * Remove vocalStyles
     *
     * @param \Vocalizr\AppBundle\Entity\VocalStyle $vocalStyles
     */
    public function removeVocalStyle(\Vocalizr\AppBundle\Entity\VocalStyle $vocalStyles)
    {
        $this->vocalStyles->removeElement($vocalStyles);
    }

    /**
     * Get vocalStyles
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getVocalStyles()
    {
        return $this->vocalStyles;
    }

    /**
     * Set project_bid
     *
     * @param \Vocalizr\AppBundle\Entity\ProjectBid $projectBid
     *
     * @return Project
     */
    public function setProjectBid(\Vocalizr\AppBundle\Entity\ProjectBid $projectBid = null)
    {
        $this->project_bid = $projectBid;

        return $this;
    }

    /**
     * Get project_bid
     *
     * @return \Vocalizr\AppBundle\Entity\ProjectBid
     */
    public function getProjectBid()
    {
        return $this->project_bid;
    }

    /**
     * Add project_comments
     *
     * @param \Vocalizr\AppBundle\Entity\ProjectComment $projectComments
     *
     * @return Project
     */
    public function addProjectComment(\Vocalizr\AppBundle\Entity\ProjectComment $projectComments)
    {
        $this->project_comments[] = $projectComments;

        return $this;
    }

    /**
     * Remove project_comments
     *
     * @param \Vocalizr\AppBundle\Entity\ProjectComment $projectComments
     */
    public function removeProjectComment(\Vocalizr\AppBundle\Entity\ProjectComment $projectComments)
    {
        $this->project_comments->removeElement($projectComments);
    }

    /**
     * Get project_comments
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProjectComments()
    {
        return $this->project_comments;
    }

    /**
     * Add project_lyrics
     *
     * @param \Vocalizr\AppBundle\Entity\ProjectLyrics $projectLyrics
     *
     * @return Project
     */
    public function addProjectLyric(\Vocalizr\AppBundle\Entity\ProjectLyrics $projectLyrics)
    {
        $this->project_lyrics[] = $projectLyrics;

        return $this;
    }

    /**
     * Remove project_lyrics
     *
     * @param \Vocalizr\AppBundle\Entity\ProjectLyrics $projectLyrics
     */
    public function removeProjectLyric(\Vocalizr\AppBundle\Entity\ProjectLyrics $projectLyrics)
    {
        $this->project_lyrics->removeElement($projectLyrics);
    }

    /**
     * Get project_lyrics
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProjectLyrics()
    {
        return $this->project_lyrics;
    }

    /**
     * Set user_transaction
     *
     * @param \Vocalizr\AppBundle\Entity\UserTransaction $userTransaction
     *
     * @return Project
     */
    public function setUserTransaction(\Vocalizr\AppBundle\Entity\UserTransaction $userTransaction = null)
    {
        $this->user_transaction = $userTransaction;

        return $this;
    }

    /**
     * Get user_transaction
     *
     * @return \Vocalizr\AppBundle\Entity\UserTransaction
     */
    public function getUserTransaction()
    {
        return $this->user_transaction;
    }

    /**
     * Set project_escrow
     *
     * @param ProjectEscrow $projectEscrow
     *
     * @return Project
     */
    public function setProjectEscrow(ProjectEscrow $projectEscrow = null)
    {
        $this->project_escrow = $projectEscrow;

        return $this;
    }

    /**
     * Get project_escrow
     *
     * @return ProjectEscrow
     */
    public function getProjectEscrow()
    {
        return $this->project_escrow;
    }

    /**
     * Set prompt_assets
     *
     * @param bool $promptAssets
     *
     * @return Project
     */
    public function setPromptAssets($promptAssets)
    {
        $this->prompt_assets = $promptAssets;

        return $this;
    }

    /**
     * Get prompt_assets
     *
     * @return bool
     */
    public function getPromptAssets()
    {
        return $this->prompt_assets;
    }

    /**
     * Add project_assets
     *
     * @param \Vocalizr\AppBundle\Entity\ProjectAsset $projectAssets
     *
     * @return Project
     */
    public function addProjectAsset(\Vocalizr\AppBundle\Entity\ProjectAsset $projectAssets)
    {
        $this->project_assets[] = $projectAssets;

        return $this;
    }

    /**
     * Remove project_assets
     *
     * @param \Vocalizr\AppBundle\Entity\ProjectAsset $projectAssets
     */
    public function removeProjectAsset(\Vocalizr\AppBundle\Entity\ProjectAsset $projectAssets)
    {
        $this->project_assets->removeElement($projectAssets);
    }

    /**
     * Get project_assets
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProjectAssets()
    {
        return $this->project_assets;
    }

    /**
     * Set lyrics
     *
     * @param string $lyrics
     *
     * @return Project
     */
    public function setLyrics($lyrics)
    {
        $this->lyrics = $lyrics;

        return $this;
    }

    /**
     * Get lyrics
     *
     * @return string
     */
    public function getLyrics()
    {
        return $this->lyrics;
    }

    /**
     * Add project_contracts
     *
     * @param \Vocalizr\AppBundle\Entity\ProjectContract $projectContracts
     *
     * @return Project
     */
    public function addProjectContract(\Vocalizr\AppBundle\Entity\ProjectContract $projectContracts)
    {
        $this->project_contracts[] = $projectContracts;

        return $this;
    }

    /**
     * Remove project_contracts
     *
     * @param \Vocalizr\AppBundle\Entity\ProjectContract $projectContracts
     */
    public function removeProjectContract(\Vocalizr\AppBundle\Entity\ProjectContract $projectContracts)
    {
        $this->project_contracts->removeElement($projectContracts);
    }

    /**
     * Get project_contracts
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProjectContracts()
    {
        return $this->project_contracts;
    }

    /**
     * Set num_bids
     *
     * @param int $numBids
     *
     * @return Project
     */
    public function setNumBids($numBids)
    {
        $this->num_bids = $numBids;

        return $this;
    }

    /**
     * Get num_bids
     *
     * @return int
     */
    public function getNumBids()
    {
        return $this->num_bids;
    }

    /**
     * Set completed_at
     *
     * @param \DateTime $completedAt
     *
     * @return Project
     */
    public function setCompletedAt($completedAt)
    {
        $this->completed_at = $completedAt;

        return $this;
    }

    /**
     * Get completed_at
     *
     * @return \DateTime
     */
    public function getCompletedAt()
    {
        return $this->completed_at;
    }

    /**
     * Add project_invites
     *
     * @param \Vocalizr\AppBundle\Entity\ProjectInvite $projectInvites
     *
     * @return Project
     */
    public function addProjectInvite(\Vocalizr\AppBundle\Entity\ProjectInvite $projectInvites)
    {
        $this->project_invites[] = $projectInvites;

        return $this;
    }

    /**
     * Remove project_invites
     *
     * @param \Vocalizr\AppBundle\Entity\ProjectInvite $projectInvites
     */
    public function removeProjectInvite(\Vocalizr\AppBundle\Entity\ProjectInvite $projectInvites)
    {
        $this->project_invites->removeElement($projectInvites);
    }

    /**
     * Get project_invites
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProjectInvites()
    {
        return $this->project_invites;
    }

    /**
     * Set last_activity
     *
     * @param string $lastActivity
     *
     * @return Project
     */
    public function setLastActivity($lastActivity)
    {
        $this->last_activity = $lastActivity;

        return $this;
    }

    /**
     * Get last_activity
     *
     * @return string
     */
    public function getLastActivity()
    {
        return json_decode($this->last_activity, true);
    }

    /**
     * Set looking_for
     *
     * @param string $lookingFor
     *
     * @return Project
     */
    public function setLookingFor($lookingFor)
    {
        $this->looking_for = $lookingFor;

        return $this;
    }

    /**
     * Get looking_for
     *
     * @return string
     */
    public function getLookingFor()
    {
        return $this->looking_for;
    }

    /**
     * Set employee_user_info
     *
     * @param \Vocalizr\AppBundle\Entity\UserInfo $employeeUserInfo
     *
     * @return Project
     */
    public function setEmployeeUserInfo(\Vocalizr\AppBundle\Entity\UserInfo $employeeUserInfo = null)
    {
        $this->employee_user_info = $employeeUserInfo;

        return $this;
    }

    /**
     * Get employee_user_info
     *
     * @return \Vocalizr\AppBundle\Entity\UserInfo
     */
    public function getEmployeeUserInfo()
    {
        return $this->employee_user_info;
    }

    /**
     * Add project_audio
     *
     * @param \Vocalizr\AppBundle\Entity\ProjectAudio $projectAudio
     *
     * @return Project
     */
    public function addProjectAudio(\Vocalizr\AppBundle\Entity\ProjectAudio $projectAudio)
    {
        $this->project_audio[] = $projectAudio;

        return $this;
    }

    /**
     * Remove project_audio
     *
     * @param \Vocalizr\AppBundle\Entity\ProjectAudio $projectAudio
     */
    public function removeProjectAudio(\Vocalizr\AppBundle\Entity\ProjectAudio $projectAudio)
    {
        $this->project_audio->removeElement($projectAudio);
    }

    /**
     * Get project_audio
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProjectAudio()
    {
        return $this->project_audio;
    }

    /**
     * Set publish_type
     *
     * @param string $publishType
     *
     * @return Project
     */
    public function setPublishType($publishType)
    {
        $this->publish_type = $publishType;

        return $this;
    }

    /**
     * Get publish_type
     *
     * @return string
     */
    public function getPublishType()
    {
        return $this->publish_type;
    }

    /**
     * Set published_at
     *
     * @param \DateTime $publishedAt
     *
     * @return Project
     */
    public function setPublishedAt($publishedAt)
    {
        $this->published_at = $publishedAt;

        return $this;
    }

    /**
     * Get published_at
     *
     * @return \DateTime
     */
    public function getPublishedAt()
    {
        return $this->published_at;
    }

    /**
     * Set employer_read_at
     *
     * @param \DateTime $employerReadAt
     *
     * @return Project
     */
    public function setEmployerReadAt($employerReadAt)
    {
        $this->employer_read_at = $employerReadAt;

        return $this;
    }

    /**
     * Get employer_read_at
     *
     * @return \DateTime
     */
    public function getEmployerReadAt()
    {
        return $this->employer_read_at;
    }

    /**
     * Set employee_read_at
     *
     * @param \DateTime $employeeReadAt
     *
     * @return Project
     */
    public function setEmployeeReadAt($employeeReadAt)
    {
        $this->employee_read_at = $employeeReadAt;

        return $this;
    }

    /**
     * Get employee_read_at
     *
     * @return \DateTime
     */
    public function getEmployeeReadAt()
    {
        return $this->employee_read_at;
    }

    /**
     * Set last_bid_at
     *
     * @param \DateTime $lastBidAt
     *
     * @return Project
     */
    public function setLastBidAt($lastBidAt)
    {
        $this->last_bid_at = $lastBidAt;

        return $this;
    }

    /**
     * Get last_bid_at
     *
     * @return \DateTime
     */
    public function getLastBidAt()
    {
        return $this->last_bid_at;
    }

    /**
     * Set bid_total
     *
     * @param int $bidTotal
     *
     * @return Project
     */
    public function setBidTotal($bidTotal)
    {
        $this->bid_total = $bidTotal;

        return $this;
    }

    /**
     * Get bid_total
     *
     * @return int
     */
    public function getBidTotal()
    {
        return $this->bid_total;
    }

    /**
     * Set awarded_at
     *
     * @param \DateTime $awardedAt
     *
     * @return Project
     */
    public function setAwardedAt($awardedAt)
    {
        $this->awarded_at = $awardedAt;

        return $this;
    }

    /**
     * Get awarded_at
     *
     * @return \DateTime
     */
    public function getAwardedAt()
    {
        return $this->awarded_at;
    }

    /**
     * Set state
     *
     * @param string $state
     *
     * @return Project
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * Get state
     *
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * Set location_lat
     *
     * @param float $locationLat
     *
     * @return Project
     */
    public function setLocationLat($locationLat)
    {
        $this->location_lat = $locationLat;

        return $this;
    }

    /**
     * Get location_lat
     *
     * @return float
     */
    public function getLocationLat()
    {
        return $this->location_lat;
    }

    /**
     * Set location_lng
     *
     * @param float $locationLng
     *
     * @return Project
     */
    public function setLocationLng($locationLng)
    {
        $this->location_lng = $locationLng;

        return $this;
    }

    /**
     * Get location_lng
     *
     * @return float
     */
    public function getLocationLng()
    {
        return $this->location_lng;
    }

    /**
     * Set employer_name
     *
     * @param string $employerName
     *
     * @return Project
     */
    public function setEmployerName($employerName)
    {
        $this->employer_name = $employerName;

        return $this;
    }

    /**
     * Get employer_name
     *
     * @return string
     */
    public function getEmployerName()
    {
        return $this->employer_name;
    }

    /**
     * Set employee_name
     *
     * @param string $employeeName
     *
     * @return Project
     */
    public function setEmployeeName($employeeName)
    {
        $this->employee_name = $employeeName;

        return $this;
    }

    /**
     * Get employee_name
     *
     * @return string
     */
    public function getEmployeeName()
    {
        return $this->employee_name;
    }

    /**
     * Set royalty
     *
     * @param int $royalty
     *
     * @return Project
     */
    public function setRoyalty($royalty)
    {
        $this->royalty = $royalty;

        return $this;
    }

    /**
     * Get royalty
     *
     * @return int
     */
    public function getRoyalty()
    {
        return $this->royalty;
    }

    /**
     * Set royalty_mechanical
     *
     * @param bool $royaltyMechanical
     *
     * @return Project
     */
    public function setRoyaltyMechanical($royaltyMechanical)
    {
        $this->royalty_mechanical = $royaltyMechanical;

        return $this;
    }

    /**
     * Get royalty_mechanical
     *
     * @return bool
     */
    public function getRoyaltyMechanical()
    {
        return $this->royalty_mechanical;
    }

    /**
     * Set royalty_performance
     *
     * @param bool $royaltyPerformance
     *
     * @return Project
     */
    public function setRoyaltyPerformance($royaltyPerformance)
    {
        $this->royalty_performance = $royaltyPerformance;

        return $this;
    }

    /**
     * Get royalty_performance
     *
     * @return bool
     */
    public function getRoyaltyPerformance()
    {
        return $this->royalty_performance;
    }

    /**
     * Set project_type
     *
     * @param string $projectType
     *
     * @return Project
     */
    public function setProjectType($projectType)
    {
        $this->project_type = $projectType;

        return $this;
    }

    /**
     * Get project_type
     *
     * @return string
     */
    public function getProjectType()
    {
        return $this->project_type;
    }

    /**
     * Add user_reviews
     *
     * @param \Vocalizr\AppBundle\Entity\UserReview $userReviews
     *
     * @return Project
     */
    public function addUserReview(\Vocalizr\AppBundle\Entity\UserReview $userReviews)
    {
        $this->user_reviews[] = $userReviews;

        return $this;
    }

    /**
     * Remove user_reviews
     *
     * @param \Vocalizr\AppBundle\Entity\UserReview $userReviews
     */
    public function removeUserReview(\Vocalizr\AppBundle\Entity\UserReview $userReviews)
    {
        $this->user_reviews->removeElement($userReviews);
    }

    /**
     * Get user_reviews
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserReviews()
    {
        return $this->user_reviews;
    }

    /**
     * Add bid_messages
     *
     * @param \Vocalizr\AppBundle\Entity\Message $bidMessages
     *
     * @return Project
     */
    public function addBidMessage(\Vocalizr\AppBundle\Entity\Message $bidMessages)
    {
        $this->bid_messages[] = $bidMessages;

        return $this;
    }

    /**
     * Remove bid_messages
     *
     * @param \Vocalizr\AppBundle\Entity\Message $bidMessages
     */
    public function removeBidMessage(\Vocalizr\AppBundle\Entity\Message $bidMessages)
    {
        $this->bid_messages->removeElement($bidMessages);
    }

    /**
     * Get bid_messages
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getBidMessages()
    {
        return $this->bid_messages;
    }

    /**
     * Add message_threads
     *
     * @param \Vocalizr\AppBundle\Entity\MessageThread $messageThreads
     *
     * @return Project
     */
    public function addMessageThread(\Vocalizr\AppBundle\Entity\MessageThread $messageThreads)
    {
        $this->message_threads[] = $messageThreads;

        return $this;
    }

    /**
     * Remove message_threads
     *
     * @param \Vocalizr\AppBundle\Entity\MessageThread $messageThreads
     */
    public function removeMessageThread(\Vocalizr\AppBundle\Entity\MessageThread $messageThreads)
    {
        $this->message_threads->removeElement($messageThreads);
    }

    /**
     * Get message_threads
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMessageThreads()
    {
        return $this->message_threads;
    }

    /**
     * Set highlight
     *
     * @param bool $highlight
     *
     * @return Project
     */
    public function setHighlight($highlight)
    {
        $this->highlight = $highlight;

        return $this;
    }

    /**
     * Get highlight
     *
     * @return bool
     */
    public function getHighlight()
    {
        return $this->highlight;
    }

    /**
     * Set featured
     *
     * @param bool $featured
     *
     * @return Project
     */
    public function setFeatured($featured)
    {
        $this->featured = $featured;

        return $this;
    }

    /**
     * Get featured
     *
     * @return bool
     */
    public function getFeatured()
    {
        return $this->featured;
    }

    /**
     * @return bool
     */
    public function getMessaging()
    {
        return $this->messaging;
    }

    /**
     * @param bool $messaging
     * @return Project
     */
    public function setMessaging($messaging)
    {
        $this->messaging = $messaging;
        return $this;
    }

    /**
     * @return string
     */
    public function getSsid()
    {
        return $this->ssid;
    }

    /**
     * @param string $ssid
     * @return Project
     */
    public function setSsid($ssid)
    {
        $this->ssid = $ssid;
        return $this;
    }

    /**
     * Set fees
     *
     * @param int $fees
     *
     * @return Project
     */
    public function setFees($fees)
    {
        $this->fees = $fees;

        return $this;
    }

    /**
     * Get fees
     *
     * @return int
     */
    public function getFees()
    {
        return $this->fees;
    }

    /**
     * Set pro_required
     *
     * @param bool $proRequired
     *
     * @return Project
     */
    public function setProRequired($proRequired)
    {
        $this->pro_required = $proRequired;
        $this->lock_to_cert = $proRequired;

        return $this;
    }

    /**
     * Get pro_required
     *
     * @return bool
     */
    public function getProRequired()
    {
        return ($this->lock_to_cert || $this->pro_required);
    }

    /**
     * Set bpm
     *
     * @param string $bpm
     *
     * @return Project
     */
    public function setBpm($bpm)
    {
        $this->bpm = $bpm;

        return $this;
    }

    /**
     * Get bpm
     *
     * @return string
     */
    public function getBpm()
    {
        return $this->bpm;
    }

    /**
     * Set featured_at
     *
     * @param \DateTime $featuredAt
     *
     * @return Project
     */
    public function setFeaturedAt($featuredAt)
    {
        $this->featured_at = $featuredAt;

        return $this;
    }

    /**
     * Get featured_at
     *
     * @return \DateTime
     */
    public function getFeaturedAt()
    {
        return $this->featured_at;
    }

    /**
     * Set hire_user
     *
     * @param \Vocalizr\AppBundle\Entity\UserInfo $hireUser
     *
     * @return Project
     */
    public function setHireUser(\Vocalizr\AppBundle\Entity\UserInfo $hireUser = null)
    {
        $this->hire_user = $hireUser;

        return $this;
    }

    /**
     * Get hire_user
     *
     * @return \Vocalizr\AppBundle\Entity\UserInfo
     */
    public function getHireUser()
    {
        return $this->hire_user;
    }

    /**
     * Set audio_brief
     *
     * @param string $audioBrief
     *
     * @return Project
     */
    public function setAudioBrief($audioBrief)
    {
        $this->audio_brief = $audioBrief;

        return $this;
    }

    /**
     * Get audio_brief
     *
     * @return string
     */
    public function getAudioBrief()
    {
        return $this->audio_brief;
    }

    /**
     * Set audio_brief_click
     *
     * @param int $audioBriefClick
     *
     * @return Project
     */
    public function setAudioBriefClick($audioBriefClick)
    {
        $this->audio_brief_click = $audioBriefClick;

        return $this;
    }

    /**
     * Get audio_brief_click
     *
     * @return int
     */
    public function getAudioBriefClick()
    {
        return $this->audio_brief_click;
    }

    /**
     * Set sfs
     *
     * @param bool $sfs
     *
     * @return Project
     */
    public function setSfs($sfs)
    {
        $this->sfs = $sfs;

        return $this;
    }

    /**
     * Get sfs
     *
     * @return bool
     */
    public function getSfs()
    {
        return $this->sfs;
    }

    /**
     * Set public_voting
     *
     * @param bool $publicVoting
     *
     * @return Project
     */
    public function setPublicVoting($publicVoting)
    {
        $this->public_voting = $publicVoting;

        return $this;
    }

    /**
     * Get public_voting
     *
     * @return bool
     */
    public function getPublicVoting()
    {
        return $this->public_voting;
    }

    /**
     * @return bool
     */
    public function isFullyRefunded()
    {
        return $this->fullyRefunded;
    }

    /**
     * @param bool $fullyRefunded
     * @return Project
     */
    public function setFullyRefunded($fullyRefunded)
    {
        $this->fullyRefunded = $fullyRefunded;
        return $this;
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
     * @return string
     * @throws \Exception
     */
    public function getGigAge()
    {
        $interval = $this->created_at->diff(new \DateTime());
        if ($this->awarded_at) {
            $interval = $this->awarded_at->diff($this->created_at);
        }

        return $interval->days . 'd ' . $interval->h . 'h';
    }

    /**
     * @return string|null
     */
    public function getPaymentStatus()
    {
        return $this->payment_status ? $this->payment_status : self::PAYMENT_STATUS_PENDING;
    }

    /**
     * @param string|null $payment_status
     * @return Project
     */
    public function setPaymentStatus($payment_status)
    {
        $this->payment_status = $payment_status;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getDaysExtended()
    {
        return $this->days_extended;
    }

    /**
     * @param int|null $days_extended
     * @return Project
     */
    public function setDaysExtended($days_extended)
    {
        $this->days_extended = $days_extended;
        return $this;
    }
}