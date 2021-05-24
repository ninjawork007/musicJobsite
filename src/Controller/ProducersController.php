<?php

namespace App\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

use App\Entity\Project;
use App\Entity\UserInfo;
use App\Form\Type\UserSearchType;

class ProducersController extends AbstractUserSearchController
{
    /**
     * @Route("/producers", name="producers")
     * @Route("/producers/gig/{project}", defaults={"project" = ""}, name="producers_gig")
     * @Route("/producers/sort/{filter}", defaults={"filter" = ""}, name="producers_filter")
     * @Template()
     * @param Request $request
     * @param ContainerInterface $container
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request, ContainerInterface $container)
    {
        $em           = $this->getDoctrine()->getManager();
        $user         = $this->getUser();
        $userInfoRepo = $em->getRepository('App:UserInfo');

        if (!$user) {
            return $this->redirect($this->generateUrl('home'));
        }
        $q = $userInfoRepo->createQueryBuilder('ui');
        $q->leftJoin('ui.user_audio', 'ua', 'WITH', 'ua.default_audio = 1');
        $q->leftJoin('ui.genres', 'g');
        $q->where('ui.is_producer = 1 AND ui.is_active = 1');

        if (!($filter = $request->get('filter'))) {
            $filter = $request->getSession()->get('producer_filter');
        }
        if (!in_array($filter, ['newest', 'latest', 'random', 'shuffle_certified'])) {
            $filter = 'shuffle_certified';
        }
        $request->getSession()->set('producer_filter', $filter);

        $lockCertified = ($filter === 'shuffle_certified');

        $form = $this->createForm(UserSearchType::class, [
            'budget'        => $this->getProjectConfigData()['budget'],
            'lockCertified' => $lockCertified
        ]);

        $results = [];

        if (!$request->get('search')) {
            $_REQUEST[$form->getName()]['audio'] = true;
        }

//        $form->bind($_REQUEST[$form->getName()]);
//        $form->handleRequest($_REQUEST[$form->getName()]);
        $form->handleRequest($request);

        if ($request->get('search')) {
            if ($form->isSubmitted() && $form->isValid()) {

                $data = $form->getData();

                if ($data['gender']) {
                    $q->andWhere('ui.gender = :gender');
                    $params[':gender'] = $data['gender'];
                }

                if (count($data['genre']) > 0) {
                    $genreIds = [];
                    foreach ($data['genre'] as $genre) {
                        $genreIds[] = $genre->getId();
                    }
                    $q->andWhere($q->expr()->in('g.id', $genreIds));
                }

                if ($data['sounds_like']) {
                    $q->addSelect('uvt, vt');
                    $q->innerJoin('ui.user_voice_tags', 'uvt');
                    $q->innerJoin('uvt.voice_tag', 'vt');
                    $q->andWhere('UPPER(vt.name) LIKE :soundsLike');
                    $params[':soundsLike'] = '%' . $data['sounds_like'] . '%';
                }

                if ($data['studio_access']) {
                    $q->andWhere('ui.studio_access = 1');
                }

                if ($data['audio']) {
                    $q->andWhere('ua.id IS NOT NULL');
                }

                if (isset($data['certified']) && $data['certified']) {
                    $q->andWhere('ui.is_certified = 1');
                }

                if ($data['country']) {
                    $country = $em->getRepository('App:Country')
                        ->createQueryBuilder('c')
                        ->where('c.id = :country')
                        ->setParameter('country', $data['country'])
                        ->getQuery()->getSingleResult();

                    // get the code of the selected country
                    $q->andWhere('ui.country = :country');

                    $params[':country'] = $country->getCode();
                }

                if ($data['city']) {
                    $q->andWhere($q->expr()->like('ui.city', ':city'));
                    $params[':city'] = '%' . $data['city'] . '%';
                }

                if ($data['username']) {
                    $q->andWhere('ui.username LIKE :username');
                    $params[':username'] = '%' . $data['username'] . '%';
                }

                $fee = $data['fees'];
                if ($fee) {
                    list($min, $max) = explode('-', $fee);
                    if ($min) {
                        $min = $min * 100;
                        $q->andWhere('ui.producer_fee >= :minFee');
                        $params[':minFee'] = $min;
                    }
                    if ($max) {
                        $max = $max * 100;
                        $q->andWhere('ui.producer_fee <= :maxFee');
                        $params[':maxFee'] = $max;
                    }
                }
            }
        } else {
            $q->andWhere('ua.id IS NOT NULL');
        }

        if (isset($params) && count($params) > 0) {
            $q->setParameters($params);
        }

        $paginator = $container->get('knp_paginator');

        // Do select query
        $q->select('ui, up');
        $q->leftJoin('ui.user_pref', 'up');

        if ($filter) {
            switch ($filter) {
                case 'newest':
                    $q->orderBy('ui.date_registered', 'DESC');
                    break;
                case 'oldest':
                    $q->orderBy('ui.date_registered', 'ASC');
                    break;
                case 'random':
                    $q->addSelect('RAND() as HIDDEN rand');
                    $q->orderBy('rand');
                    break;
                case 'shuffle_certified':
                    $q
                        ->andWhere('ui.is_certified = 1')
                        ->addSelect('RAND() as HIDDEN rand')
                        ->addOrderBy('rand')
                    ;
                    break;
                case 'rated':
                default:
                    $q
                        ->orderBy('ui.producer_rated_count', 'DESC')
                        ->addOrderBy('ui.producer_rating', 'DESC')
                        ->addOrderBy('ui.last_login', 'DESC')
                    ;
                    break;
            }
        }

        if (isset($params) && count($params) > 0) {
            $q->setParameters($params);
        }

        $query = $q->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->get('page', 1)/*page number*/,
            20// limit per page
        );

        // Get ids of all profiles on screen
        $userIds = [];
        foreach ($pagination as $result) {
            $userIds[] = $result->getId();
        }

        // Get all user audios
        $userAudios = [];
        $audioIds   = [];

        if ($userIds) {
            $qb = $em->getRepository('App:UserAudio')
                    ->createQueryBuilder('ua');
            $qb->select('ui, ua');
            $qb->innerJoin('ua.user_info', 'ui');
            $qb->where($qb->expr()->in('ua.user_info', $userIds));
            $qb->addOrderBy('ua.default_audio', 'DESC');
            $aResults = $qb->getQuery()->execute();

            foreach ($aResults as $audio) {
                $audioIds[]                                   = $audio->getId();
                $userAudios[$audio->getUserInfo()->getId()][] = $audio;
            }
        }
        $audioLikes   = [];
        $userConnects = [];
        $favs         = [];

        // Get all audio ids on this screen
        if ($user) {
            $dm = $container->get('doctrine_mongodb')->getManager();

            if ($audioIds) {
                $qb = $dm->createQueryBuilder('App:AudioLike')
                        ->field('from_user_id')->equals($user->getId())
                        ->field('audio_id')->in($audioIds);
                $results = $qb->getQuery()->execute();

                foreach ($results as $result) {
                    $audioLikes[] = $result->getAudioId();
                }
            }

            // If user is logged in
            // - Get Audio likes
            // - Get user connects

            if ($userIds) {

                // See if logged in member has any of those connections
                $qb = $em->getRepository('App:UserConnectInvite')
                        ->createQueryBuilder('uc')
                        ->select('uc');

                $qb->where('(uc.from = :user and ' . $qb->expr()->in('uc.to', $userIds) . ')');
                $qb->orWhere('(uc.to = :user and ' . $qb->expr()->in('uc.from', $userIds) . ')');

                $params = [
                    'user' => $user,
                ];
                $qb->setParameters($params);

                $results = $qb->getQuery()->execute();

                if ($results) {
                    foreach ($results as $result) {
                        $userConnects[$result->getConnectedUser($user)->getId()] = $result;
                    }
                }

                // Get user favourites
                $favs       = [];
                $conn       = $container->get('database_connection');
                $favResults = $conn->fetchAll('SELECT favorite_user_info_id FROM user_favorite WHERE user_info_id = ' . $user->getId() . ' AND favorite_user_info_id IN (' . implode(',', $userIds) . ')');

                if ($favResults) {
                    foreach ($favResults as $fav) {
                        $favs[] = $fav['favorite_user_info_id'];
                    }
                }
            }
        }

        $freePlan = $em->getRepository('App:SubscriptionPlan')->findOneBy([
            'static_key' => 'FREE',
        ]);

        $hasProjects = $em->getRepository('App:Project')
            ->getUserOpenProjectCount($user);

        return $this->render('Producers/index.html.twig', [
            'favs'            => $favs,
            'form'            => $form->createView(),
            'filter'          => $filter,
            'freePlan'        => $freePlan,
            'pagination'      => $pagination,
            'audioLikes'      => $audioLikes,
            'hasProjects'     => $hasProjects,
            'userConnects'    => $userConnects,
            'userAudioList'   => $userAudios,
        ]);
    }
}
