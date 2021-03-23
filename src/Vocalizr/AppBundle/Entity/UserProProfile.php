<?php

namespace Vocalizr\AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class UserProProfile
 * @package Vocalizr\AppBundle\Entity
 *
 * @ORM\Entity()
 */
class UserProProfile
{
    const BACKGROUND_NETWORK_DIRECTORY = '/uploads/background';
    const ABOUT_ME_NETWORK_DIRECTORY   = '/uploads/about/full';
    const ABOUT_ME_THUMBNAIL_NETWORK_DIRECTORY = '/uploads/about/thumb';

    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var UserInfo
     *
     * @ORM\OneToOne(targetEntity="Vocalizr\AppBundle\Entity\UserInfo", inversedBy="proProfile")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    private $userInfo;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $heroImage;

    /**
     * @var array
     *
     * @ORM\Column(type="simple_array", nullable=true)
     */
    private $socialLinks = [];

    /**
     * @var array
     *
     * @ORM\Column(type="json_array", nullable=true)
     */
    private $aboutMeImages = [];

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $facebookLink;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $soundcloudLink;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $instagramLink;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $spotifyLink;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $youtubeLink;

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return $this->userInfo->isProProfileEnabled();
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled($enabled)
    {
        $this->userInfo->setProProfileEnabled($enabled);
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
     * @return UserProProfile
     */
    public function setUserInfo($userInfo)
    {
        $this->userInfo = $userInfo;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getHeroImage()
    {
        return $this->heroImage;
    }

    /**
     * @return string|null
     */
    public function getHeroImageWebPath()
    {
        return ($this->heroImage ?
            sprintf('%s/%s', self::BACKGROUND_NETWORK_DIRECTORY, $this->heroImage) :
            null);
    }

    /**
     * @param string|null $heroImage
     * @return UserProProfile
     */
    public function setHeroImage($heroImage)
    {
        $this->heroImage = $heroImage;
        return $this;
    }

    /**
     * @return array
     */
    public function getSocialLinks()
    {
        return $this->socialLinks;
    }

    /**
     * @param array $socialLinks
     * @return UserProProfile
     */
    public function setSocialLinks($socialLinks)
    {
        $this->socialLinks = $socialLinks;
        return $this;
    }

    /**
     * @return string
     */
    public function getSocialLinksCommaSeparated()
    {
        return join(',', $this->socialLinks);
    }

    /**
     * @param string $socialLinks
     * @return UserProProfile
     */
    public function setSocialLinksCommaSeparated($socialLinks)
    {
        $this->socialLinks = explode(',', $socialLinks);
        return $this;
    }

    /**
     * @return string|null
     */
    public function getFacebookLink()
    {
        return $this->facebookLink;
    }

    /**
     * @param string|null $facebookLink
     * @return UserProProfile
     */
    public function setFacebookLink($facebookLink)
    {
        $this->facebookLink = $facebookLink;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSoundcloudLink()
    {
        return $this->soundcloudLink;
    }

    /**
     * @param string|null $soundcloudLink
     * @return UserProProfile
     */
    public function setSoundcloudLink($soundcloudLink)
    {
        $this->soundcloudLink = $soundcloudLink;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getInstagramLink()
    {
        return $this->instagramLink;
    }

    /**
     * @param string|null $instagramLink
     * @return UserProProfile
     */
    public function setInstagramLink($instagramLink)
    {
        $this->instagramLink = $instagramLink;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getSpotifyLink()
    {
        return $this->spotifyLink;
    }

    /**
     * @param string|null $spotifyLink
     * @return UserProProfile
     */
    public function setSpotifyLink($spotifyLink)
    {
        $this->spotifyLink = $spotifyLink;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getYoutubeLink()
    {
        return $this->youtubeLink;
    }

    /**
     * @param string|null $youtubeLink
     * @return UserProProfile
     */
    public function setYoutubeLink($youtubeLink)
    {
        $this->youtubeLink = $youtubeLink;
        return $this;
    }

    /**
     * @return array
     */
    public function getAboutMeImages()
    {
        return $this->aboutMeImages;
    }

    /**
     * @param int $index
     * @return string|null
     */
    public function getAboutMeImageWebPath($index)
    {
        return (isset($this->aboutMeImages[$index]) && $this->aboutMeImages[$index] ?
            sprintf('%s/%s', self::ABOUT_ME_THUMBNAIL_NETWORK_DIRECTORY, $this->aboutMeImages[$index]) :
            null);
    }

    /**
     * @return array
     */
    public function getAboutMeImageWebPaths()
    {
        $paths = [];
        for ($i = 0; $i < 3; $i++) {
            $paths[] = $this->getAboutMeImageWebPath($i);
        }

        return array_filter($paths);
    }

    /**
     * @param int $index
     * @return string|null
     */
    public function getAboutMeThumbnailImageWebPath($index)
    {
        return (isset($this->aboutMeImages[$index]) && $this->aboutMeImages[$index] ?
            sprintf('%s/%s', self::ABOUT_ME_THUMBNAIL_NETWORK_DIRECTORY, $this->aboutMeImages[$index]) :
            null);
    }

    /**
     * @param array $aboutMeImages
     * @return UserProProfile
     */
    public function setAboutMeImages($aboutMeImages)
    {
        $this->aboutMeImages = $aboutMeImages;
        return $this;
    }

    /**
     * @param int $index
     * @param string $aboutMeImage
     * @return UserProProfile
     */
    public function setAboutMeImage($index, $aboutMeImage)
    {
        $this->aboutMeImages[$index] = $aboutMeImage;
        return $this;
    }

    /**
     * @return array
     */
    public function getLinksAsArray()
    {
        return [
            'facebook' => $this->facebookLink,
            'soundcloud' => $this->soundcloudLink,
            'instagram' => $this->instagramLink,
            'spotify' => $this->spotifyLink,
            'youtube' => $this->youtubeLink,
        ];
    }
}