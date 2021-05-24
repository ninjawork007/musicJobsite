<?php

namespace Vocalizr\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Vocalizr\AppBundle\Repository\VocalStyleRepository")
 * @ORM\Table(name="vocal_style")
 */
class VocalStyle
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    public $id;

    /**
     * @ORM\Column(type="string", length=64)
     */
    protected $title;

    /**
     * Relationships
     */

    /**
     * @ORM\ManyToMany(targetEntity="Project", mappedBy="vocalStyles")
     */
    protected $projects;

    /**
     * Vocal style
     *
     * @ORM\OneToMany(targetEntity="UserVocalStyle", mappedBy="vocal_style")
     */
    protected $user_vocal_styles;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->user_genres = new \Doctrine\Common\Collections\ArrayCollection();
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
     * @return Genre
     */
    public function setTitle($title)
    {
        $this->title = $title;

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
     * Add projects
     *
     * @param \Vocalizr\AppBundle\Entity\Project $projects
     *
     * @return Genre
     */
    public function addProject(\Vocalizr\AppBundle\Entity\Project $projects)
    {
        $this->projects[] = $projects;

        return $this;
    }

    /**
     * Remove projects
     *
     * @param \Vocalizr\AppBundle\Entity\Project $projects
     */
    public function removeProject(\Vocalizr\AppBundle\Entity\Project $projects)
    {
        $this->projects->removeElement($projects);
    }

    /**
     * Get projects
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getProjects()
    {
        return $this->projects;
    }

    /**
     * Return the string representation of the Genre entity
     *
     * @return string
     */
    public function __toString()
    {
        return $this->title;
    }

    /**
     * Add users
     *
     * @param \Vocalizr\AppBundle\Entity\UserInfo $users
     *
     * @return Genre
     */
    public function addUser(\Vocalizr\AppBundle\Entity\UserInfo $users)
    {
        $this->users[] = $users;

        return $this;
    }

    /**
     * Add user_vocal_styles
     *
     * @param \Vocalizr\AppBundle\Entity\UserVocalStyle $userVocalStyles
     *
     * @return VocalStyle
     */
    public function addUserVocalStyle(\Vocalizr\AppBundle\Entity\UserVocalStyle $userVocalStyles)
    {
        $this->user_vocal_styles[] = $userVocalStyles;

        return $this;
    }

    /**
     * Remove user_vocal_styles
     *
     * @param \Vocalizr\AppBundle\Entity\UserVocalStyle $userVocalStyles
     */
    public function removeUserVocalStyle(\Vocalizr\AppBundle\Entity\UserVocalStyle $userVocalStyles)
    {
        $this->user_vocal_styles->removeElement($userVocalStyles);
    }

    /**
     * Get user_vocal_styles
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserVocalStyles()
    {
        return $this->user_vocal_styles;
    }
}