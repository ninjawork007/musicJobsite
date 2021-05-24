<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\VocalCharacteristicRepository")
 * @ORM\Table(name="vocal_characteristic")
 */
class VocalCharacteristic
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
     * @ORM\ManyToMany(targetEntity="Project", mappedBy="vocalCharacteristics")
     */
    protected $projects;

    /**
     * Vocal characteristics
     *
     * @ORM\OneToMany(targetEntity="UserVocalCharacteristic", mappedBy="vocal_characteristic")
     */
    protected $user_vocal_characteristics;

    /**
     * Constructor
     */
    public function __construct()
    {
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
     * @param \App\Entity\Project $projects
     *
     * @return Genre
     */
    public function addProject(\App\Entity\Project $projects)
    {
        $this->projects[] = $projects;

        return $this;
    }

    /**
     * Remove projects
     *
     * @param \App\Entity\Project $projects
     */
    public function removeProject(\App\Entity\Project $projects)
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
     * @param \App\Entity\UserInfo $users
     *
     * @return Genre
     */
    public function addUser(\App\Entity\UserInfo $users)
    {
        $this->users[] = $users;

        return $this;
    }

    /**
     * Remove users
     *
     * @param \App\Entity\UserInfo $users
     */
    public function removeUser(\App\Entity\UserInfo $users)
    {
        $this->users->removeElement($users);
    }

    /**
     * Get users
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * Add user_vocal_characteristics
     *
     * @param \App\Entity\UserVocalCharacteristic $userVocalCharacteristics
     *
     * @return VocalCharacteristic
     */
    public function addUserVocalCharacteristic(\App\Entity\UserVocalCharacteristic $userVocalCharacteristics)
    {
        $this->user_vocal_characteristics[] = $userVocalCharacteristics;

        return $this;
    }

    /**
     * Remove user_vocal_characteristics
     *
     * @param \App\Entity\UserVocalCharacteristic $userVocalCharacteristics
     */
    public function removeUserVocalCharacteristic(\App\Entity\UserVocalCharacteristic $userVocalCharacteristics)
    {
        $this->user_vocal_characteristics->removeElement($userVocalCharacteristics);
    }

    /**
     * Get user_vocal_characteristics
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserVocalCharacteristics()
    {
        return $this->user_vocal_characteristics;
    }
}