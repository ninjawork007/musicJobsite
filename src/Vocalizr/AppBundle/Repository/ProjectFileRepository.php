<?php

namespace Vocalizr\AppBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Vocalizr\AppBundle\Entity\ProjectFile;

class ProjectFileRepository extends EntityRepository
{
    public function saveDropboxFile($userInfo, $project, $projectComment, $data)
    {
        $em = $this->getEntityManager();

        $pa = new ProjectFile();
        $pa->setUserInfo($userInfo);
        $pa->setProject($project);
        $pa->setProjectComment($projectComment);
        $pa->setTitle($data['name']);
        $pa->setDropboxLink($data['link']);

        return $pa;
    }

    /**
     * Get file by slug
     *
     * @param string $slug
     *
     * @return ProjectFile|null
     */
    public function getBySlug($slug)
    {
        $q = $this->createQueryBuilder('pa')
                ->select('pf, ui')
                ->innerJoin('pf.user_info', 'ui')
                ->where('pf.slug = :slug');
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
