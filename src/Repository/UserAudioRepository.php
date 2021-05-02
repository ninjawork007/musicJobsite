<?php

namespace App\Repository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query;

use App\Entity\UserAudio;
use App\Entity\UserInfo;
use Doctrine\Persistence\ManagerRegistry;


class UserAudioRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserAudio::class);
    }

    /**
     * Save uploaded file to database
     *
     * @param int    $userInfoId
     * @param string $title
     * @param string $fileName
     *
     * @return bool|object UserAudio entity
     */
    public function saveUploadedFile($userInfoId, $title, $fileName, $defaultAudio = null)
    {
        $em = $this->_em;
        $userAudios = $this->getProfileTracksByUser($userInfoId);

        // Check if file exists
        $uploadDir = __DIR__ . '/../../../../tmp';
        if (!file_exists($uploadDir . DIRECTORY_SEPARATOR . $fileName)) {
            echo $uploadDir . DIRECTORY_SEPARATOR . $fileName;
            return false;
        }

        $userInfo = $em->getReference('VocalizrAppBundle:UserInfo', $userInfoId);

        $userAudio = new UserAudio();
        $userAudio->setUserInfo($userInfo);
        $userAudio->setTitle($title);
        $userAudio->setPath($fileName);
        // This will move the file to the correct directory once entity is saved
        $file            = new \Symfony\Component\HttpFoundation\File\File($uploadDir . DIRECTORY_SEPARATOR . $fileName);
        $userAudio->file = $file;
        if (count($userAudios) == 0 || $defaultAudio) {
            $userAudio->setDefaultAudio(true);
        }

        // Calculate length
        $getID3   = new \getid3();
        $fileInfo = $getID3->analyze($uploadDir . DIRECTORY_SEPARATOR . $fileName);
        if (isset($fileInfo['playtime_seconds'])) {
            $milliseconds = $fileInfo['playtime_seconds'] * 1000;
            $userAudio->setDuration($milliseconds);
            $userAudio->setDurationString($fileInfo['playtime_string']);
        }

        $em->persist($userAudio);
        $em->flush();

        return $userAudio;
    }

    /**
     * @param string   $slug
     * @param UserInfo $user
     *
     * @return UserAudio|null
     */
    public function findOneBySlugAndUser($slug, $user)
    {
        $qb = $this->createQueryBuilder('ua');
        $qb
            ->where('ua.user_info = :user')
            ->andWhere('ua.slug = :slug')

            ->setParameters([
                'user' => $user,
                'slug' => $slug,
            ])
        ;

        try {
            return $qb->getQuery()->getSingleResult();
        } catch (NoResultException $e) {
            return null;
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    /**
     * Get default audio track for user
     *
     * @param int $userInfoId
     *
     * @return UserAudio
     */
    public function getDefaultAudioForUser($userInfoId)
    {
        $q = $this->createQueryBuilder('ua')
                ->select('ua, ui')
                ->innerJoin('ua.user_info', 'ui')
                ->where('ua.default_audio = 1 AND ua.user_info = :userInfoId');

        $params = [
            ':userInfoId' => $userInfoId,
        ];
        $q->setParameters($params);
        $query = $q->getQuery();

        try {
            return $query->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            return null;
        }
    }

    /**
     * @param $userIds
     * @return UserAudio[]
     */
    public function getDefaultAudiosForUsers($userIds)
    {
        $qb = $this->createQueryBuilder('ua');
        $qb
            ->select('ua, ui.id')
            ->innerJoin('ua.user_info', 'ui')
            ->where('ua.default_audio = 1')
            ->andWhere($qb->expr()->in('ua.user_info', $userIds))
        ;

        $result = $qb->getQuery()->getResult();

        return array_combine(array_column($result, 'id'), array_column($result, 0));
    }

    /**
     * Get tracks for user profile
     *
     * @param int|UserInfo $userInfo
     * @param bool         $hydrateArray
     * @param int          $limit
     *
     * @return array
     */
    public function getProfileTracksByUser($userInfo, $hydrateArray = true, $limit = null)
    {
        $qb = $this->createQueryBuilder('ua');
        $qb
            ->select('ua, ui')
            ->innerJoin('ua.user_info', 'ui')
            ->where('ua.user_info = :userInfo')
            ->orderBy('ua.default_audio', 'DESC')
            ->addOrderBy('ua.created_at', 'DESC')
        ;
        if (!is_null($limit)) {
            $qb->setMaxResults($limit);
        }

        $qb->setParameters([
            ':userInfo' => $userInfo,
        ]);
        $query = $qb->getQuery();

        return $query->getResult($hydrateArray ? AbstractQuery::HYDRATE_ARRAY : AbstractQuery::HYDRATE_OBJECT);
    }

    /**
     * Find user audio by id hydrate result in array
     *
     * @param int $id
     *
     * @return array|null
     */
    public function findOneById($id)
    {
        $q = $this->createQueryBuilder('ua')
                ->select('ua, ui')
                ->innerJoin('ua.user_info', 'ui')
                ->where('ua.id = :id');

        $params = [
            ':id' => $id,
        ];
        $q->setParameters($params);
        $query = $q->getQuery();

        return $query->getOneOrNullResult(Query::HYDRATE_ARRAY);
    }

    /**
     * Save track from user_sc_track table
     * This table is a cache of the users soundcloud account
     *
     * @param int         $userInfoId
     * @param UserScTrack $scTrack    Entity
     * @param bool        $default    Default track for user
     */
    public function saveTrackFromSoundCloud($userInfoId, $scTrack, $default = false)
    {
        $em = $this->_em;

        // If file is marked as default audio then
        // Clear any of tracks that are default for the new track that will be default
        if ($default) {
            // It's for user profile, so don't delete - just update
            $this->unsetDefaultAudio($userInfoId);
        }

        $entity = new UserAudio();
        $entity->setUserInfo($em->getReference('VocalizrAppBundle:UserInfo', $userInfoId));
        $entity->setScId($scTrack->getScId());
        $entity->setDuration($scTrack->getDuration());
        $entity->setDurationString($scTrack->getDuration());
        // Duration string
        $minutes = floor(($scTrack->getDuration() % (1000 * 60 * 60)) / (1000 * 60));
        $seconds = floor((($scTrack->getDuration() % (1000 * 60 * 60)) % (1000 * 60)) / 1000);
        $entity->setDurationString($minutes . ':' . str_pad($seconds, 2, 0, STR_PAD_LEFT));

        $entity->setScSynced(true);
        $entity->setTitle($scTrack->getTitle());
        $entity->setScPermalinkUrl($scTrack->getPermalinkUrl());
        $entity->setScStreamUrl($scTrack->getStreamUrl());
        $entity->setDefaultAudio($default);
        $entity->setScRaw($scTrack->getRawApiResult());

        $em->persist($entity);
        $em->flush();
    }

    /**
     * Save track from soundcloud api response
     *
     * @param int         $userInfoId
     * @param UserScTrack $scTrack    Entity
     * @param bool        $default    Default track for user
     */
    public function saveTrackFromSoundCloudApi($userInfo, $track)
    {
        $em = $this->_em;

        $entity = new UserAudio();
        $entity->setUserInfo($userInfo);
        $entity->setScId($track->id);
        $entity->setDuration($track->duration);
        // Duration string
        $minutes = floor(($track->duration % (1000 * 60 * 60)) / (1000 * 60));
        $seconds = floor((($track->duration % (1000 * 60 * 60)) % (1000 * 60)) / 1000);
        $entity->setDurationString($minutes . ':' . str_pad($seconds, 2, 0, STR_PAD_LEFT));

        $entity->setScSynced(true);
        $entity->setTitle($track->title);
        $entity->setScPermalinkUrl($track->permalink_url);
        $entity->setScStreamUrl($track->stream_url);
        $entity->setDefaultAudio(true);
        $entity->setScRaw(json_encode($track));

        $em->persist($entity);
        $em->flush();
    }

    /**
     * Delete audio that is marked as default
     *
     * @param int $userInfoId
     */
    public function deleteDefaultAudio($userInfoId)
    {
        $q = $this->createQueryBuilder('ua')
                ->where('ua.user_info = :userInfoId AND ua.default_audio = :defaultAudio');

        $params = [
            ':userInfoId'   => $userInfoId,
            ':defaultAudio' => true,
        ];
        $q->setParameters($params);
        $query = $q->getQuery();
        if ($results = $query->execute()) {
            $result = current($results);
            $this->_em->remove($result);
            $this->_em->flush();
        }
    }

    /**
     * Unset default audio for user
     *
     * @param int $userInfoId
     */
    public function unsetDefaultAudio($userInfoId)
    {
        $q = $this->createQueryBuilder('ua')
                ->update()
                ->set('ua.default_audio', "'0'")
                ->where('ua.user_info = :userInfoId AND ua.default_audio = :defaultAudio');
        $params = [
            ':userInfoId'   => $userInfoId,
            ':defaultAudio' => true,
        ];
        $q->setParameters($params);
        $query = $q->getQuery();
        $query->execute();
    }

    /**
     * Update latest audio track to default
     */
    public function setLatestAudioToDefault($userInfoId)
    {
        // Unset current default audio
        $this->unsetDefaultAudio($userInfoId);

        $this->_em->getConnection()->executeUpdate(
            "UPDATE user_audio ua SET ua.default_audio = '1'
             WHERE ua.user_info_id = '" . $userInfoId . "' ORDER BY ua.created_at DESC
             LIMIT 1"
        );
    }

    /**
     * Set audio as default audio
     *
     * @param int $userInfoId
     * @param int $userAudioId
     */
    public function setAudioAsDefaultAudio($userInfoId, $userAudioId)
    {
        $this->unsetDefaultAudio($userInfoId);

        $q = $this->createQueryBuilder('ua')
                ->update()
                ->set('ua.default_audio', "'1'")
                ->where('ua.id = :userAudioId');
        $q->setParameter(':userAudioId', $userAudioId);
        $query = $q->getQuery();
        $query->execute();
    }
}
