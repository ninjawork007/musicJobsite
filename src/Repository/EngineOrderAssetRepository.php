<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use App\Entity\EngineOrderAsset;

class EngineOrderAssetRepository extends EntityRepository
{
    /**
     * Save uploaded file to database
     *
     * @param int    $userInfoId
     * @param int    $engineOrderId
     * @param string $title
     * @param string $fileName      tmp file name in database
     *
     * @return bool|object ProjectAsset entity
     */
    public function saveUploadedFile($userInfoId, $engineOrderId, $title, $fileName)
    {
        $em = $this->getEntityManager();

        // Check if file exists
        $uploadDir = __DIR__ . '/../../../../tmp';

        if (!file_exists($uploadDir . DIRECTORY_SEPARATOR . $fileName)) {
            return false;
        }

        $userInfo = $em->getReference('App:UserInfo', $userInfoId);
        $project  = $em->getReference('App:EngineOrder', $engineOrderId);

        $pa = new EngineOrderAsset();
        $pa->setUserInfo($userInfo);
        $pa->setEngineOrder($project);
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

    /**
     * Get asset by slug and engine order
     *
     * @param string      $slug
     * @param EngineOrder $engineOrder
     *
     * @return EngineOrderAsset|null
     */
    public function getBySlugAndEngineOrder($slug, $engineOrder)
    {
        $q = $this->createQueryBuilder('pa')
                ->select('pa, ui, eo')
                ->innerJoin('pa.engine_order', 'eo')
                ->innerJoin('pa.user_info', 'ui')
                ->where('pa.slug = :slug AND pa.engine_order = :engineOrder');
        $params = [
            ':slug'        => $slug,
            ':engineOrder' => $engineOrder,
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
