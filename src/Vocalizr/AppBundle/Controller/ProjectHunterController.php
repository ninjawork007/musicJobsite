<?php

namespace Vocalizr\AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Vocalizr\AppBundle\Entity\Language;
use Vocalizr\AppBundle\Entity\Project;
use Vocalizr\AppBundle\Form\Type\ProjectSearchType;

class ProjectHunterController extends Controller
{
    /**
     * @Route("/jobs/{filter}", defaults={"filter" = "latest"}, name="gig_hunter")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $em          = $this->getDoctrine()->getEntityManager();
        $projectRepo = $em->getRepository('VocalizrAppBundle:Project');
        $user = $this->getUser();

        if (!$user) {
            return $this->redirect($this->generateUrl('home'));
        }
        $ymlParser  = new \Symfony\Component\Yaml\Parser();
        $file       = $this->get('kernel')->getRootDir() . '/../src/Vocalizr/AppBundle/Resources/config/project.yml';
        $projectYml = $ymlParser->parse(file_get_contents($file));

        $form = $this->createForm(new ProjectSearchType($projectYml['budget']));
        $page = $request->get('page', 1);
        $user = $this->getUser();

        if ($request->get('filter')) {
            $request->getSession()->set('gig_filter', $request->get('filter'));
        }

        $filter = $request->getSession()->get('gig_filter', 'latest');

        $date = new \DateTime();
        $date->sub(new \DateInterval('P2D'));

        $publishedAt = new \DateTime();
        $publishedAt->modify('-7 days');

        $q = $projectRepo->createQueryBuilder('p');
        $q->select('p, pa, ui');
        $q->innerJoin('p.user_info', 'ui');
        $q->leftJoin('p.project_audio', 'pa', 'WITH', "pa.flag = '" . \Vocalizr\AppBundle\Entity\ProjectAudio::FLAG_FEATURED . "'");
        $q->andWhere('p.is_active = true');
        $q->andWhere('p.bids_due >= :bidsDue');
        $q->andWhere('p.publish_type = :publishType');
        $q->andWhere('p.employee_user_info is null');
        $q->andWhere('(p.featured = 0 OR (p.featured = 1 and p.published_at <= :publishedAt))');

        $params = [
            ':publishType' => Project::PUBLISH_PUBLIC,
            ':bidsDue'     => $date,
            ':publishedAt' => $publishedAt,
        ];

        //$q->andWhere('p.project_type = :filter');
        //$params['filter'] = Project::PROJECT_TYPE_PAID;

        /*
          if ($request->get('filter'))
          {

          if ($request->get('filter') == "paid") {
          return $this->redirect('/gig-hunter', 302);
          }
          if ($request->get('filter') == "collaborations") {
          $q->andWhere('p.project_type = :filter');
          $params['filter'] = Project::PROJECT_TYPE_COLLABORATION;
          }

          }
         *
         */

        if ($filter) {
            switch ($filter) {
                case 'latest':
                    $q->orderBy('p.published_at', 'DESC');
                    break;
                case 'ending_soon':
                    $params[':bidsDue'] = new \DateTime();
                    $q->orderBy('p.bids_due', 'ASC');
                    break;
                case 'lowest_bids':
                    $q->orderBy('p.num_bids', 'ASC');
                    break;
                case 'budget':
                    $q->orderBy('p.budget_from', 'DESC');
                    break;
                default:
                    $q->orderBy('p.published_at', 'DESC');
                    break;
            }
        }

        if ($user) {
            $q->addSelect('pbs');
            $q->leftJoin('p.project_bids', 'pbs', 'WITH', 'pbs.user_info = :userInfoId');
            $params[':userInfoId'] = $user->getId();
        }

        $requestData = [];
        if ($request->get('project_search')) {
            $requestData = $request->get('project_search');
        }

        if (!$request->get('search')) {
            $requestData['project_type'] = ['paid', 'contest'];
            $requestData['looking_for']  = ['vocalist', 'producer'];
        }

        $form->bind($requestData);

        if ($request->get('search')) {
            if ($form->isValid()) {
                $data = $form->getData();

                if ($data['keywords']) {
                    $q->andWhere('(UPPER(p.title) LIKE :keywords OR UPPER(p.description) LIKE :keywords)');
                    $params[':keywords'] = '%' . strtoupper($data['keywords']) . '%';
                }

                if ($data['gender']) {
                    $q->andWhere('p.gender = :gender OR p.gender IS NULL');
                    $params[':gender'] = $data['gender'];
                }

                if (count($data['genre']) > 0) {
                    $genreIds = [];
                    foreach ($data['genre'] as $genre) {
                        $genreIds[] = $genre->getId();
                    }
                    $q->innerJoin('p.genres', 'g');
                    $q->andWhere($q->expr()->in('g.id', $genreIds));
                }

                if ($data['studio_access']) {
                    $q->andWhere('p.studio_access = 1');
                }

                if (count($data['project_type']) > 0) {
                    $ptypes = [];
                    foreach ($data['project_type'] as $type) {
                        $ptypes[] = $type;
                    }
                    $q->andWhere($q->expr()->in('p.project_type', ':projecttypes'));
                    $params[':projecttypes'] = $ptypes;
                }

                if (count($data['looking_for']) > 0) {
                    $ptypes = [];
                    foreach ($data['looking_for'] as $type) {
                        $ptypes[] = $type;
                    }
                    $q->andWhere($q->expr()->in('p.looking_for', ':lookingfor'));
                    $params[':lookingfor'] = $ptypes;
                }

                if (count($data['vocal_characteristic']) > 0) {
                    $vocalIds = [];
                    foreach ($data['vocal_characteristic'] as $vocal) {
                        $vocalIds[] = $vocal->getId();
                    }
                    $q->innerJoin('p.vocalCharacteristics', 'vc');
                    $q->andWhere($q->expr()->in('vc.id', ':vocalids'));
                    $params[':vocalids'] = $vocalIds;
                }

                if (count($data['vocal_style']) > 0) {
                    $vocalIds = [];
                    foreach ($data['vocal_style'] as $vocal) {
                        $vocalIds[] = $vocal->getId();
                    }
                    $q->innerJoin('p.vocalStyles', 'vs');
                    $q->andWhere($q->expr()->in('vs.id', ':vocalstyles'));
                    $params[':vocalstyles'] = $vocalIds;
                }

                $budget = $data['budget'];
                if ($budget) {
                    list($min, $max) = explode('-', $budget);
                    if ($min) {
                        $q->andWhere('p.budget_from >= :min');
                        $params[':min'] = $min;
                    }
                    if ($max) {
                        $q->andWhere('p.budget_to <= :max');
                        $params[':max'] = $max;
                    }
                }
                /** @var Language[] $languages */
                $languages = $data['languages'];
                if (count($languages) > 0) {
                    $langIds = [];
                    foreach ($languages as $language) {
                        $langIds[] = $language->getId();
                    }
                    $q->leftJoin('p.language', 'l');
                    $q->andWhere($q->expr()->in('l', ':languages'));
                    $params[':languages'] = $langIds;
                }
            }
        }

        if (isset($params) && count($params) > 0) {
            $q->setParameters($params);
        }

        $query = $q->getQuery();

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $this->get('request')->query->get('page', 1)/* page number */,
            20// limit per page
        );

        // Get featured projects
        $publishedAt = new \DateTime();
        $publishedAt->modify('-7 days');

        $q = $projectRepo->createQueryBuilder('p');
        $q->select('p, pa, ui');
        $q->innerJoin('p.user_info', 'ui');
        $q->leftJoin('p.project_audio', 'pa', 'WITH', "pa.flag = '" . \Vocalizr\AppBundle\Entity\ProjectAudio::FLAG_FEATURED . "'");
        $q->andWhere('p.is_active = true');
        $q->andWhere('p.bids_due >= :bidsDue');
        $q->andWhere('p.publish_type = :publishType');
        $q->andWhere('p.employee_user_info is null');
        $q->andWhere('p.published_at >= :publishedAt');
        $q->andWhere('p.featured = 1');
        $q->orderBy('p.published_at', 'DESC');

        $params = [
            ':publishType' => Project::PUBLISH_PUBLIC,
            'publishedAt'  => $publishedAt,
            ':bidsDue'     => $date,
        ];

        if ($user) {
            $q->addSelect('pbs');
            $q->leftJoin('p.project_bids', 'pbs', 'WITH', 'pbs.user_info = :userInfoId');
            $params[':userInfoId'] = $user->getId();
        }
        if ($request->get('search')) {
            if ($form->isValid()) {
                $data = $form->getData();
                if (count($data['project_type']) > 0) {
                    $ptypes = [];
                    foreach ($data['project_type'] as $type) {
                        $ptypes[] = $type;
                    }
                    $q->andWhere($q->expr()->in('p.project_type', $ptypes));
                }
            }
        }

        if (isset($data['looking_for']) && count($data['looking_for']) > 0) {
            $ptypes = [];
            foreach ($data['looking_for'] as $type) {
                $ptypes[] = $type;
            }
            $q->andWhere($q->expr()->in('p.looking_for', ':lookingfor'));
            $params[':lookingfor'] = $ptypes;
        }

        $q->setParameters($params);
        $query    = $q->getQuery();
        $featured = $query->execute();

        // Check for sfs promotion
        $qb = $em->getRepository('VocalizrAppBundle:Project')
                ->createQueryBuilder('p')
                ->where('p.sfs = 1 AND p.bids_due >= :bidsDue');
        $qb->setParameters([
            'bidsDue' => new \DateTime(),
        ]);
        $query = $qb->getQuery();

        $result = $query->execute();
        $sfs    = false;
        if ($result) {
            $sfs = $result[0];
        }
        $data['sfs'] = $sfs;

        return [
            'form'       => $form->createView(),
            'pagination' => $pagination,
            'filter'     => $filter,
            'featured'   => $featured,
            'sfs'        => $sfs,
        ];
    }
}
