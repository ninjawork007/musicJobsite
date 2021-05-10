<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\ORMException;
use App\Entity\ProjectAudio;

class ProjectAudioRepository extends EntityRepository
{
    /**
     * Save uploaded file to database
     *
     * @param int    $projectId
     * @param int    $userInfoId
     * @param string $title
     * @param string $fileName
     * @param string $flag
     *
     * @return bool|object ProjectAudio entity
     */
    public function saveUploadedFile($projectId, $userInfoId, $title, $fileName, $flag)
    {
        try {
            $em = $this->_em;

            dd(__DIR__);
            // Check if file exists
            $uploadDir = __DIR__ . '/../../../../tmp';
            if (!file_exists($uploadDir . DIRECTORY_SEPARATOR . $fileName)) {
                return false;
            }

            $project  = $em->getReference('AppApp:Project', $projectId);
            $userInfo = $em->getReference('App:UserInfo', $userInfoId);

            $projectAudio = new ProjectAudio();
            $projectAudio->setUserInfo($userInfo);
            $projectAudio->setProject($project);

            $projectAudio->setTitle($title);
            $projectAudio->setPath($fileName);
            // This will move the file to the correct directory once entity is saved
            $file               = new \Symfony\Component\HttpFoundation\File\File($uploadDir . DIRECTORY_SEPARATOR . $fileName);
            $projectAudio->file = $file;

            // Calculate length
            $getID3   = new \getid3();
            $fileInfo = $getID3->analyze($uploadDir . DIRECTORY_SEPARATOR . $fileName);
            if (isset($fileInfo['playtime_seconds'])) {
                $milliseconds = $fileInfo['playtime_seconds'] * 1000;
                $projectAudio->setDuration($milliseconds);
                $projectAudio->setDurationString($fileInfo['playtime_string']);
            }
            $projectAudio->setFlag($flag);

            $em->persist($projectAudio);
            $em->flush();

            return $projectAudio;

        } catch (ORMException $e) {
            error_log('An exception has been thrown while saving uploaded project audio file: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @param array $projectIds
     * @return ProjectAudio[]
     */
    public function getFeaturedAudiosForProjects(&$projectIds = [])
    {
        $qb = $this->createQueryBuilder('pa');
        $qb
            ->select('pa, p.id')
            ->innerJoin('pa.project', 'p')
            ->where('pa.flag = :flag')
            ->andWhere($qb->expr()->in('p.id', $projectIds))
            ->setParameter('flag', ProjectAudio::FLAG_FEATURED)
        ;

        $result = $qb->getQuery()->getResult();

        return array_combine(array_column($result, 'id'), array_column($result, 0));
    }

    /**
     * @param array[] $feedItems
     * @param int $projectId
     * @return ProjectAudio[]
     */
    public function getFeedAudio($feedItems, $projectId)
    {
        $audioSlugs = [];
        foreach ($feedItems as $feedItem) {
            $data = json_decode($feedItem['data'], true);

            if (isset($data['audio_slug'])) {
                $audioSlugs[$feedItem['id']] = $data['audio_slug'];
            }
        }

        if (!$audioSlugs) {
            return [];
        }

        $qb = $this->createQueryBuilder('pa');
        $qb
            ->where($qb->expr()->in('pa.slug', $audioSlugs))
            ->andWhere('pa.project = :project_id')

            ->setParameter('project_id', $projectId)
        ;

        /** @var ProjectAudio[] $audios */
        $audios = $qb->getQuery()->getResult();
        $indexedAudios = [];

        foreach ($audios as $audio) {
            $feedItemId = array_search($audio->getSlug(), $audioSlugs);
            $indexedAudios[$feedItemId] = $audio;
        }

        return $indexedAudios;
    }
}
