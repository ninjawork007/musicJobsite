<?php

namespace Vocalizr\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Vocalizr\AppBundle\Repository\UserSpotifyPlaylistRepository")
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Table(name="user_spotify_playlists")
 */
class UserSpotifyPlaylist
{
    const SPOTIFY_PLAYLIST_TYPE_TRACK = 0;

    const SPOTIFY_PLAYLIST_TYPE_ALBUM = 1;

    const SPOTIFY_PLAYLIST_TYPE_PLAYLIST = 2;

    public static $types = [
        self::SPOTIFY_PLAYLIST_TYPE_TRACK    => 'Track',
        self::SPOTIFY_PLAYLIST_TYPE_ALBUM    => 'Album',
        self::SPOTIFY_PLAYLIST_TYPE_PLAYLIST => 'Playlist',
    ];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="UserInfo", inversedBy="userVideos")
     * @ORM\JoinColumn(name="user_info_id", referencedColumnName="id")
     */
    private $userInfo;

    /**
     * @ORM\Column(type="integer")
     */
    private $type;

    /**
     * @ORM\Column(type="string")
     */
    private $spotifyId;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $userId;

    /**
     * @ORM\Column(type="datetime", name="created_at")
     */
    private $createdAt;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        $this->setCreatedAt(new \DateTime());
    }

    /**
     * @return UserInfo
     */
    public function getUserInfo()
    {
        return $this->userInfo;
    }

    /**
     * @param UserInfo $userInfo
     */
    public function setUserInfo($userInfo)
    {
        $this->userInfo = $userInfo;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return mixed
     */
    public function getSpotifyId()
    {
        return $this->spotifyId;
    }

    /**
     * @param mixed $spotifyId
     */
    public function setSpotifyId($spotifyId)
    {
        $this->spotifyId = $spotifyId;
    }

    /**
     * @return mixed
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param mixed $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param mixed $userId
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;
    }
}