<?php

namespace App\Repository;

use Doctrine\ORM\EntityRepository;
use App\Entity\ProjectContract;

class ProjectContractRepository extends EntityRepository
{
    /**
     * Save uploaded file to database
     *
     * @param int    $userInfoId
     * @param int    $projectId
     * @param string $title
     * @param string $fileName   tmp file name in database
     *
     * @return bool|ProjectContract
     */
    public function saveUploadedFile($userInfoId, $projectId, $title, $fileName)
    {
        $em = $this->getEntityManager();

        // Check if file exists
        $uploadDir = __DIR__ . '/../../tmp';

        if (!file_exists($uploadDir . DIRECTORY_SEPARATOR . $fileName)) {
            return false;
        }

        $userInfo = $em->getReference('App:UserInfo', $userInfoId);
        $project  = $em->getReference('App:Project', $projectId);

        $pc = new ProjectContract();
        $pc->setUserInfo($userInfo);
        $pc->setProject($project);
        $pc->setTitle($title);

        $pc->setPath($fileName);
        // This will move the file to the correct directory once entity is saved
        $file     = new \Symfony\Component\HttpFoundation\File\File($uploadDir . DIRECTORY_SEPARATOR . $fileName);
        $pc->file = $file;

        $em->persist($pc);
        $em->flush();

        return $pc;
    }

    /**
     * Delete contract by slug and user id
     *
     * @param int    $userInfoId
     * @param string $slug
     *
     * @return int
     */
    public function deleteByUserAndSlug($userInfoId, $slug)
    {
        $q = $this->_em->createQuery('DELETE FROM App:ProjectContract pc 
            WHERE pc.user_info = :userInfoId AND pc.slug = :slug');
        $q->setParameters([
            ':userInfoId' => $userInfoId,
            ':slug'       => $slug,
        ]);
        return $q->execute();
    }
}
