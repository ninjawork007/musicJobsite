<?php

namespace Vocalizr\AppBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Vocalizr\AppBundle\Entity\MessageFile;

class MessageFileRepository extends EntityRepository
{
    /**
     * Save uploaded file to database
     *
     * @param int    $userInfo
     * @param int    $message
     * @param string $title
     * @param string $fileName tmp file name in database
     *
     * @return bool|object MessageFile
     */
    public function saveUploadedFile($userInfo, $project, $message, $title, $fileName)
    {
        $em = $this->getEntityManager();

        // Check if file exists
        $uploadDir = __DIR__ . '/../../../../tmp';

        if (!file_exists($uploadDir . DIRECTORY_SEPARATOR . $fileName)) {
            return false;
        }

        $fileSize = filesize($uploadDir . DIRECTORY_SEPARATOR . $fileName);

        $pa = new MessageFile();
        $pa->setUserInfo($userInfo);
        $pa->setProject($project);
        $pa->setMessage($message);
        $pa->setTitle($title);
        $pa->setPath($fileName);
        $pa->setFilesize($fileSize);

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

        $em->persist($pa);
        $em->flush();

        return $pa;
    }

    public function saveDropboxFile($userInfo, $project, $message, $data)
    {
        $em = $this->getEntityManager();

        $mf = new MessageFile();
        $mf->setUserInfo($userInfo);
        $mf->setProject($project);
        $mf->setMessage($message);
        $mf->setTitle($data['name']);
        $mf->setFilesize($data['size']);
        $mf->setDropboxLink($data['link']);

        $em->persist($mf);
        $em->flush();

        return $mf;
    }

    /**
     * Get file by slug
     *
     * @param string $slug
     *
     * @return ProjectAsset|null
     */
    public function getBySlug($slug)
    {
        $q = $this->createQueryBuilder('mf')
                ->select('mf, ui')
                ->innerJoin('pa.user_info', 'ui')
                ->where('mf.slug = :slug');
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
