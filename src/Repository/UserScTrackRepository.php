<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;

use App\Entity\UserScTrack;

class UserScTrackRepository extends EntityRepository
{
    /**
     * Save to database from SoundCloud Api result
     *
     * @param int   $userInfoId
     * @param array $tracks
     *
     * @return array $tracks
     */
    public function saveFromSoundcloud($userInfoId, $tracks)
    {
        if (count($tracks) == 0) {
            return false;
        }

        // Clear table for user
        $this->deleteByUserInfoId($userInfoId);

        foreach ($tracks as $track) {
            $entity   = new UserScTrack();
            $userInfo = $this->_em->getReference('App:UserInfo', $userInfoId);
            $entity->setUserInfo($userInfo);
            $entity->setScId($track->id);
            $entity->setTitle($track->title);
            $entity->setDescription($track->description);
            $entity->setDuration($track->duration);

            $minutes = floor(($track->duration % (1000 * 60 * 60)) / (1000 * 60));
            $seconds = floor((($track->duration % (1000 * 60 * 60)) % (1000 * 60)) / 1000);

            $entity->setDurationString($minutes . ':' . str_pad($seconds, 2, 0, STR_PAD_LEFT));

            $entity->setGenre($track->genre);
            $entity->setBpm($track->bpm);
            $entity->setPermalinkUrl($track->permalink_url);
            $entity->setStreamUrl($track->stream_url);
            $entity->setRawApiResult(json_encode($track));
            $this->_em->persist($entity);
        }

        $this->_em->flush();

        return $tracks;
    }

    /**
     * Delete entries by user info id
     *
     * @param int $userInfoId
     *
     * @return int
     */
    public function deleteByUserInfoId($userInfoId)
    {
        $q = $this->createQueryBuilder('t')
                ->delete('App:UserScTrack', 't')
                ->where('t.user_info = :userInfoId')
                ->setParameter(':userInfoId', $userInfoId);
        $query = $q->getQuery();

        return $query->execute();
    }

    /**
     * Get tracks by user info id
     *
     * @param int $userInfoId
     *
     * @return array
     */
    public function getTracksByUserInfoId($userInfoId)
    {
        $q = $this->createQueryBuilder('t')
                ->select('t')
                //->leftJoin('t.user_audio', 'ua', 'WITH', 'ua.user_info = '.$userInfoId)
                ->where('t.user_info = :userInfoId');

        $params = [
            ':userInfoId' => $userInfoId,
        ];
        $q->setParameters($params);
        $query = $q->getQuery();

        return $query->getArrayResult();
    }
}
