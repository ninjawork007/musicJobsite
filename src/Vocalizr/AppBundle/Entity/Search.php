<?php

namespace Vocalizr\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Vocalizr\AppBundle\Repository\SearchRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="search")
 */
class Search
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo", inversedBy="search")
     */
    protected $user_info = null;

    /**
     * @ORM\Column(type="string", length=64)
     */
    protected $search_term;

    /**
     * @ORM\Column(type="integer", length=10)
     */
    protected $num_results;

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
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set search_term
     *
     * @param string $searchTerm
     *
     * @return Search
     */
    public function setSearchTerm($searchTerm)
    {
        $this->search_term = $searchTerm;

        return $this;
    }

    /**
     * Get search_term
     *
     * @return string
     */
    public function getSearchTerm()
    {
        return $this->search_term;
    }

    /**
     * Set num_results
     *
     * @param int $numResults
     *
     * @return Search
     */
    public function setNumResults($numResults)
    {
        $this->num_results = $numResults;

        return $this;
    }

    /**
     * Get num_results
     *
     * @return int
     */
    public function getNumResults()
    {
        return $this->num_results;
    }

    /**
     * Set created_at
     *
     * @param \DateTime $createdAt
     *
     * @return Search
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
     * @param UserInfo $userInfo
     *
     * @return Search
     */
    public function setUserInfo(\Vocalizr\AppBundle\Entity\UserInfo $userInfo = null)
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
}