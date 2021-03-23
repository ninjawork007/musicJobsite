<?php

namespace Vocalizr\AppBundle\Model;

use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\File\File;
use Vocalizr\AppBundle\Document\AudioLike;
use Vocalizr\AppBundle\Entity\SubscriptionPlan;
use Vocalizr\AppBundle\Entity\UserAudio;
use Vocalizr\AppBundle\Entity\UserInfo;
use Vocalizr\AppBundle\Repository\UserAudioRepository;
use Vocalizr\AppBundle\Service\MediaInfoService;
use Vocalizr\AppBundle\Service\ProcessImageService;

/**
 * Class UserAudioModel
 *
 * @package Vocalizr\AppBundle\Model
 *
 * @property UserAudioRepository $repository
 */
class UserAudioModel extends Model
{
    /** @var SubscriptionPlan|null */
    private $freePlan;

    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var string
     */
    private $uploadDir;

    /**
     * @var MediaInfoService
     */
    private $mediaInfo;

    /**
     * @var ProcessImageService
     */
    private $imageProcessor;

    /**
     * @param DocumentManager $dm
     * @param MediaInfoService $mediaInfo
     * @param ProcessImageService $imageProcessor
     * @param string $uploadDir
     */
    public function __construct(DocumentManager $dm, MediaInfoService $mediaInfo, ProcessImageService $imageProcessor, $uploadDir)
    {
        $this->dm = $dm;
        $this->uploadDir = $uploadDir;
        $this->mediaInfo = $mediaInfo;
        $this->imageProcessor = $imageProcessor;
    }

    /**
     * Save uploaded file to database
     *
     * @param int|UserInfo $userInfoId
     * @param string       $title
     * @param string       $fileName
     * @param bool|null    $defaultAudio
     *
     * @return UserAudio|bool
     */
    public function saveUploadedAudio($userInfoId, $title, $fileName, $defaultAudio = null)
    {
        $filePath = $this->uploadDir . DIRECTORY_SEPARATOR . $fileName;
        if (!file_exists($filePath)) {
            error_log(sprintf('Could not save UserAudio. Uploaded file "%s" was not found.', $filePath));
            return null;
        }

        if (is_int($userInfoId)) {
            $userInfo = $this->em->getReference('VocalizrAppBundle:UserInfo', $userInfoId);
        } else {
            $userInfo = $userInfoId;
        }

        $userAudio = (new UserAudio())
            ->setUserInfo($userInfo)
            ->setTitle($title)
            ->setPath($fileName)
        ;

        $userAudios = $this->repository->getProfileTracksByUser($userInfoId);

        // This will move the file to the correct directory once entity is saved
        $file = new File($filePath);

        $userAudio->file = $file;

        if (count($userAudios) == 0 || $defaultAudio) {
            $userAudio->setDefaultAudio(true);
        }

        $analyze = $this->mediaInfo->analyzeAudio($filePath);

        $userAudio
            ->setDuration($analyze['playtime_seconds'] * 1000)
            ->setDurationString($analyze['playtime_string'])
        ;

        if ($pictureData = $analyze['pictureData']) {
            try {
                $picTempPath = $this->uploadDir . DIRECTORY_SEPARATOR . uniqid('art_', true);
                file_put_contents($picTempPath, $pictureData);

                $imagePath = $this->imageProcessor->processUploadedImage($picTempPath, '', [
                    UserAudio::ALBUM_ARTS_WEB_DIRECTORY => [
                        'processing' => [
                            'square' => 150,
                        ]
                    ]
                ]);

                $userAudio->setAlbumArt($imagePath);
                @unlink($picTempPath);

            } catch (\Exception $e) {
                error_log('An error occurred while saving album art. ' . $e->getMessage());
            } catch (\Error $e) {
                error_log('An error occurred while saving album art. ' . $e->getMessage());
            }
        }

        $this->em->persist($userAudio);
        $this->em->flush();

        return $userAudio;
    }

    /**
     * @param UserInfo $user
     *
     * @return int
     */
    public function getAudioLimit(UserInfo $user)
    {
        if ($user->getSubscriptionPlan()) {
            return $user->getSubscriptionPlan()->getUserAudioLimit();
        }

        if (!$this->freePlan) {
            $this->freePlan = $this->em->getRepository('VocalizrAppBundle:SubscriptionPlan')->findOneBy([
                'static_key' => 'FREE',
            ]);
        }

        return $this->freePlan->getUserAudioLimit();
    }

    /**
     * @param UserInfo $userInfo
     * @param bool     $hydrateArray
     *
     * @return array|UserAudio[]
     */
    public function getUserAudios(UserInfo $userInfo, $hydrateArray = false)
    {
        return $this->repository->getProfileTracksByUser($userInfo, $hydrateArray, $this->getAudioLimit($userInfo));
    }

    /**
     * @param UserInfo $user
     * @return UserAudio[]
     */
    public function getTopTracks(UserInfo $user)
    {
        return $this->repository->getProfileTracksByUser($user, false, 4);
    }

    /**
     * @param UserInfo|null $fromUser
     * @param UserAudio[] $userAudios
     * @return array
     */
    public function getAudioLikes($fromUser, $userAudios)
    {
        $audioIds = [];
        $audioLikes = [];

        foreach ($userAudios as $audio) {
            if ($fromUser !== $audio->getUserInfo()) {
                $audioIds[] = $audio->getId();
            }
        }

        if (empty($audioIds) || !$fromUser) {
            return [];
        }

        $qb = $this->dm->createQueryBuilder('VocalizrAppBundle:AudioLike')
            ->field('from_user_id')->equals($fromUser->getId())
            ->field('audio_id')->in($audioIds)
        ;

        /** @var AudioLike[] $results */
        $results = $qb->getQuery()->execute();

        foreach ($results as $result) {
            $audioLikes[] = $result->getAudioId();
        }

        return $audioLikes;
    }

    protected function getEntityName()
    {
        return 'VocalizrAppBundle:UserAudio';
    }
}