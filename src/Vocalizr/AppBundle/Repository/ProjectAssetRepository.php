<?php

namespace Vocalizr\AppBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Vocalizr\AppBundle\Entity\ProjectAsset;

class ProjectAssetRepository extends EntityRepository
{
    /**
     * Save uploaded file to database
     *
     * @param int    $userInfoId
     * @param int    $projectId
     * @param string $title
     * @param string $fileName   tmp file name in database
     *
     * @return bool|object ProjectAsset entity
     */
    public function saveUploadedFile($userInfoId, $projectId, $title, $fileName)
    {
        $em = $this->getEntityManager();

        // Check if file exists
        $uploadDir = __DIR__ . '/../../../../tmp';

        if (!file_exists($uploadDir . DIRECTORY_SEPARATOR . $fileName)) {
            return false;
        }

        $userInfo = $em->getReference('VocalizrAppBundle:UserInfo', $userInfoId);
        $project  = $em->getReference('VocalizrAppBundle:Project', $projectId);

        $pa = new ProjectAsset();
        $pa->setUserInfo($userInfo);
        $pa->setProject($project);
        $pa->setTitle($title);

        $pa->setPath($fileName);
        // This will move the file to the correct directory once entity is saved
        $file     = new \Symfony\Component\HttpFoundation\File\File($uploadDir . DIRECTORY_SEPARATOR . $fileName);
        $pa->file = $file;

        // Get mime type
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mimeType = finfo_file($finfo, $uploadDir . DIRECTORY_SEPARATOR . $fileName);
                finfo_close($finfo);

                $pa->setMimeType($mimeType);
            }
        }

        // Calculate length of audio file
        $getID3   = new \getid3();
        $fileInfo = $getID3->analyze($uploadDir . DIRECTORY_SEPARATOR . $fileName);
        if (isset($fileInfo['playtime_seconds'])) {
            $milliseconds = $fileInfo['playtime_seconds'] * 1000;
            $pa->setDuration($milliseconds);
            $pa->setDurationString($fileInfo['playtime_string']);
        }

        $em->persist($pa);
        $em->flush();

        return $pa;
    }

    public function saveDropboxFile($userInfo, $project, $data)
    {
        $em = $this->getEntityManager();

        $pa = new ProjectAsset();
        $pa->setUserInfo($userInfo);
        $pa->setProject($project);
        $pa->setTitle($data['name']);
        $pa->setDropboxLink($data['link']);

        $em->persist($pa);
        $em->flush();

        return $pa;
    }

    /**
     * Get assets by project id
     * join user_info table
     *
     * @param int $projectId
     */
    public function getByProjectId($projectId)
    {
        $q = $this->createQueryBuilder('pa');
        $q
            ->select('pa, ui')
            ->innerJoin('pa.user_info', 'ui')
            ->where('pa.project = :projectId')
            ->andWhere('pa.preview_path is not null')
            ->orderBy('pa.created_at', 'DESC')
        ;
        $params = [
            ':projectId' => $projectId,
        ];
        $q->setParameters($params);

        $query = $q->getQuery();

        return $query->execute();
    }

    /**
     * Get asset by slug
     *
     * @param string $slug
     *
     * @return ProjectAsset|null
     */
    public function getBySlug($slug)
    {
        $q = $this->createQueryBuilder('pa')
                ->select('pa, ui')
                ->innerJoin('pa.user_info', 'ui')
                ->where('pa.slug = :slug');
        $params = [
            ':slug' => $slug,
        ];
        $q->setParameters($params);

        $query = $q->getQuery();

        try {
            return $query->getOneOrNullResult();
        } catch (\Doctrine\ORM\NonUniqueResultException $e) {
            return null;
        }
    }
}
