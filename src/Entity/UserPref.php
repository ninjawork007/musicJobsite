<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserPrefRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="user_pref")
 */
class UserPref
{
    const DEFAULT_EMAIL_PROJECT_INVITES = true;

    const DEFAULT_EMAIL_PROJECT_BIDS = true;

    const DEFAULT_EMAIL_NEW_PROJECTS = true;

    const DEFAULT_EMAIL_VOCALIST_SUGGESTIONS = true;

    const DEFAULT_ACTIVITY_FILTER = 'all';

    const DEFAULT_EMAIL_NEW_COLLABS = false;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo", inversedBy="user_pref")
     */
    protected $user_info;

    /**
     * @ORM\Column(type="string", length=20)
     */
    protected $email_project_digest = 'instantly';

    /**
     * @ORM\Column(type="boolean")
     */
    protected $email_project_bids = UserPref::DEFAULT_EMAIL_PROJECT_BIDS;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $email_project_invites = UserPref::DEFAULT_EMAIL_PROJECT_INVITES;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $email_new_projects = UserPref::DEFAULT_EMAIL_NEW_PROJECTS;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $email_vocalist_suggestions = UserPref::DEFAULT_EMAIL_VOCALIST_SUGGESTIONS;

    /**
     * @ORM\Column(type="string", length=10)
     */
    protected $activity_filter = UserPref::DEFAULT_ACTIVITY_FILTER;

    /**
     * @ORM\Column(type="boolean", options={"default":1})
     */
    protected $email_messages = true;

    /**
     * @ORM\Column(type="boolean", options={"default":1})
     */
    protected $email_connections = true;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $email_tag_voting = true;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $email_new_collabs = UserPref::DEFAULT_EMAIL_NEW_COLLABS;

    /**
     * @ORM\Column(type="boolean", options={"default":0})
     */
    protected $connect_restrict_subscribed = false;

    /**
     * @ORM\Column(type="boolean", options={"default":0})
     */
    protected $connect_restrict_certified = false;

    /**
     * @ORM\Column(type="boolean", options={"default":1})
     */
    protected $connect_accept = true;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $updated_at = null;

    /**
     * Relationships
     */

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
     * Set email_project_digest
     *
     * @param string $emailProjectDigest
     *
     * @return UserPref
     */
    public function setEmailProjectDigest($emailProjectDigest)
    {
        $this->email_project_digest = $emailProjectDigest;

        return $this;
    }

    /**
     * Get email_project_digest
     *
     * @return string
     */
    public function getEmailProjectDigest()
    {
        return $this->email_project_digest;
    }

    /**
     * Set email_project_bids
     *
     * @param bool $emailProjectBids
     *
     * @return UserPref
     */
    public function setEmailProjectBids($emailProjectBids)
    {
        $this->email_project_bids = $emailProjectBids;

        return $this;
    }

    /**
     * Get email_project_bids
     *
     * @return bool
     */
    public function getEmailProjectBids()
    {
        return $this->email_project_bids;
    }

    /**
     * Set email_project_invites
     *
     * @param bool $emailProjectInvites
     *
     * @return UserPref
     */
    public function setEmailProjectInvites($emailProjectInvites)
    {
        $this->email_project_invites = $emailProjectInvites;

        return $this;
    }

    /**
     * Get email_project_invites
     *
     * @return bool
     */
    public function getEmailProjectInvites()
    {
        return $this->email_project_invites;
    }

    /**
     * Set email_tag_voting
     *
     * @param bool $emailTagVoting
     *
     * @return UserPref
     */
    public function setEmailTagVoting($emailTagVoting)
    {
        $this->email_tag_voting = $emailTagVoting;

        return $this;
    }

    /**
     * Get email_tag_voting
     *
     * @return bool
     */
    public function getEmailTagVoting()
    {
        return $this->email_tag_voting;
    }

    /**
     * Set updated_at
     *
     * @param \DateTime $updatedAt
     *
     * @return UserPref
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
     * @param \App\Entity\UserInfo $userInfo
     *
     * @return UserPref
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
     * Set email_new_projects
     *
     * @param bool $emailNewProjects
     *
     * @return UserPref
     */
    public function setEmailNewProjects($emailNewProjects)
    {
        $this->email_new_projects = $emailNewProjects;

        return $this;
    }

    /**
     * Get email_new_projects
     *
     * @return bool
     */
    public function getEmailNewProjects()
    {
        return $this->email_new_projects;
    }

    /**
     * Set email_vocalist_suggestions
     *
     * @param bool $emailVocalistSuggestions
     *
     * @return UserPref
     */
    public function setEmailVocalistSuggestions($emailVocalistSuggestions)
    {
        $this->email_vocalist_suggestions = $emailVocalistSuggestions;

        return $this;
    }

    /**
     * Get email_vocalist_suggestions
     *
     * @return bool
     */
    public function getEmailVocalistSuggestions()
    {
        return $this->email_vocalist_suggestions;
    }

    /**
     * Set activity_filter
     *
     * @param string $activityFilter
     *
     * @return UserPref
     */
    public function setActivityFilter($activityFilter)
    {
        $this->activity_filter = $activityFilter;

        return $this;
    }

    /**
     * Get activity_filter
     *
     * @return string
     */
    public function getActivityFilter()
    {
        return $this->activity_filter;
    }

    /**
     * Set email_new_collabs
     *
     * @param bool $emailNewCollabs
     *
     * @return UserPref
     */
    public function setEmailNewCollabs($emailNewCollabs)
    {
        $this->email_new_collabs = $emailNewCollabs;

        return $this;
    }

    /**
     * Get email_new_collabs
     *
     * @return bool
     */
    public function getEmailNewCollabs()
    {
        return $this->email_new_collabs;
    }

    /**
     * Set connect_restrict_subscribed
     *
     * @param bool $connectRestrictSubscribed
     *
     * @return UserPref
     */
    public function setConnectRestrictSubscribed($connectRestrictSubscribed)
    {
        $this->connect_restrict_subscribed = $connectRestrictSubscribed;

        return $this;
    }

    /**
     * Get connect_restrict_subscribed
     *
     * @return bool
     */
    public function getConnectRestrictSubscribed()
    {
        return $this->connect_restrict_subscribed;
    }

    /**
     * Set connect_restrict_certified
     *
     * @param bool $connectRestrictCertified
     *
     * @return UserPref
     */
    public function setConnectRestrictCertified($connectRestrictCertified)
    {
        $this->connect_restrict_certified = $connectRestrictCertified;

        return $this;
    }

    /**
     * Get connect_restrict_certified
     *
     * @return bool
     */
    public function getConnectRestrictCertified()
    {
        return $this->connect_restrict_certified;
    }

    /**
     * Set connect_accept
     *
     * @param bool $connectAccept
     *
     * @return UserPref
     */
    public function setConnectAccept($connectAccept)
    {
        $this->connect_accept = $connectAccept;

        return $this;
    }

    /**
     * Get connect_accept
     *
     * @return bool
     */
    public function getConnectAccept()
    {
        return $this->connect_accept;
    }

    /**
     * Set email_messages
     *
     * @param bool $emailMessages
     *
     * @return UserPref
     */
    public function setEmailMessages($emailMessages)
    {
        $this->email_messages = $emailMessages;

        return $this;
    }

    /**
     * Get email_messages
     *
     * @return bool
     */
    public function getEmailMessages()
    {
        return $this->email_messages;
    }

    /**
     * Set email_connections
     *
     * @param bool $emailConnections
     *
     * @return UserPref
     */
    public function setEmailConnections($emailConnections)
    {
        $this->email_connections = $emailConnections;

        return $this;
    }

    /**
     * Get email_connections
     *
     * @return bool
     */
    public function getEmailConnections()
    {
        return $this->email_connections;
    }
}