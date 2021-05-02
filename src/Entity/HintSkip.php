<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class HintSkip
 *
 * @package App\Entity
 *
 * @ORM\Entity(repositoryClass="App\Repository\HintSkipRepository")
 * @ORM\Table(name="user_hint_skips")
 */
class HintSkip
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var UserInfo
     *
     * @ORM\ManyToOne(targetEntity="App\Entity\UserInfo", inversedBy="skippedHints")
     */
    private $user;

    /**
     * @var int
     *
     * @ORM\Column(name="hint", type="smallint")
     */
    private $hint;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     *
     * @return HintSkip
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
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
     * @return HintSkip
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return int
     */
    public function getHint()
    {
        return $this->hint;
    }

    /**
     * @param int $hint
     *
     * @return HintSkip
     */
    public function setHint($hint)
    {
        $this->hint = $hint;
        return $this;
    }
}