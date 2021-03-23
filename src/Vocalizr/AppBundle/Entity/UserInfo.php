<?php

namespace Vocalizr\AppBundle\Entity;

require_once __DIR__ . '/../../../../vendor/simpleimage/lib/SimpleImage.php';

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Vocalizr\AppBundle\Validator\Constraints\CustomRegex;

/**
 * @ORM\Entity(repositoryClass="Vocalizr\AppBundle\Repository\UserInfoRepository")
 * @ORM\HasLifecycleCallbacks()
 * @UniqueEntity(fields="email", message="Email already exists")
 * @UniqueEntity(fields="username", message="Username already exists", groups={"register_finalize", "register_step1"})
 * @ORM\Table(name="user_info", indexes={
 *     @ORM\Index(name="email_idx", columns={"email"}),
 *     @ORM\Index(name="username_idx", columns={"username"}),
 *     @ORM\Index(name="last_login_idx", columns={"last_login"}),
 *     @ORM\Index(name="rating_idx", columns={"rating", "rated_count", "last_login"}),
 *     @ORM\Index(name="date_registered_idx", columns={"date_registered"}),
 * })
 */
class UserInfo implements UserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $soundcloud_id = null;

    /**
     * @ORM\Column(length=255, nullable=true)
     */
    protected $soundcloud_access_token = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $soundcloud_set_id = null;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $soundcloud_username = null;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     * @Assert\NotBlank(message="Username is required", groups={"register_finalize"})
     *
     * @Assert\Length(
     *      min = "3",
     *      max = "15",
     *      minMessage = "Username must be at least {{ limit }} characters|Username must be at least {{ limit }} characters",
     *      maxMessage = "Username cannot be longer than {{ limit }} characters|Username cannot be longer than {{ limit }} characters",
     *      groups={"register_step1"}
     * )
     * @Assert\Regex(
     *     pattern="/[^0-9A-Za-z_]/",
     *     match=false,
     *     message="Username can only contain alphanumeric characters and underscores",
     *     groups={"register_step1"}
     * )
     */
    protected $username;

    /**
     * @ORM\Column(type="string", length=120, nullable=true)
     * @Assert\Regex(
     *     pattern="/[^\x20-\x7e]/",
     *     match=false,
     *     message="Display name can only contain default characters",
     *     groups={"user_edit"}
     * )
     */
    protected $display_name = null;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     * @Assert\NotBlank(message="First name is required", groups={"register_step1"})
     */
    protected $first_name = null;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     * @Assert\NotBlank(message="Last name is required", groups={"register_step1"})
     */
    protected $last_name = null;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Assert\NotBlank(message="Email is required", groups={"register_step1"})
     * @Assert\Email(message="Email format was invalid", groups={"register_step1"})
     * @Assert\Regex(
     *     message="Denied",
     *     groups={"register_step1"},
     *     match=false,
     *     pattern="/^.*(googleappmail\.com|hotmail-s\.com)$/mu",
     * )
     */
    protected $email = null;

    /**
     * @ORM\Column(type="string", length=40, nullable=true)
     * @Assert\NotBlank(message="Password is required", groups={"register_step1", "password_change"})
     */
    protected $password = null;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    protected $salt = null;

    /**
     * @ORM\Column(type="string", length=64, nullable=true)
     */
    protected $avatar = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @CustomRegex(
     *     pattern="/[^\x20-\x7e\s\p{P}]+/mu",
     *     match=false,
     *     message="Profile can only contain default characters. Invalid characters: {{ values }}",
     *     groups={"user_edit"}
     * )
     */
    protected $profile = null;

    /**
     * @ORM\Column(type="string", length=1, nullable=true)
     * @Assert\NotBlank(message="Please select your gender", groups={"register_finalize"})
     */
    protected $gender = null;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     */
    protected $state = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\NotBlank(message="Invalid city", groups={"register_finalize_old"})
     * @Assert\Regex(
     *     pattern="/[^\ A-Za-z_\-]/",
     *     match=false,
     *     message="Only english characters allowd for City field",
     *     groups={"user_edit"}
     * )
     */
    protected $city = null;

    /**
     * @ORM\Column(type="float", precision=10, scale=6, nullable=true)
     */
    protected $location_lat = null;

    /**
     * @ORM\Column(type="float", precision=10, scale=6, nullable=true)
     */
    protected $location_lng = null;

    /**
     * @ORM\Column(type="boolean", nullable=true)
     */
    protected $studio_access = false;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $microphone = null;

    /**
     * @ORM\Column(type="integer", length=11, nullable=true)
     */
    protected $vocalist_fee = null;

    /**
     * @ORM\Column(type="integer", length=11, nullable=true)
     */
    protected $producer_fee = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $last_activity = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $last_login = null;

    /**
     * @ORM\Column(length=40)
     */
    protected $unique_str = null;

    /**
     * @ORM\Column(type="integer", length=11)
     */
    protected $wallet = 0;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $date_registered;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $completed_profile = null;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $is_producer = false;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $is_vocalist = false;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $is_songwriter = false;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $email_confirmed = false;

    /**
     * @ORM\Column(type="string", length=2, nullable=true)
     * @Assert\NotBlank(message="Required", groups={"user_edit"})
     * @Assert\Regex(
     *     pattern="/[^\ A-Za-z_\-]/",
     *     match=false,
     *     message="Only english characters allowd for Country field",
     *     groups={"user_edit"}
     * )
     */
    protected $country = null;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $is_active = false;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $date_activated;

    /**
     * @ORM\Column(type="string", length=50, nullable=true)
     */
    protected $referral_code = null;

    /**
     * Average Rating
     *
     * @ORM\Column(type="decimal", scale=2, precision=9)
     */
    protected $rating = 0;

    /**
     * Total amount of times they have been rated
     *
     * @ORM\Column(type="integer", length=5)
     */
    protected $rated_count = 0;

    /**
     * Total number of rating value
     *
     * @ORM\Column(type="integer", length=8)
     */
    protected $rating_total = 0;

    /**
     * Average Vocalist Rating
     *
     * @ORM\Column(type="decimal", scale=2, precision=9, options={"default": 0})
     */
    protected $vocalist_rating = 0;

    /**
     * Total amount of times they have been rated as vocalists
     *
     * @ORM\Column(type="integer", length=5, options={"default": 0})
     */
    protected $vocalist_rated_count = 0;

    /**
     * Average Producer Rating
     *
     * @ORM\Column(type="decimal", scale=2, precision=9, options={"default": 0})
     */
    protected $producer_rating = 0;

    /**
     * Total amount of times they have been rated as employer
     *
     * @ORM\Column(type="integer", length=5, options={"default": 0})
     */
    protected $producer_rated_count = 0;

    /**
     * Average Producer Rating
     *
     * @ORM\Column(type="decimal", scale=2, precision=9, options={"default": 0})
     */
    protected $employer_rating = 0;

    /**
     * Total amount of times they have been rated as employer
     *
     * @ORM\Column(type="integer", length=5, options={"default": 0})
     */
    protected $employer_rated_count = 0;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    public $path;

    /**
     * @Assert\File(maxSize="6000000")
     */
    public $file;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $unread_project_activity = false;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $unseen_project_invitation = false;

    /**
     * @ORM\Column(type="integer", length=8)
     */
    protected $num_unread_messages = 0;

    /**
     * @ORM\Column(type="integer", length=8)
     */
    protected $num_notifications = 0;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $soundcloud_register = false;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $is_admin = false;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $is_certified = false;

    /**
     * @ORM\Column(type="string", length=32, nullable=true)
     */
    protected $stripe_cust_id = null;

    /**
     * @ORM\Column(type="integer")
     */
    protected $connect_count = 0;

    /**
     * @ORM\Column(name="user_spotify_id", type="string", nullable=true)
     */
    protected $userSpotifyId;

    /**
     * @ORM\Column(name="login_ip", type="string", nullable=true)
     */
    protected $loginIp;

    /**
     * @ORM\Column(name="register_ip", type="string", nullable=true)
     */
    protected $registerIp;

    /**
     * @var bool
     *
     * @ORM\Column(name="registration_finished", type="boolean", options={"default": true})
     */
    protected $registration_finished = false;

    /**
     * @var string|null
     *
     * @ORM\Column(name="withdraw_email", type="string", nullable=true)
     */
    protected $withdrawEmail;

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean", options={"default": false})
     */
    protected $proProfileEnabled = false;

    /** _______________________
     * | Relationships         |
     * |_______________________|
     */

    /**
     * User preferences
     *
     * @ORM\OneToOne(targetEntity="UserPref", mappedBy="user_info")
     */
    protected $user_pref;

    /**
     * @var UserProProfile|null
     *
     * @ORM\OneToOne(
     *     targetEntity="Vocalizr\AppBundle\Entity\UserProProfile",
     *     mappedBy="userInfo",
     *     cascade={"persist", "remove"},
     * )
     */
    protected $proProfile;

    /**
     * Subscription Plan
     *
     * @ORM\ManyToOne(targetEntity="SubscriptionPlan")
     * @ORM\JoinColumn(name="subscription_plan_id", referencedColumnName="id")
     */
    protected $subscription_plan = null;

    /**
     * User setting
     *
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="UserSetting", mappedBy="user_info")
     */
    protected $user_settings;

    /**
     * User marketplace items
     *
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="MarketplaceItem", mappedBy="user_info")
     */
    protected $marketplace_items;

    /**
     * User projects
     *
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="Project", mappedBy="user_info")
     */
    protected $projects;

    /**
     * User project bids
     *
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="ProjectBid", mappedBy="user_info")
     */
    protected $project_bids;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="ProjectInvite", mappedBy="user_info")
     */
    protected $project_invites;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="ProjectFeed", mappedBy="user_info")
     */
    protected $project_feeds;

    /**
     * Reviews on user
     *
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="UserReview", mappedBy="user_info")
     */
    protected $user_reviews;

    /**
     * Reviews that user has made
     *
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="UserReview", mappedBy="reviewed_by")
     */
    protected $reviewed_users;

    /**
     * User messages sent
     *
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="Message", mappedBy="user_info")
     */
    protected $messages_sent;

    /**
     * User messages received
     *
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="Message", mappedBy="to_user_info")
     */
    protected $messages_received;

    /**
     * Voice tags assigned to user
     *
     * @var Collection|UserVoiceTag[]
     *
     * @ORM\OneToMany(targetEntity="UserVoiceTag", mappedBy="user_info")
     */
    protected $user_voice_tags;

    /**
     * Vocal characteristic assigned to user
     *
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="UserVocalCharacteristic", mappedBy="user_info")
     */
    protected $user_vocal_characteristics;

    /**
     * Vocal style assigned to user
     *
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="UserVocalStyle", mappedBy="user_info")
     */
    protected $user_vocal_styles;

    /**
     * Audio that user has uploaded
     *
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="UserAudio", mappedBy="user_info")
     * @ORM\OrderBy({"sort_order" = "ASC", "default_audio" = "DESC", "created_at" = "DESC"})
     */
    protected $user_audio;

    /**
     * Audio that user has uploaded for a project
     *
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="ProjectAudio", mappedBy="user_info")
     */
    protected $project_audio;

    /**
     * Audio that user has uploaded for a marketplace_item
     *
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="MarketplaceItemAudio", mappedBy="user_info")
     */
    protected $marketplace_item_audio;

    /**
     * Private Messages user has sent
     *
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="UserMessage", mappedBy="from")
     */
    protected $user_messages_sent;

    /**
     * Private messages sent to the user
     *
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="UserMessage", mappedBy="to")
     */
    protected $user_messages;

    /**
     * Searches a user has made
     *
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="Search", mappedBy="user_info")
     */
    protected $searches;

    /**
     * @ORM\OneToOne(targetEntity="UserStat")
     */
    protected $user_stat;

    /**
     * @var Collection|Genre[]
     *
     * @ORM\ManyToMany(targetEntity="Genre")
     * @ORM\JoinTable(name="user_genre",
     *      joinColumns={@ORM\JoinColumn(name="user_info_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="genre_id", referencedColumnName="id")}
     *      )
     */
    protected $genres;

    /**
     * @var Collection
     *
     * @ORM\ManyToMany(targetEntity="UserInfo", mappedBy="favorites")
     */
    protected $favorite_by;

    /**
     * @var Collection
     *
     * @ORM\ManyToMany(targetEntity="UserInfo", inversedBy="favorite_by")
     * @ORM\JoinTable(name="user_favorite",
     *      joinColumns={@ORM\JoinColumn(name="user_info_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="favorite_user_info_id", referencedColumnName="id")}
     *      )
     */
    protected $favorites;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="UserSubscription", mappedBy="user_info")
     */
    protected $user_subscriptions;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="UserTransaction", mappedBy="user_info")
     */
    protected $user_transactions;

    /**
     * @var Collection|UserWithdraw[]
     *
     * @ORM\OneToMany(targetEntity="Vocalizr\AppBundle\Entity\UserWithdraw", mappedBy="user_info")
     */
    protected $user_withdraws;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="UserWalletTransaction", mappedBy="user_info")
     */
    protected $user_wallet_transactions;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="UserConnectInvite", mappedBy="to")
     */
    protected $user_connect_invites;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="UserConnectInvite", mappedBy="from")
     */
    protected $user_connect_invites_sent;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="UserConnect", mappedBy="to")
     */
    protected $connections;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="UserBlock", mappedBy="user_info")
     */
    protected $user_blocks;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="UserVideo", mappedBy="userInfo", cascade={"persist", "remove"})
     */
    protected $userVideos;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="UserSpotifyPlaylist", mappedBy="userInfo", cascade={"persist", "remove"})
     */
    protected $userSpotifyPlaylists;

    /**
     * @var Collection|UserInfoLanguage
     *
     * @ORM\OneToMany(targetEntity="UserInfoLanguage", mappedBy="userInfo", cascade={"persist", "remove"})
     */
    protected $userLanguages;

    /**
     * @var Country
     *
     * @ORM\ManyToOne(targetEntity="Country")
     * @ORM\JoinColumn(name="country_id", referencedColumnName="id", nullable=true)
     */
    protected $userCountry;

    /**
     * @var ArrayCollection|UserActionAudit[]
     *
     * @ORM\OneToMany(targetEntity="Vocalizr\AppBundle\Entity\UserActionAudit", mappedBy="user")
     */
    protected $audits;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="Vocalizr\AppBundle\Entity\HintSkip", mappedBy="user", cascade={"remove"})
     */
    protected $skippedHints;

    /**
     * @var UserTotalOnline|null
     *
     * @ORM\OneToOne(targetEntity="Vocalizr\AppBundle\Entity\UserTotalOnline", inversedBy="user")
     * @ORM\JoinColumn(name="user_online_id")
     */
    protected $userOnline;

    /**
     * @var ArrayCollection|UserStripeIdentity[]
     *
     * @ORM\OneToMany(targetEntity="Vocalizr\AppBundle\Entity\UserStripeIdentity", mappedBy="user")
     */
    protected $userIdentity;

    /**
     * @ORM\Column(type="boolean", nullable=true, options={"default": true})
     */
    protected $getCertifiedMailSend = false;

    /** _______________________
     * | Not mapped fields     |
     * |_______________________|
     */

    /**
     * @var bool
     */
    private $activationEventSuppressed = false;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->user_reviews       = new ArrayCollection();
        $this->reviewed_users     = new ArrayCollection();
        $this->user_audio         = new ArrayCollection();
        $this->projects           = new ArrayCollection();
        $this->user_messages_sent = new ArrayCollection();
        $this->user_messages      = new ArrayCollection();
        $this->searches           = new ArrayCollection();
        $this->user_subscriptions = new ArrayCollection();
        $this->skippedHints       = new ArrayCollection();
        $this->audits             = new ArrayCollection();
        $this->user_withdraws     = new ArrayCollection();
        $this->userIdentity       = new ArrayCollection();
        $this->unique_str         = uniqid('u', true);
    }

    public function __sleep()
    {
        return ['id'];
    }

    public function __toString()
    {
        return (string) $this->getDisplayName();
    }

    public function __toJson()
    {
        return json_encode([
            'id'          => $this->id,
            'displayName' => $this->getDisplayName(),
        ], true);
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        if (php_sapi_name() == 'cli') {
        } else {
            $this->setLoginIp(!empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']);
            $this->setRegisterIp(!empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']);
        }
    }

    /**
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->last_activity = new \DateTime();
    }

    /**
     * Get amount in dollars.
     * Converts cents to dollars
     *
     * @return float
     */
    public function getWalletDollars()
    {
        return number_format($this->wallet / 100, 2, '.', ',');
    }

    /**
     * Get username or first name.
     * If first name is avail, then return first name.
     * Otherwise return username
     *
     * @return string
     */
    public function getUsernameOrFirstName()
    {
        if (!empty($this->first_name)) {
            return $this->first_name;
        }
        return $this->getUsernameOrDisplayName();
    }

    /**
     * Get username or display name
     * If display name is avail, then return display name.
     * Otherwise return username
     *
     * @return string
     */
    public function getUsernameOrDisplayName()
    {
        if (!empty($this->display_name)) {
            return $this->display_name;
        }
        return $this->username;
    }

    /**
     * Clean description of links and emails etc
     */
    public function cleanProfile()
    {
        $pattern = "/[^@\s]*@[^@\s]*\.[^@\s]*/";
        $this->setProfile(preg_replace($pattern, '', $this->profile));
        $pattern = "/[a-zA-Z]*[:\/\/]*[A-Za-z0-9\-_]+\.+[A-Za-z0-9\.\/%&=\?\-_]+/i";
        $this->setProfile(preg_replace($pattern, '', $this->getProfile()));
        $this->setProfile(str_replace(['skype', 'facebook', 'gmail', 'twitter', 'dot com', 'dotcom'], '', $this->profile));
    }

    /**
     * Init salt
     */
    public function initSalt()
    {
        $this->salt = md5(time() + rand(9, 999999));
    }

    public function getFullName()
    {
        if (empty($this->first_name)) {
            return '';
        }
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Check if user is subscribed or not
     *
     * @return bool
     */
    public function isSubscribed()
    {
        $plan = $this->getSubscriptionPlan();
        if ($plan && $plan->getStaticKey() !== SubscriptionPlan::PLAN_FREE) {
            return true;
        }

        return false;
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
     * Set username
     *
     * @param string $username
     *
     * @return UserInfo
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set first_name
     *
     * @param string $firstName
     *
     * @return UserInfo
     */
    public function setFirstName($firstName)
    {
        $this->first_name = $firstName;

        return $this;
    }

    /**
     * Get first_name
     *
     * @return string
     */
    public function getFirstName()
    {
        return $this->first_name;
    }

    /**
     * Set last_name
     *
     * @param string $lastName
     *
     * @return UserInfo
     */
    public function setLastName($lastName)
    {
        $this->last_name = $lastName;

        return $this;
    }

    /**
     * Get last_name
     *
     * @return string
     */
    public function getLastName()
    {
        return $this->last_name;
    }

    /**
     * Set email
     *
     * @param string $email
     *
     * @return UserInfo
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set password
     *
     * @param string $password
     *
     * @return UserInfo
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set salt
     *
     * @param string $salt
     *
     * @return UserInfo
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * Get salt
     *
     * @return string
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * Set last_activity
     *
     * @param \DateTime $lastActivity
     *
     * @return UserInfo
     */
    public function setLastActivity($lastActivity)
    {
        $this->last_activity = $lastActivity;

        return $this;
    }

    /**
     * Get last_activity
     *
     * @return \DateTime
     */
    public function getLastActivity()
    {
        return $this->last_activity;
    }

    /**
     * Set last_login
     *
     * @param \DateTime $lastLogin
     *
     * @return UserInfo
     */
    public function setLastLogin($lastLogin)
    {
        $this->last_login = $lastLogin;

        return $this;
    }

    /**
     * Get last_login
     *
     * @return \DateTime
     */
    public function getLastLogin()
    {
        return $this->last_login;
    }

    /**
     * Set date_registered
     *
     * @param \DateTime $dateRegistered
     *
     * @return UserInfo
     */
    public function setDateRegistered($dateRegistered)
    {
        $this->date_registered = $dateRegistered;

        return $this;
    }

    /**
     * Get date_registered
     *
     * @return \DateTime
     */
    public function getDateRegistered()
    {
        return $this->date_registered;
    }

    /**
     * Set is_producer
     *
     * @param bool $isProducer
     *
     * @return UserInfo
     */
    public function setIsProducer($isProducer)
    {
        $this->is_producer = $isProducer;

        return $this;
    }

    /**
     * Get is_producer
     *
     * @return bool
     */
    public function getIsProducer()
    {
        return $this->is_producer;
    }

    /**
     * Set is_vocalist
     *
     * @param bool $isVocalist
     *
     * @return UserInfo
     */
    public function setIsVocalist($isVocalist)
    {
        $this->is_vocalist = $isVocalist;

        return $this;
    }

    /**
     * Get is_vocalist
     *
     * @return bool
     */
    public function getIsVocalist()
    {
        return $this->is_vocalist;
    }

    /**
     * @return bool
     */
    public function isVocalistAndProducer()
    {
        return ($this->getIsVocalist() && $this->getIsProducer());
    }

    /**
     * Set is_songwriter
     *
     * @param bool $isSongwriter
     *
     * @return UserInfo
     */
    public function setIsSongwriter($isSongwriter)
    {
        $this->is_songwriter = $isSongwriter;

        return $this;
    }

    /**
     * Get is_songwriter
     *
     * @return bool
     */
    public function getIsSongwriter()
    {
        return $this->is_songwriter;
    }

    /**
     * Set email_confirmed
     *
     * @param bool $emailConfirmed
     *
     * @return UserInfo
     */
    public function setEmailConfirmed($emailConfirmed)
    {
        $this->email_confirmed = $emailConfirmed;

        return $this;
    }

    /**
     * Get email_confirmed
     *
     * @return bool
     */
    public function getEmailConfirmed()
    {
        return $this->email_confirmed;
    }

    /**
     * Set is_active
     *
     * @param bool $isActive
     *
     * @return UserInfo
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
     * Add user_reviews
     *
     * @param UserReview $userReviews
     *
     * @return UserInfo
     */
    public function addUserReview(UserReview $userReviews)
    {
        $this->user_reviews[] = $userReviews;

        return $this;
    }

    /**
     * Remove user_reviews
     *
     * @param UserReview $userReviews
     */
    public function removeUserReview(UserReview $userReviews)
    {
        $this->user_reviews->removeElement($userReviews);
    }

    /**
     * Get user_reviews
     *
     * @return Collection|UserReview[]
     */
    public function getUserReviews()
    {
        return $this->user_reviews;
    }

    /**
     * Add reviewed_users
     *
     * @param UserReview $reviewedUsers
     *
     * @return UserInfo
     */
    public function addReviewedUser(UserReview $reviewedUsers)
    {
        $this->reviewed_users[] = $reviewedUsers;

        return $this;
    }

    /**
     * Remove reviewed_users
     *
     * @param UserReview $reviewedUsers
     */
    public function removeReviewedUser(UserReview $reviewedUsers)
    {
        $this->reviewed_users->removeElement($reviewedUsers);
    }

    /**
     * Get reviewed_users
     *
     * @return Collection|UserReview[]
     */
    public function getReviewedUsers()
    {
        return $this->reviewed_users;
    }

    /**
     * Add user_audio
     *
     * @param UserAudio $userAudio
     *
     * @return UserInfo
     */
    public function addUserAudio(UserAudio $userAudio)
    {
        $this->user_audio[] = $userAudio;

        return $this;
    }

    /**
     * Remove user_audio
     *
     * @param UserAudio $userAudio
     */
    public function removeUserAudio(UserAudio $userAudio)
    {
        $this->user_audio->removeElement($userAudio);
    }

    /**
     * Get user_audio
     *
     * @return Collection|UserAudio[]
     */
    public function getUserAudio()
    {
        return $this->user_audio;
    }

    /**
     * Add projects
     *
     * @param Project $project
     *
     * @return UserInfo
     */
    public function addProject(Project $project)
    {
        $this->projects[] = $project;

        return $this;
    }

    /**
     * Remove projects
     *
     * @param Project $project
     */
    public function removeProject(Project $project)
    {
        $this->projects->removeElement($project);
    }

    /**
     * Get projects
     *
     * @return Collection|Project[]
     */
    public function getProjects()
    {
        return $this->projects;
    }

    /**
     * Add user_messages_sent
     *
     * @param UserMessage $userMessagesSent
     *
     * @return UserInfo
     */
    public function addUserMessagesSent(UserMessage $userMessagesSent)
    {
        $this->user_messages_sent[] = $userMessagesSent;

        return $this;
    }

    /**
     * Remove user_messages_sent
     *
     * @param UserMessage $userMessagesSent
     */
    public function removeUserMessagesSent(UserMessage $userMessagesSent)
    {
        $this->user_messages_sent->removeElement($userMessagesSent);
    }

    /**
     * Get user_messages_sent
     *
     * @return Collection
     */
    public function getUserMessagesSent()
    {
        return $this->user_messages_sent;
    }

    /**
     * Add user_messages
     *
     * @param UserMessage $userMessages
     *
     * @return UserInfo
     */
    public function addUserMessage(UserMessage $userMessages)
    {
        $this->user_messages[] = $userMessages;

        return $this;
    }

    /**
     * Remove user_messages
     *
     * @param UserMessage $userMessages
     */
    public function removeUserMessage(UserMessage $userMessages)
    {
        $this->user_messages->removeElement($userMessages);
    }

    /**
     * Get user_messages
     *
     * @return Collection
     */
    public function getUserMessages()
    {
        return $this->user_messages;
    }

    /**
     * Get searches
     *
     * @return Collection
     */
    public function getSearches()
    {
        return $this->searches;
    }

    /**
     * Set user_stat
     *
     * @param UserStat $userStat
     *
     * @return UserInfo
     */
    public function setUserStat(UserStat $userStat = null)
    {
        $this->user_stat = $userStat;

        return $this;
    }

    /**
     * Get user_stat
     *
     * @return UserStat
     */
    public function getUserStat()
    {
        return $this->user_stat;
    }

    /**
     * Add user_subscriptions
     *
     * @param UserSubscription $userSubscriptions
     *
     * @return UserInfo
     */
    public function addUserSubscription(UserSubscription $userSubscriptions)
    {
        $this->user_subscriptions[] = $userSubscriptions;

        return $this;
    }

    /**
     * Remove user_subscriptions
     *
     * @param UserSubscription $userSubscriptions
     */
    public function removeUserSubscription(UserSubscription $userSubscriptions)
    {
        $this->user_subscriptions->removeElement($userSubscriptions);
    }

    /**
     * Get user_subscriptions
     *
     * @return Collection|UserSubscription[]
     */
    public function getUserSubscriptions()
    {
        return $this->user_subscriptions;
    }

    /**
     * Add user_transactions
     *
     * @param UserTransaction $userTransactions
     *
     * @return UserInfo
     */
    public function addUserTransaction(UserTransaction $userTransactions)
    {
        $this->user_transactions[] = $userTransactions;

        return $this;
    }

    /**
     * Remove user_transactions
     *
     * @param UserTransaction $userTransactions
     */
    public function removeUserTransaction(UserTransaction $userTransactions)
    {
        $this->user_transactions->removeElement($userTransactions);
    }

    /**
     * Get user_transactions
     *
     * @return Collection
     */
    public function getUserTransactions()
    {
        return $this->user_transactions;
    }

    /**
     * @inheritDoc
     */
    public function getRoles()
    {
        return ['ROLE_USER'];
    }

    /**
     * @inheritDoc
     */
    public function eraseCredentials()
    {
    }

    /**
     * @inheritDoc
     */
    public function equals(UserInterface $user)
    {
        return $this->email === $user->getEmail();
    }

    /**
     * Set unique_str
     *
     * @param string $uniqueStr
     *
     * @return UserInfo
     */
    public function setUniqueStr($uniqueStr)
    {
        $this->unique_str = $uniqueStr;

        return $this;
    }

    /**
     * Get unique_str
     *
     * @return string
     */
    public function getUniqueStr()
    {
        return $this->unique_str;
    }

    /**
     * Set soundcloud_access_token
     *
     * @param string $soundcloudAccessToken
     *
     * @return UserInfo
     */
    public function setSoundcloudAccessToken($soundcloudAccessToken)
    {
        $this->soundcloud_access_token = $soundcloudAccessToken;

        return $this;
    }

    /**
     * Get soundcloud_access_token
     *
     * @return string
     */
    public function getSoundcloudAccessToken()
    {
        return $this->soundcloud_access_token;
    }

    /**
     * Add searches
     *
     * @param Search $searches
     *
     * @return UserInfo
     */
    public function addSearche(Search $searches)
    {
        $this->searches[] = $searches;

        return $this;
    }

    /**
     * Remove searches
     *
     * @param Search $searches
     */
    public function removeSearche(Search $searches)
    {
        $this->searches->removeElement($searches);
    }

    /**
     * Set profile
     *
     * @param string $profile
     *
     * @return UserInfo
     */
    public function setProfile($profile)
    {
        $this->profile = $profile;

        return $this;
    }

    /**
     * Get profile
     *
     * @return string
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * Set avatar
     *
     * @param string $avatar
     *
     * @return UserInfo
     */
    public function setAvatar($avatar)
    {
        $this->avatar = $avatar;

        return $this;
    }

    /**
     * Get avatar
     *
     * @return string
     */
    public function getAvatar()
    {
        return $this->avatar;
    }

    /**
     * Set state
     *
     * @param string $state
     *
     * @return UserInfo
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
     * Set country
     *
     * @param string $country
     *
     * @return UserInfo
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
     * Set studio_access
     *
     * @param bool $studioAccess
     *
     * @return UserInfo
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
     * Set gender
     *
     * @param string $gender
     *
     * @return UserInfo
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

    public function getAbsolutePath()
    {
        return null === $this->path
            ? null
            : $this->getUploadRootDir() . '/' . $this->path;
    }

    public function getWebPath()
    {
        return null === $this->path
            ? null
            : $this->getUploadDir() . '/' . $this->path;
    }

    public function getThumbnailWebPath($size, $default = 'images/default_avatar.svg')
    {
        return null === $this->path
            ? $default
            : "{$this->getUploadDir()}/$size/{$this->path}";
    }

    public function getUploadRootDir()
    {
        // the absolute directory path where uploaded
        // documents should be saved
        return __DIR__ . '/../../../../web/' . $this->getUploadDir();
    }

    public function getUploadDir()
    {
        // get rid of the __DIR__ so it doesn't screw up
        // when displaying uploaded doc/image in the view.
        return 'uploads/avatar/';
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function preUpload()
    {
        if (null !== $this->file) {
            // do whatever you want to generate a unique name
            $filename   = sha1(uniqid(mt_rand(), true));
            $this->path = $filename . '.' . $this->file->guessExtension();
        }
    }

    /**
     * @ORM\PostPersist()
     * @ORM\PostUpdate()
     */
    public function upload()
    {
        if (null === $this->file) {
            return;
        }

        // if there is an error when moving the file, an exception will
        // be automatically thrown by move(). This will properly prevent
        // the entity from being persisted to the database on error
        $this->file->move($this->getUploadRootDir(), $this->path);

        // Create image sizes
        // resize large image
        $simpleImage = new \SimpleImage();
        $simpleImage->load($this->getWebPath());
        $simpleImage->square_crop(180);
        $simpleImage->save($this->getUploadRootDir() . '/large/' . $this->path, null, 75);

        // resize image
        $simpleImage = new \SimpleImage();
        $simpleImage->load($this->getWebPath());
        $simpleImage->square_crop(100);
        $simpleImage->save($this->getUploadRootDir() . '/small/' . $this->path, null, 75);

        unset($this->file);
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
            $file = $this->getUploadRootDir() . '/large/' . $this->path;
            if (file_exists($file)) {
                unlink($file);
            }
            $file = $this->getUploadRootDir() . '/small/' . $this->path;
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Set path
     *
     * @param string $path
     *
     * @return UserInfo
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
     * Set city
     *
     * @param string $city
     *
     * @return UserInfo
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
     * Weather or not they have connected to sound cloud
     *
     * @return bool
     */
    public function hasSoundCloud()
    {
        return $this->soundcloud_access_token;
    }

    /**
     * Set soundcloud_set_id
     *
     * @param int $soundcloudSetId
     *
     * @return UserInfo
     */
    public function setSoundcloudSetId($soundcloudSetId)
    {
        $this->soundcloud_set_id = $soundcloudSetId;

        return $this;
    }

    /**
     * Get soundcloud_set_id
     *
     * @return int
     */
    public function getSoundcloudSetId()
    {
        return $this->soundcloud_set_id;
    }

    /**
     * Set soundcloud_id
     *
     * @param int $soundcloudId
     *
     * @return UserInfo
     */
    public function setSoundcloudId($soundcloudId)
    {
        $this->soundcloud_id = $soundcloudId;

        return $this;
    }

    /**
     * Get soundcloud_id
     *
     * @return int
     */
    public function getSoundcloudId()
    {
        return $this->soundcloud_id;
    }

    /**
     * Add genres
     *
     * @param Genre $genres
     *
     * @return UserInfo
     */
    public function addGenre(Genre $genres)
    {
        $this->genres[] = $genres;

        return $this;
    }

    /**
     * Remove genres
     *
     * @param Genre $genres
     */
    public function removeGenre(Genre $genres)
    {
        $this->genres->removeElement($genres);
    }

    /**
     * Get genres
     *
     * @return Collection
     */
    public function getGenres()
    {
        return $this->genres;
    }

    /**
     * Add user_voice_tags
     *
     * @param UserVoiceTag $userVoiceTags
     *
     * @return UserInfo
     */
    public function addUserVoiceTag(UserVoiceTag $userVoiceTags)
    {
        $this->user_voice_tags[] = $userVoiceTags;

        return $this;
    }

    /**
     * Remove user_voice_tags
     *
     * @param UserVoiceTag $userVoiceTags
     */
    public function removeUserVoiceTag(UserVoiceTag $userVoiceTags)
    {
        $this->user_voice_tags->removeElement($userVoiceTags);
    }

    /**
     * Get user_voice_tags
     *
     * @return Collection|UserVoiceTag[]
     */
    public function getUserVoiceTags()
    {
        return $this->user_voice_tags;
    }

    /**
     * Add vocalStyles
     *
     * @param VocalStyle $vocalStyles
     *
     * @return UserInfo
     */
    public function addVocalStyle(VocalStyle $vocalStyles)
    {
        $this->vocalStyles[] = $vocalStyles;

        return $this;
    }

    /**
     * Remove vocalStyles
     *
     * @param VocalStyle $vocalStyles
     */
    public function removeVocalStyle(VocalStyle $vocalStyles)
    {
        $this->vocalStyles->removeElement($vocalStyles);
    }

    /**
     * Get vocalStyles
     *
     * @return Collection
     */
    public function getVocalStyles()
    {
        return $this->vocalStyles;
    }

    /**
     * Set soundcloud_username
     *
     * @param string $soundcloudUsername
     *
     * @return UserInfo
     */
    public function setSoundcloudUsername($soundcloudUsername)
    {
        $this->soundcloud_username = $soundcloudUsername;

        return $this;
    }

    /**
     * Get soundcloud_username
     *
     * @return string
     */
    public function getSoundcloudUsername()
    {
        return $this->soundcloud_username;
    }

    /**
     * Add vocalCharacteristics
     *
     * @param VocalCharacteristic $vocalCharacteristics
     *
     * @return UserInfo
     */
    public function addVocalCharacteristic(VocalCharacteristic $vocalCharacteristics)
    {
        $this->vocalCharacteristics[] = $vocalCharacteristics;

        return $this;
    }

    /**
     * Remove vocalCharacteristics
     *
     * @param VocalCharacteristic $vocalCharacteristics
     */
    public function removeVocalCharacteristic(VocalCharacteristic $vocalCharacteristics)
    {
        $this->vocalCharacteristics->removeElement($vocalCharacteristics);
    }

    /**
     * Get vocalCharacteristics
     *
     * @return Collection
     */
    public function getVocalCharacteristics()
    {
        return $this->vocalCharacteristics;
    }

    /**
     * Set referral_code
     *
     * @param string $referralCode
     *
     * @return UserInfo
     */
    public function setReferralCode($referralCode)
    {
        $this->referral_code = $referralCode;

        return $this;
    }

    /**
     * Get referral_code
     *
     * @return string
     */
    public function getReferralCode()
    {
        return $this->referral_code;
    }

    /**
     * Add project_bids
     *
     * @param ProjectBid $projectBids
     *
     * @return UserInfo
     */
    public function addProjectBid(ProjectBid $projectBids)
    {
        $this->project_bids[] = $projectBids;

        return $this;
    }

    /**
     * Remove project_bids
     *
     * @param ProjectBid $projectBids
     */
    public function removeProjectBid(ProjectBid $projectBids)
    {
        $this->project_bids->removeElement($projectBids);
    }

    /**
     * Get project_bids
     *
     * @return Collection
     */
    public function getProjectBids()
    {
        return $this->project_bids;
    }

    /**
     * Add favorite_by
     *
     * @param UserInfo $favoriteBy
     *
     * @return UserInfo
     */
    public function addFavoriteBy(UserInfo $favoriteBy)
    {
        $this->favorite_by[] = $favoriteBy;

        return $this;
    }

    /**
     * Remove favorite_by
     *
     * @param UserInfo $favoriteBy
     */
    public function removeFavoriteBy(UserInfo $favoriteBy)
    {
        $this->favorite_by->removeElement($favoriteBy);
    }

    /**
     * Get favorite_by
     *
     * @return Collection
     */
    public function getFavoriteBy()
    {
        return $this->favorite_by;
    }

    /**
     * Add favorites
     *
     * @param UserInfo $favorites
     *
     * @return UserInfo
     */
    public function addFavorite(UserInfo $favorites)
    {
        $this->favorites[] = $favorites;

        return $this;
    }

    /**
     * Remove favorites
     *
     * @param UserInfo $favorites
     */
    public function removeFavorite(UserInfo $favorites)
    {
        $this->favorites->removeElement($favorites);
    }

    /**
     * Get favorites
     *
     * @return Collection
     */
    public function getFavorites()
    {
        return $this->favorites;
    }

    /**
     * Set wallet
     *
     * @param int $wallet
     *
     * @return UserInfo
     */
    public function setWallet($wallet)
    {
        $this->wallet = $wallet;

        return $this;
    }

    /**
     * Get wallet
     *
     * @return int
     */
    public function getWallet()
    {
        return $this->wallet;
    }

    /**
     * Add user_wallet_transactions
     *
     * @param UserWalletTransaction $userWalletTransactions
     *
     * @return UserInfo
     */
    public function addUserWalletTransaction(UserWalletTransaction $userWalletTransactions)
    {
        $this->user_wallet_transactions[] = $userWalletTransactions;

        return $this;
    }

    /**
     * Remove user_wallet_transactions
     *
     * @param UserWalletTransaction $userWalletTransactions
     */
    public function removeUserWalletTransaction(UserWalletTransaction $userWalletTransactions)
    {
        $this->user_wallet_transactions->removeElement($userWalletTransactions);
    }

    /**
     * Get user_wallet_transactions
     *
     * @return Collection|UserWalletTransaction[]
     */
    public function getUserWalletTransactions()
    {
        return $this->user_wallet_transactions;
    }

    /**
     * Set rating_total
     *
     * @param int $ratingTotal
     *
     * @return UserInfo
     */
    public function setRatingTotal($ratingTotal)
    {
        $this->rating_total = $ratingTotal;

        return $this;
    }

    /**
     * Get rating_total
     *
     * @return int
     */
    public function getRatingTotal()
    {
        return $this->rating_total;
    }

    /**
     * @return float
     */
    public function getVocalistRating()
    {
        return $this->vocalist_rating;
    }

    /**
     * @param float $vocalist_rating
     * @return UserInfo
     */
    public function setVocalistRating($vocalist_rating)
    {
        $this->vocalist_rating = $vocalist_rating;
        return $this;
    }

    /**
     * @return int
     */
    public function getVocalistRatedCount()
    {
        return $this->vocalist_rated_count;
    }

    /**
     * @param int $vocalist_rated_count
     * @return UserInfo
     */
    public function setVocalistRatedCount($vocalist_rated_count)
    {
        $this->vocalist_rated_count = $vocalist_rated_count;
        return $this;
    }

    /**
     * @return float
     */
    public function getProducerRating()
    {
        return $this->producer_rating;
    }

    /**
     * @param float $producer_rating
     * @return UserInfo
     */
    public function setProducerRating($producer_rating)
    {
        $this->producer_rating = $producer_rating;
        return $this;
    }

    /**
     * @return int
     */
    public function getProducerRatedCount()
    {
        return $this->producer_rated_count;
    }

    /**
     * @param int $producer_rated_count
     * @return UserInfo
     */
    public function setProducerRatedCount($producer_rated_count)
    {
        $this->producer_rated_count = $producer_rated_count;
        return $this;
    }

    /**
     * @return float
     */
    public function getEmployerRating()
    {
        return $this->employer_rating;
    }

    /**
     * @param float $employer_rating
     * @return UserInfo
     */
    public function setEmployerRating($employer_rating)
    {
        $this->employer_rating = $employer_rating;
        return $this;
    }

    /**
     * @return int
     */
    public function getEmployerRatedCount()
    {
        return $this->employer_rated_count;
    }

    /**
     * @param int $employer_rated_count
     * @return UserInfo
     */
    public function setEmployerRatedCount($employer_rated_count)
    {
        $this->employer_rated_count = $employer_rated_count;
        return $this;
    }

    /**
     * Add user_vocal_characteristics
     *
     * @param UserVocalCharacteristic $userVocalCharacteristics
     *
     * @return UserInfo
     */
    public function addUserVocalCharacteristic(UserVocalCharacteristic $userVocalCharacteristics)
    {
        $this->user_vocal_characteristics[] = $userVocalCharacteristics;

        return $this;
    }

    /**
     * Remove user_vocal_characteristics
     *
     * @param UserVocalCharacteristic $userVocalCharacteristics
     */
    public function removeUserVocalCharacteristic(UserVocalCharacteristic $userVocalCharacteristics)
    {
        $this->user_vocal_characteristics->removeElement($userVocalCharacteristics);
    }

    /**
     * Get user_vocal_characteristics
     *
     * @return Collection|UserVocalCharacteristic[]
     */
    public function getUserVocalCharacteristics()
    {
        return $this->user_vocal_characteristics;
    }

    /**
     * Set rating
     *
     * @param string $rating
     *
     * @return UserInfo
     */
    public function setRating($rating)
    {
        $this->rating = $rating;

        return $this;
    }

    /**
     * Get rating
     *
     * @return string
     */
    public function getRating()
    {
        return $this->rating;
    }

    /**
     * Set rated_count
     *
     * @param int $ratedCount
     *
     * @return UserInfo
     */
    public function setRatedCount($ratedCount)
    {
        $this->rated_count = $ratedCount;

        return $this;
    }

    /**
     * Get rated_count
     *
     * @return int
     */
    public function getRatedCount()
    {
        return $this->rated_count;
    }

    /**
     * Add user_vocal_styles
     *
     * @param UserVocalStyle $userVocalStyles
     *
     * @return UserInfo
     */
    public function addUserVocalStyle(UserVocalStyle $userVocalStyles)
    {
        $this->user_vocal_styles[] = $userVocalStyles;

        return $this;
    }

    /**
     * Remove user_vocal_styles
     *
     * @param UserVocalStyle $userVocalStyles
     */
    public function removeUserVocalStyle(UserVocalStyle $userVocalStyles)
    {
        $this->user_vocal_styles->removeElement($userVocalStyles);
    }

    /**
     * Get user_vocal_styles
     *
     * @return Collection|UserVocalStyle[]
     */
    public function getUserVocalStyles()
    {
        return $this->user_vocal_styles;
    }

    /**
     * Set microphone
     *
     * @param string $microphone
     *
     * @return UserInfo
     */
    public function setMicrophone($microphone)
    {
        $this->microphone = $microphone;

        return $this;
    }

    /**
     * Get microphone
     *
     * @return string
     */
    public function getMicrophone()
    {
        return $this->microphone;
    }

    /**
     * Add project_invites
     *
     * @param ProjectInvite $projectInvites
     *
     * @return UserInfo
     */
    public function addProjectInvite(ProjectInvite $projectInvites)
    {
        $this->project_invites[] = $projectInvites;

        return $this;
    }

    /**
     * Remove project_invites
     *
     * @param ProjectInvite $projectInvites
     */
    public function removeProjectInvite(ProjectInvite $projectInvites)
    {
        $this->project_invites->removeElement($projectInvites);
    }

    /**
     * Get project_invites
     *
     * @return Collection
     */
    public function getProjectInvites()
    {
        return $this->project_invites;
    }

    /**
     * Set user_pref
     *
     * @param UserPref $userPref
     *
     * @return UserInfo
     */
    public function setUserPref(UserPref $userPref = null)
    {
        $this->user_pref = $userPref;

        return $this;
    }

    /**
     * Get user_pref
     *
     * @return UserPref
     */
    public function getUserPref()
    {
        return $this->user_pref;
    }

    /**
     * Add project_feeds
     *
     * @param ProjectFeed $projectFeeds
     *
     * @return UserInfo
     */
    public function addProjectFeed(ProjectFeed $projectFeeds)
    {
        $this->project_feeds[] = $projectFeeds;

        return $this;
    }

    /**
     * Remove project_feeds
     *
     * @param ProjectFeed $projectFeeds
     */
    public function removeProjectFeed(ProjectFeed $projectFeeds)
    {
        $this->project_feeds->removeElement($projectFeeds);
    }

    /**
     * Get project_feeds
     *
     * @return Collection
     */
    public function getProjectFeeds()
    {
        return $this->project_feeds;
    }

    /**
     * Add user_settings
     *
     * @param UserSetting $userSettings
     *
     * @return UserInfo
     */
    public function addUserSetting(UserSetting $userSettings)
    {
        $this->user_settings[] = $userSettings;

        return $this;
    }

    /**
     * Remove user_settings
     *
     * @param UserSetting $userSettings
     */
    public function removeUserSetting(UserSetting $userSettings)
    {
        $this->user_settings->removeElement($userSettings);
    }

    /**
     * Get user_settings
     *
     * @return Collection
     */
    public function getUserSettings()
    {
        return $this->user_settings;
    }

    /**
     * Add project_audio
     *
     * @param ProjectAudio $projectAudio
     *
     * @return UserInfo
     */
    public function addProjectAudio(ProjectAudio $projectAudio)
    {
        $this->project_audio[] = $projectAudio;

        return $this;
    }

    /**
     * Remove project_audio
     *
     * @param ProjectAudio $projectAudio
     */
    public function removeProjectAudio(ProjectAudio $projectAudio)
    {
        $this->project_audio->removeElement($projectAudio);
    }

    /**
     * Get project_audio
     *
     * @return Collection
     */
    public function getProjectAudio()
    {
        return $this->project_audio;
    }

    /**
     * Set completed_profile
     *
     * @param \DateTime $completedProfile
     *
     * @return UserInfo
     */
    public function setCompletedProfile($completedProfile)
    {
        $this->completed_profile = $completedProfile;

        return $this;
    }

    /**
     * Get completed_profile
     *
     * @return \DateTime
     */
    public function getCompletedProfile()
    {
        return $this->completed_profile;
    }

    /**
     * Set location_lat
     *
     * @param float $locationLat
     *
     * @return UserInfo
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
     * @return UserInfo
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
     * Set vocalist_fee
     *
     * @param float $vocalistFee
     *
     * @return UserInfo
     */
    public function setVocalistFee($vocalistFee)
    {
        $this->vocalist_fee = $vocalistFee;

        return $this;
    }

    /**
     * Get vocalist_fee
     *
     * @return float
     */
    public function getVocalistFee()
    {
        return $this->vocalist_fee;
    }

    /**
     * Set producer_fee
     *
     * @param float $producerFee
     *
     * @return UserInfo
     */
    public function setProducerFee($producerFee)
    {
        $this->producer_fee = $producerFee;

        return $this;
    }

    /**
     * Get producer_fee
     *
     * @return float
     */
    public function getProducerFee()
    {
        return $this->producer_fee;
    }

    /**
     * Set unread_project_activity
     *
     * @param bool $unreadProjectActivity
     *
     * @return UserInfo
     */
    public function setUnreadProjectActivity($unreadProjectActivity)
    {
        $this->unread_project_activity = $unreadProjectActivity;

        return $this;
    }

    /**
     * Get unread_project_activity
     *
     * @return bool
     */
    public function getUnreadProjectActivity()
    {
        return $this->unread_project_activity;
    }

    /**
     * Set unseen_project_invitation
     *
     * @param bool $unseenProjectInvitation
     *
     * @return UserInfo
     */
    public function setUnseenProjectInvitation($unseenProjectInvitation)
    {
        $this->unseen_project_invitation = $unseenProjectInvitation;

        return $this;
    }

    /**
     * Get unseen_project_invitation
     *
     * @return bool
     */
    public function getUnseenProjectInvitation()
    {
        return $this->unseen_project_invitation;
    }

    /**
     * Set display_name
     *
     * @param string $displayName
     *
     * @return UserInfo
     */
    public function setDisplayName($displayName)
    {
        $this->display_name = $displayName;

        return $this;
    }

    /**
     * Get display_name
     *
     * @return string
     */
    public function getDisplayName()
    {
        if (!$this->display_name) {
            return $this->username;
        }
        return $this->display_name;
    }

    /**
     * Set date_activated
     *
     * @param \DateTime $dateActivated
     *
     * @return UserInfo
     */
    public function setDateActivated($dateActivated)
    {
        $this->date_activated = $dateActivated;

        return $this;
    }

    /**
     * Get date_activated
     *
     * @return \DateTime
     */
    public function getDateActivated()
    {
        return $this->date_activated;
    }

    /**
     * Set soundcloud_register
     *
     * @param bool $soundcloudRegister
     *
     * @return UserInfo
     */
    public function setSoundcloudRegister($soundcloudRegister)
    {
        $this->soundcloud_register = $soundcloudRegister;

        return $this;
    }

    /**
     * Get soundcloud_register
     *
     * @return bool
     */
    public function getSoundcloudRegister()
    {
        return $this->soundcloud_register;
    }

    /**
     * Add messages_sent
     *
     * @param Message $messagesSent
     *
     * @return UserInfo
     */
    public function addMessagesSent(Message $messagesSent)
    {
        $this->messages_sent[] = $messagesSent;

        return $this;
    }

    /**
     * Remove messages_sent
     *
     * @param Message $messagesSent
     */
    public function removeMessagesSent(Message $messagesSent)
    {
        $this->messages_sent->removeElement($messagesSent);
    }

    /**
     * Get messages_sent
     *
     * @return Collection
     */
    public function getMessagesSent()
    {
        return $this->messages_sent;
    }

    /**
     * Add messages_received
     *
     * @param Message $messagesReceived
     *
     * @return UserInfo
     */
    public function addMessagesReceived(Message $messagesReceived)
    {
        $this->messages_received[] = $messagesReceived;

        return $this;
    }

    /**
     * Remove messages_received
     *
     * @param Message $messagesReceived
     */
    public function removeMessagesReceived(Message $messagesReceived)
    {
        $this->messages_received->removeElement($messagesReceived);
    }

    /**
     * Get messages_received
     *
     * @return Collection
     */
    public function getMessagesReceived()
    {
        return $this->messages_received;
    }

    /**
     * Set num_unread_messages
     *
     * @param int $numUnreadMessages
     *
     * @return UserInfo
     */
    public function setNumUnreadMessages($numUnreadMessages)
    {
        $this->num_unread_messages = $numUnreadMessages;

        return $this;
    }

    /**
     * Get num_unread_messages
     *
     * @return int
     */
    public function getNumUnreadMessages()
    {
        return $this->num_unread_messages;
    }

    /**
     * Set is_admin
     *
     * @param bool $isAdmin
     *
     * @return UserInfo
     */
    public function setIsAdmin($isAdmin)
    {
        $this->is_admin = $isAdmin;

        return $this;
    }

    /**
     * Get is_admin
     *
     * @return bool
     */
    public function getIsAdmin()
    {
        return $this->is_admin;
    }

    /**
     * Set is_certified
     *
     * @param bool $isCertified
     *
     * @return UserInfo
     */
    public function setIsCertified($isCertified)
    {
        $this->is_certified = $isCertified;

        return $this;
    }

    /**
     * Get is_certified
     *
     * @return bool
     */
    public function getIsCertified()
    {
        return $this->is_certified;
    }

    /**
     * Set num_notifications
     *
     * @param int $numNotifications
     *
     * @return UserInfo
     */
    public function setNumNotifications($numNotifications)
    {
        $this->num_notifications = $numNotifications;

        return $this;
    }

    /**
     * Get num_notifications
     *
     * @return int
     */
    public function getNumNotifications()
    {
        return $this->num_notifications;
    }

    /**
     * Set subscription_plan
     *
     * @param SubscriptionPlan $subscriptionPlan
     *
     * @return UserInfo
     */
    public function setSubscriptionPlan(SubscriptionPlan $subscriptionPlan = null)
    {
        $this->subscription_plan = $subscriptionPlan;

        return $this;
    }

    /**
     * Get subscription_plan
     *
     * @return SubscriptionPlan
     */
    public function getSubscriptionPlan()
    {
        return $this->subscription_plan;
    }

    /**
     * Set connect_count
     *
     * @param int $connectCount
     *
     * @return UserInfo
     */
    public function setConnectCount($connectCount)
    {
        $this->connect_count = $connectCount;

        return $this;
    }

    /**
     * Get connect_count
     *
     * @return int
     */
    public function getConnectCount()
    {
        return $this->connect_count;
    }

    /**
     * Set stripe_cust_id
     *
     * @param string $stripeCustId
     *
     * @return UserInfo
     */
    public function setStripeCustId($stripeCustId)
    {
        $this->stripe_cust_id = $stripeCustId;

        return $this;
    }

    /**
     * Get stripe_cust_id
     *
     * @return string
     */
    public function getStripeCustId()
    {
        return $this->stripe_cust_id;
    }

    /**
     * Add user_connect_invites
     *
     * @param UserConnectInvite $userConnectInvites
     *
     * @return UserInfo
     */
    public function addUserConnectInvite(UserConnectInvite $userConnectInvites)
    {
        $this->user_connect_invites[] = $userConnectInvites;

        return $this;
    }

    /**
     * Remove user_connect_invites
     *
     * @param UserConnectInvite $userConnectInvites
     */
    public function removeUserConnectInvite(UserConnectInvite $userConnectInvites)
    {
        $this->user_connect_invites->removeElement($userConnectInvites);
    }

    /**
     * Get user_connect_invites
     *
     * @return Collection
     */
    public function getUserConnectInvites()
    {
        return $this->user_connect_invites;
    }

    /**
     * Add user_connect_invites_sent
     *
     * @param UserConnectInvite $userConnectInvitesSent
     *
     * @return UserInfo
     */
    public function addUserConnectInvitesSent(UserConnectInvite $userConnectInvitesSent)
    {
        $this->user_connect_invites_sent[] = $userConnectInvitesSent;

        return $this;
    }

    /**
     * Remove user_connect_invites_sent
     *
     * @param UserConnectInvite $userConnectInvitesSent
     */
    public function removeUserConnectInvitesSent(UserConnectInvite $userConnectInvitesSent)
    {
        $this->user_connect_invites_sent->removeElement($userConnectInvitesSent);
    }

    /**
     * Get user_connect_invites_sent
     *
     * @return Collection
     */
    public function getUserConnectInvitesSent()
    {
        return $this->user_connect_invites_sent;
    }

    /**
     * Add user_blocks
     *
     * @param UserBlock $userBlocks
     *
     * @return UserInfo
     */
    public function addUserBlock(UserBlock $userBlocks)
    {
        $this->user_blocks[] = $userBlocks;

        return $this;
    }

    /**
     * Remove user_blocks
     *
     * @param UserBlock $userBlocks
     */
    public function removeUserBlock(UserBlock $userBlocks)
    {
        $this->user_blocks->removeElement($userBlocks);
    }

    /**
     * Get user_blocks
     *
     * @return Collection
     */
    public function getUserBlocks()
    {
        return $this->user_blocks;
    }

    /**
     * Add connections
     *
     * @param UserConnect $connections
     *
     * @return UserInfo
     */
    public function addConnection(UserConnect $connections)
    {
        $this->connections[] = $connections;

        return $this;
    }

    /**
     * Remove connections
     *
     * @param UserConnect $connections
     */
    public function removeConnection(UserConnect $connections)
    {
        $this->connections->removeElement($connections);
    }

    /**
     * Get connections
     *
     * @return Collection|UserConnect[]
     */
    public function getConnections()
    {
        return $this->connections;
    }

    /**
     * Add marketplace_items
     *
     * @param MarketplaceItem $marketplaceItems
     *
     * @return UserInfo
     */
    public function addMarketplaceItem(MarketplaceItem $marketplaceItems)
    {
        $this->marketplace_items[] = $marketplaceItems;

        return $this;
    }

    /**
     * Remove marketplace_items
     *
     * @param MarketplaceItem $marketplaceItems
     */
    public function removeMarketplaceItem(MarketplaceItem $marketplaceItems)
    {
        $this->marketplace_items->removeElement($marketplaceItems);
    }

    /**
     * Get marketplace_items
     *
     * @return Collection
     */
    public function getMarketplaceItems()
    {
        return $this->marketplace_items;
    }

    /**
     * Add marketplace_item_audio
     *
     * @param MarketplaceItemAudio $marketplaceItemAudio
     *
     * @return UserInfo
     */
    public function addMarketplaceItemAudio(MarketplaceItemAudio $marketplaceItemAudio)
    {
        $this->marketplace_item_audio[] = $marketplaceItemAudio;

        return $this;
    }

    /**
     * Remove marketplace_item_audio
     *
     * @param MarketplaceItemAudio $marketplaceItemAudio
     */
    public function removeMarketplaceItemAudio(MarketplaceItemAudio $marketplaceItemAudio)
    {
        $this->marketplace_item_audio->removeElement($marketplaceItemAudio);
    }

    /**
     * Get marketplace_item_audio
     *
     * @return Collection
     */
    public function getMarketplaceItemAudio()
    {
        return $this->marketplace_item_audio;
    }

    /**
     * @return mixed
     */
    public function getUserVideos()
    {
        return $this->userVideos;
    }

    /**
     * @param UserVideo $userVideo
     *
     * @return UserInfo
     */
    public function addUserVideo(UserVideo $userVideo)
    {
        $this->userVideos[] = $userVideo;

        $userVideo->setUserInfo($this);

        return $this;
    }

    /**
     * @return mixed
     */
    public function getUserSpotifyId()
    {
        return $this->userSpotifyId;
    }

    /**
     * @param mixed $userSpotifyId
     */
    public function setUserSpotifyId($userSpotifyId)
    {
        $this->userSpotifyId = $userSpotifyId;
    }

    /**
     * Remove UserVideo
     *
     * @param UserVideo $userVideo
     */
    public function removeUserVideo(UserVideo $userVideo)
    {
        $this->userVideos->removeElement($userVideo);
    }

    /**
     * @return int
     */
    public function getVideosCount()
    {
        return count($this->userVideos);
    }

    /**
     * @param UserSpotifyPlaylist $userSpotifyPlaylist
     *
     * @return UserInfo
     */
    public function addUserSpotifyPlaylist(UserSpotifyPlaylist $userSpotifyPlaylist)
    {
        $this->userSpotifyPlaylists[] = $userSpotifyPlaylist;

        $userSpotifyPlaylist->setUserInfo($this);

        return $this;
    }

    /**
     * Remove UserSpotifyPlaylist
     *
     * @param UserSpotifyPlaylist $userSpotifyPlaylist
     */
    public function removeUserSpotifyPlaylist(UserSpotifyPlaylist $userSpotifyPlaylist)
    {
        $this->userSpotifyPlaylists->removeElement($userSpotifyPlaylist);
    }

    /**
     * @return UserSpotifyPlaylist[]
     */
    public function getUserSpotifyPlaylists()
    {
        return $this->userSpotifyPlaylists;
    }

    /**
     * @return int
     */
    public function getUserSpotifyPlaylistsCount()
    {
        return count($this->userSpotifyPlaylists);
    }

    /**
     * @param UserInfoLanguage $userLanguage
     *
     * @return Language
     */
    public function addUserLanguage(UserInfoLanguage $userLanguage)
    {
        $this->userLanguages[] = $userLanguage;

        return $this;
    }

    /**
     * @param UserInfoLanguage $userLanguage
     */
    public function removeUserLanguage(UserInfoLanguage $userLanguage)
    {
        $this->userLanguages->removeElement($userLanguage);
    }

    /**
     * @return UserInfoLanguage[]
     */
    public function getUserLanguages()
    {
        return $this->userLanguages;
    }

    /**
     * @return mixed
     */
    public function getLoginIp()
    {
        return $this->loginIp;
    }

    /**
     * @param mixed $loginIp
     */
    public function setLoginIp($loginIp)
    {
        $this->loginIp = $loginIp;
    }

    /**
     * @return mixed
     */
    public function getRegisterIp()
    {
        return $this->registerIp;
    }

    /**
     * @param mixed $registerIp
     */
    public function setRegisterIp($registerIp)
    {
        $this->registerIp = $registerIp;
    }

    /**
     * @return Country
     */
    public function getUserCountry()
    {
        return $this->userCountry;
    }

    /**
     * @param Country $userCountry
     *
     * @return UserInfo
     */
    public function setUserCountry($userCountry)
    {
        $this->userCountry = $userCountry;
        return $this;
    }

    /**
     * @return ArrayCollection|HintSkip[]
     */
    public function isSkippedHints()
    {
        return $this->skippedHints;
    }

    /**
     * @param ArrayCollection|HintSkip[] $skippedHints
     *
     * @return UserInfo
     */
    public function setSkippedHints($skippedHints)
    {
        $this->skippedHints = $skippedHints;
        return $this;
    }

    /**
     * @param HintSkip $skip
     *
     * @return UserInfo
     */
    public function addSkippedHint(HintSkip $skip)
    {
        $this->skippedHints->add($skip);
        return $this;
    }

    /**
     * Remove skippedHints
     *
     * @param HintSkip $skippedHints
     */
    public function removeSkippedHint(HintSkip $skippedHints)
    {
        $this->skippedHints->removeElement($skippedHints);
    }

    /**
     * Get skippedHints
     *
     * @return Collection
     */
    public function getSkippedHints()
    {
        return $this->skippedHints;
    }

    /**
     * @return bool
     */
    public function isActivationEventSuppressed()
    {
        return $this->activationEventSuppressed;
    }

    /**
     * @param bool $activationEventSuppressed
     *
     * @return UserInfo
     */
    public function setActivationEventSuppressed($activationEventSuppressed)
    {
        $this->activationEventSuppressed = $activationEventSuppressed;
        return $this;
    }

    /**
     * @return bool
     */
    public function isRegistrationFinished()
    {
        return $this->registration_finished;
    }

    /**
     * @param bool $registration_finished
     *
     * @return UserInfo
     */
    public function setRegistrationFinished($registration_finished)
    {
        $this->registration_finished = $registration_finished;
        return $this;
    }

    /**
     * Get registration_finished
     *
     * @return bool
     */
    public function getRegistrationFinished()
    {
        return $this->registration_finished;
    }

    /**
     * Add audits
     *
     * @param \Vocalizr\AppBundle\Entity\UserActionAudit $audits
     *
     * @return UserInfo
     */
    public function addAudit(\Vocalizr\AppBundle\Entity\UserActionAudit $audits)
    {
        $this->audits[] = $audits;

        return $this;
    }

    /**
     * Remove audits
     *
     * @param \Vocalizr\AppBundle\Entity\UserActionAudit $audits
     */
    public function removeAudit(\Vocalizr\AppBundle\Entity\UserActionAudit $audits)
    {
        $this->audits->removeElement($audits);
    }

    /**
     * Get audits
     *
     * @return Collection
     */
    public function getAudits()
    {
        return $this->audits;
    }

    /**
     * @return Collection|UserWithdraw[]
     */
    public function getUserWithdraws()
    {
        return $this->user_withdraws;
    }

    /**
     * @param Collection|UserWithdraw[] $user_withdraws
     *
     * @return UserInfo
     */
    public function setUserWithdraws($user_withdraws)
    {
        $this->user_withdraws = $user_withdraws;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getWithdrawEmail()
    {
        return $this->withdrawEmail;
    }

    /**
     * @param string|null $withdrawEmail
     * @return UserInfo
     */
    public function setWithdrawEmail($withdrawEmail)
    {
        $this->withdrawEmail = $withdrawEmail;
        return $this;
    }
    /**
     * @return UserTotalOnline|null
     */
    public function getUserOnline()
    {
        return $this->userOnline;
    }

    /**
     * @param UserTotalOnline|null $userOnline
     * @return UserInfo
     */
    public function setUserOnline($userOnline)
    {
        $this->userOnline = $userOnline;
        return $this;
    }

    /**
     * @return bool
     */
    public function isProProfileEnabled()
    {
        return $this->proProfileEnabled;
    }

    /**
     * @param bool $proProfileEnabled
     * @return UserInfo
     */
    public function setProProfileEnabled($proProfileEnabled)
    {
        $this->proProfileEnabled = $proProfileEnabled;
        return $this;
    }

    /**
     * @return UserProProfile|null
     */
    public function getProProfile()
    {
        return $this->proProfile;
    }

    /**
     * @param UserProProfile|null $proProfile
     * @return UserInfo
     */
    public function setProProfile($proProfile)
    {
        $this->proProfile = $proProfile;
        return $this;
    }

    /**
     * @return ArrayCollection|UserStripeIdentity[]
     */
    public function getUserIdentity()
    {
        return $this->userIdentity;
    }

    /**
     * @param ArrayCollection|UserStripeIdentity[] $userIdentity
     * @return UserInfo
     */
    public function setUserIdentity($userIdentity)
    {
        $this->userIdentity = $userIdentity;
        return $this;
    }

    /**
     * @return bool
     */
    public function isVerified()
    {
        if ($this->userIdentity->last()) {
            return $this->userIdentity->last()->isVerified();
        }
        return false;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function isRequestedVerificationRecently()
    {
        /** @var UserStripeIdentity|false $lastIdentity */
        $lastIdentity = $this->userIdentity->last();
        if ($lastIdentity && !$lastIdentity->getVerificationReportId() && !$lastIdentity->isCustom()) {
            $newTime = strtotime((new \DateTime())->format('Y-m-d H:i:s'));
            $lastTime = strtotime($this->userIdentity->last()->getCreatedAt()->format('Y-m-d H:i:s'));

            $diff = $newTime - $lastTime;
            if ($diff < 600) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return bool
     */
    public function isVerificationsExists()
    {
        return $this->userIdentity->count() > 0;
    }

    /**
     * @return int|mixed
     * @throws \Exception
     */
    public function getAccountAgeInDays()
    {
        if ($this->date_registered instanceof \DateTime) {
            return $this->date_registered->diff(new \DateTime())->days;
        }
        return 0;
    }

    /**
     * @return string
     */
    public function getProfileCompleteness()
    {
        $completeness = 0;
        $total = 11;
        $this->getDisplayName() ? $completeness++ : null;
        $this->getFirstName() ? $completeness++ : null;
        $this->getLastName() ? $completeness++ : null;
        $this->getProfile() ? $completeness++ : null;
        $this->getGender() ? $completeness++ : null;
        count($this->getUserLanguages()) ? $completeness++ : null;
        $this->getCity() ? $completeness++ : null;
        $this->getCountry() ? $completeness++ : null;
        $this->getGenres()->count() ? $completeness++ : null;
        $this->getPath() ? $completeness++ : null;
        $this->getUserAudio()->count() ? $completeness++ : null;
        if ($this->is_producer) {
            $this->getProducerFee() ? $completeness++ : null;
            $total++;
        }
        if ($this->is_vocalist) {
            $this->getVocalistFee() ? $completeness++ : null;
            $this->getMicrophone() ? $completeness++ : null;
            $this->getUserVocalCharacteristics()->count() ? $completeness++ : null;
            $this->getUserVocalStyles()->count() ? $completeness++ : null;
            $this->getUserVoiceTags()->count() ? $completeness++ : null;
            $total = $total + 5;
        }

        return  round(($completeness / $total) * 100, 0) . '%';
    }

    public function getGetCertifiedMailSend()
    {
        return $this->getCertifiedMailSend;
    }

    public function setGetCertifiedMailSend($getCertifiedMailSend)
    {
        $this->getCertifiedMailSend = $getCertifiedMailSend;
    }
}
