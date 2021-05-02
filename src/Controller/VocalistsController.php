<?php

namespace App\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Project;
use App\Form\Type\UserSearchType;
use App\Form\Type\VocalistSearchType;

class VocalistsController extends AbstractUserSearchController
{
    /**
     * @Route("/vocalists", name="vocalists")
     * @Route("/vocalists/gig/{project}", defaults={"project" = ""}, name="vocalists_gig")
     * @Route("/vocalists/sort/{filter}", defaults={"filter" = ""}, name="vocalists_filter")
     * @Template()
     */
    public function indexAction(Request $request, ContainerInterface $container)
    {
        $em           = $this->getDoctrine()->getManager();
        $user         = $this->getUser();
        $userInfoRepo = $em->getRepository('App:UserInfo');

        if (!$user) {
            return $this->redirect($this->generateUrl('home'));
        }
        if ($request->get('filter', 'latest')) {
            $request->getSession()->set('vocalist_filter', $request->get('filter'));
        }

        $filter = $request->getSession()->get('vocalist_filter');
        if (!in_array($filter, ['newest', 'latest', 'random', 'shuffle_certified'])) {
            $filter = 'shuffle_certified';
        }

        $lockCertified = ($filter === 'shuffle_certified');

        $form = $this->createForm(UserSearchType::class, [
            'budget'        => $this->getProjectConfigData()['budget'],
            'lockCertified' => $lockCertified
        ]);

        $q = $userInfoRepo->createQueryBuilder('ui');
        $q->leftJoin('ui.user_audio', 'ua', 'WITH', 'ua.default_audio = 1');
        $q->where('ui.is_vocalist = 1 AND ui.is_active = 1');

        $q->leftJoin('ui.user_voice_tags', 'uvt');
        $q->leftJoin('uvt.voice_tag', 'vt');

        $results = [];

        if (!$request->get('search')) {
            $_GET[$form->getName()]['audio'] = true;
        }

//        if (array_key_exists($form->getName(), $_GET)) {
//            $form->handleRequest($_GET[$form->getName()]);
//        } else {
//            $form->handleRequest($request);
//        }

        $form->handleRequest($request);

        if ($request->get('search')) {
            if ($form->isValid()) {
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
                    $q->innerJoin('ui.genres', 'g');
                    $q->andWhere($q->expr()->in('g.id', $genreIds));
                }

                if ($data['sounds_like']) {
                    $q->andWhere('UPPER(vt.name) LIKE :soundsLike');
                    $q->orderBy('uvt.agree', 'DESC');
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

                if (isset($data['vocal_characteristic']) && count($data['vocal_characteristic']) > 0) {
                    $vocalIds = [];
                    foreach ($data['vocal_characteristic'] as $vocal) {
                        $vocalIds[] = $vocal->getId();
                    }
                    $q->innerJoin('ui.user_vocal_characteristics', 'vcs');
                    $q->innerJoin('vcs.vocal_characteristic', 'vc');
                    $q->andWhere($q->expr()->in('vc.id', $vocalIds));
                }

                if (isset($data['vocal_style']) && count($data['vocal_style']) > 0) {
                    $vocalIds = [];
                    foreach ($data['vocal_style'] as $vocal) {
                        $vocalIds[] = $vocal->getId();
                    }
                    $q->innerJoin('ui.user_vocal_styles', 'uvs');
                    $q->innerJoin('uvs.vocal_style', 'vs');
                    $q->andWhere($q->expr()->in('vs.id', $vocalIds));
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
                    $q->andWhere('(ui.username LIKE :username OR ui.display_name LIKE :username)');
                    $params[':username'] = '%' . $data['username'] . '%';
                }

                $fee = $data['fees'];
                if ($fee) {
                    list($min, $max) = explode('-', $fee);
                    if ($min) {
                        $min = $min * 100;
                        $q->andWhere('ui.vocalist_fee >= :minFee');
                        $params[':minFee'] = $min;
                    }
                    if ($max) {
                        $max = $max * 100;
                        $q->andWhere('ui.vocalist_fee <= :maxFee');
                        $params[':maxFee'] = $max;
                    }
                }

                if (count($data['languages']) > 0) {
                    $lang     = [];
                    $endEmpty = false;
                    foreach ($data['languages'] as $language) {
                        if ($language->getTitle() == 'English') {
                            $endEmpty = true;
                        }
                        $lang[] = $language->getId();
                    }

                    $expr = $em->getExpressionBuilder();

                    if ($endEmpty) {
                        $q->andWhere(
                            $q->expr()->orX(
                                $q->expr()->in(
                                    'ui.id',
                                    $em->createQueryBuilder()
                                        ->select('uui1.id')
                                        ->from('App\Entity\UserInfoLanguage', 'ul1')
                                        ->innerJoin('ul1.userInfo', 'uui1')
                                        ->where($q->expr()->in('ul1.language', $lang))
                                        ->getDql()
                                ),
                                $q->expr()->notIn(
                                    'ui.id',
                                    $em->createQueryBuilder()
                                        ->select('uui2.id')
                                        ->from('App\Entity\UserInfoLanguage', 'ul2')
                                        ->innerJoin('ul2.userInfo', 'uui2')
                                        ->getDql()
                                )
                            )
                        );
                    } else {
                        $q->andWhere(
                            $expr->in(
                                'ui.id',
                                $em->createQueryBuilder()
                                    ->select('uui.id')
                                    ->from('App\Entity\UserInfoLanguage', 'ul')
                                    ->innerJoin('ul.userInfo', 'uui')
                                    ->where($q->expr()->in('ul.language', $lang))
                                    ->getDql()
                            )
                        );
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

        $q->select('ui, up');
        $q->leftJoin('ui.user_pref', 'up');

        // Add filter
        if ($filter) {
            switch ($filter) {
                case 'newest':
                    $q->addOrderBy('ui.date_registered', 'DESC');
                    break;
                case 'oldest':
                    $q->addOrderBy('ui.date_registered', 'ASC');
                    break;
                case 'random':
                    $q->addSelect('RAND() as HIDDEN rand');
                    $q->addOrderBy('rand');
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
                    $q->addOrderBy('ui.vocalist_rated_count', 'DESC');
                    $q->addOrderBy('ui.vocalist_rating', 'DESC');
                    break;
            }
        }

        if (isset($params) && count($params) > 0) {
            $q->setParameters($params);
        }

        $query = $q->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->get('page', 1)/* page number */,
            20 // limit per page
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

        // If user is logged in
        // - Get Audio likes
        // - Get user connects
        $audioLikes   = [];
        $userConnects = [];
        $favs         = [];

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
                $conn       = $this->get('database_connection');
                $favResults = $conn->fetchAll('SELECT favorite_user_info_id FROM user_favorite WHERE user_info_id = ' . $user->getId() . ' AND favorite_user_info_id IN (' . implode(',', $userIds) . ')');

                if ($favResults) {
                    foreach ($favResults as $fav) {
                        $favs[] = $fav['favorite_user_info_id'];
                    }
                }
            }
        }

        // Top tags
        $topSoundsLike = $em->getRepository('App:UserVoiceTag')->getTop10();

        $freePlan = $em->getRepository('App:SubscriptionPlan')->findOneBy([
            'static_key' => 'FREE',
        ]);

        $hasProjects = $em->getRepository('App:Project')
                ->getUserOpenProjectCount($user);

        return $this->render('Vocalists/index.html.twig', [
            'favs'             => $favs,
            'form'             => $form->createView(),
            'filter'           => $filter,
            'freePlan'         => $freePlan,
            'pagination'       => $pagination,
            'audioLikes'       => $audioLikes,
            'hasProjects'      => $hasProjects,
            'userConnects'     => $userConnects,
            'userAudioList'    => $userAudios,
            'topSoundsLike'    => $topSoundsLike,
        ]);
    }

    /**
     * @Route("/male-vocalists", name="male_vocalists")
     * @Template()
     */
    public function maleVocalistsAction(Request $request, ContainerInterface $container)
    {
        $em           = $this->getDoctrine()->getManager();
        $user         = $this->getUser();
        $userInfoRepo = $em->getRepository('App:UserInfo');
        $page         = $request->get('page', 1);

        // Get fee options
        $projectYml = $this->getProjectConfigData();

        $form = $this->createForm(new VocalistSearchType($projectYml['budget'], $em));

        $q = $userInfoRepo->createQueryBuilder('ui');
        $q->leftJoin('ui.user_audio', 'ua', 'WITH', 'ua.default_audio = 1');
        $q->where('ui.is_vocalist = 1 AND ui.is_active = 1');

        $q->leftJoin('ui.user_voice_tags', 'uvt');
        $q->leftJoin('uvt.voice_tag', 'vt');

        $results = [];

        if (!$request->get('search')) {
            $_GET[$form->getName()]['audio']  = true;
            $_GET[$form->getName()]['gender'] = 'm';
        }

        $q->andWhere('ui.gender = :gender');
        $params[':gender'] = 'm';

//        $form->bind($_GET[$form->getName()]);
        $form->handleRequest($request);

        $q->andWhere('ua.id IS NOT NULL');

        if (isset($params) && count($params) > 0) {
            $q->setParameters($params);
        }

        $paginator = $container->get('knp_paginator');

        // Get count first
        $q->select('count(ui.id)');
        $myCount = $q->getQuery()->getSingleScalarResult();

        $q->addOrderBy('ui.rated_count', 'DESC');
        $q->addOrderBy('ui.rating', 'DESC');

        $q->select('ui, ua, up');
        $q->leftJoin('ui.user_pref', 'up');
        $q->addSelect('uvt, vt');

        if (isset($params) && count($params) > 0) {
            $q->setParameters($params);
        }

        $query = $q->getQuery();

        $query->setHint('knp_paginator.count', $myCount);

        $pagination = $paginator->paginate(
            $query,
            $request->query->get('page', 1)/* page number */,
            10 // limit per page
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
            $qb->addOrderBy('ua.default_audio', 'ASC');
            $aResults = $qb->getQuery()->execute();

            foreach ($aResults as $audio) {
                $audioIds[]                                   = $audio->getId();
                $userAudios[$audio->getUserInfo()->getId()][] = $audio;
            }
        }

        // If user is logged in
        // - Get Audio likes
        // - Get user connects
        $audioLikes   = [];
        $userConnects = [];
        $favs         = [];

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
                $conn       = $this->get('database_connection');
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

        return [
            'form'          => $form->createView(),
            'pagination'    => $pagination,
            'audioLikes'    => $audioLikes,
            'userConnects'  => $userConnects,
            'freePlan'      => $freePlan,
            'userAudioList' => $userAudios,
            'favs'          => $favs,
        ];
    }

    /**
     * @Route("/female-vocalists", name="female_vocalists")
     * @Template()
     */
    public function femaleVocalistsAction(Request $request, ContainerInterface $container)
    {
        $em           = $this->getDoctrine()->getManager();
        $user         = $this->getUser();
        $userInfoRepo = $em->getRepository('App:UserInfo');
        $page         = $request->get('page', 1);

        // Get fee options
        $projectYml = $this->getProjectConfigData();

        $form = $this->createForm(new VocalistSearchType($projectYml['budget'], $em));

        $q = $userInfoRepo->createQueryBuilder('ui');
        $q->leftJoin('ui.user_audio', 'ua', 'WITH', 'ua.default_audio = 1');
        $q->where('ui.is_vocalist = 1 AND ui.is_active = 1');

        $q->leftJoin('ui.user_voice_tags', 'uvt');
        $q->leftJoin('uvt.voice_tag', 'vt');

        $results = [];

        if (!$request->get('search')) {
            $_GET[$form->getName()]['audio']  = true;
            $_GET[$form->getName()]['gender'] = 'f';
        }

        $q->andWhere('ui.gender = :gender');
        $params[':gender'] = 'f';

//        $form->bind($_GET[$form->getName()]);
        $form->handleRequest($request);

        $q->andWhere('ua.id IS NOT NULL');

        if (isset($params) && count($params) > 0) {
            $q->setParameters($params);
        }

        $paginator = $container->get('knp_paginator');

        $q->addOrderBy('ui.rated_count', 'DESC');
        $q->addOrderBy('ui.rating', 'DESC');

        $q->select('ui, ua, up');
        $q->leftJoin('ui.user_pref', 'up');
        $q->addSelect('uvt, vt');

        if (isset($params) && count($params) > 0) {
            $q->setParameters($params);
        }

        $query = $q->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->get('page', 1)/* page number */,
            10 // limit per page
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
            $qb->addOrderBy('ua.default_audio', 'ASC');
            $aResults = $qb->getQuery()->execute();

            foreach ($aResults as $audio) {
                $audioIds[]                                   = $audio->getId();
                $userAudios[$audio->getUserInfo()->getId()][] = $audio;
            }
        }

        // If user is logged in
        // - Get Audio likes
        // - Get user connects
        $audioLikes   = [];
        $userConnects = [];
        $favs         = [];

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
                $conn       = $this->get('database_connection');
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

        return [
            'form'          => $form->createView(),
            'pagination'    => $pagination,
            'audioLikes'    => $audioLikes,
            'userConnects'  => $userConnects,
            'freePlan'      => $freePlan,
            'userAudioList' => $userAudios,
            'favs'          => $favs,
        ];
    }

    /**
     * @Route("/session-singers-hire", name="sessionSingersHire")
     * @Template()
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function sessionSingersHireAction(Request $request, ContainerInterface $container)
    {
        $em           = $this->getDoctrine()->getManager();
        $user         = $this->getUser();
        $userInfoRepo = $em->getRepository('App:UserInfo');
        $page         = $request->get('page', 1);

        // Get fee options
        $projectYml = $this->getProjectConfigData();

        $form = $this->createForm(new VocalistSearchType($projectYml['budget'], $em));

        $q = $userInfoRepo->createQueryBuilder('ui');
        $q->leftJoin('ui.user_audio', 'ua', 'WITH', 'ua.default_audio = 1');
        $q->where('ui.is_vocalist = 1 AND ui.is_active = 1');

        $q->leftJoin('ui.user_voice_tags', 'uvt');
        $q->leftJoin('uvt.voice_tag', 'vt');

        $results = [];

        if (!$request->get('search')) {
            $_GET[$form->getName()]['audio'] = true;
        }

//        $form->bind($_GET[$form->getName()]);
        $form->handleRequest($request);

        $q->andWhere('ua.id IS NOT NULL');

        if (isset($params) && count($params) > 0) {
            $q->setParameters($params);
        }

        $paginator = $container->get('knp_paginator');

        $q->addOrderBy('ui.rated_count', 'DESC');
        $q->addOrderBy('ui.rating', 'DESC');

        $q->select('ui, ua, up');
        $q->leftJoin('ui.user_pref', 'up');
        $q->addSelect('uvt, vt');

        if (isset($params) && count($params) > 0) {
            $q->setParameters($params);
        }

        $query = $q->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->get('page', 1)/* page number */,
            20 // limit per page
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
            $qb->addOrderBy('ua.default_audio', 'ASC');
            $aResults = $qb->getQuery()->execute();

            foreach ($aResults as $audio) {
                $audioIds[]                                   = $audio->getId();
                $userAudios[$audio->getUserInfo()->getId()][] = $audio;
            }
        }

        // If user is logged in
        // - Get Audio likes
        // - Get user connects
        $audioLikes   = [];
        $userConnects = [];
        $favs         = [];

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
                $conn       = $this->get('database_connection');
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

        return [
            'form'          => $form->createView(),
            'pagination'    => $pagination,
            'audioLikes'    => $audioLikes,
            'userConnects'  => $userConnects,
            'freePlan'      => $freePlan,
            'userAudioList' => $userAudios,
            'favs'          => $favs,
        ];
    }

    /**
     * @Route("/female-singers-hire", name="femaleSingersHire")
     * @Template()
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function femaleSingersHireAction(Request $request, ContainerInterface $container)
    {
        $em           = $this->getDoctrine()->getManager();
        $user         = $this->getUser();
        $userInfoRepo = $em->getRepository('App:UserInfo');
        $page         = $request->get('page', 1);

        // Get fee options
        $projectYml = $this->getProjectConfigData();

        $form = $this->createForm(new VocalistSearchType($projectYml['budget'], $em));

        $q = $userInfoRepo->createQueryBuilder('ui');
        $q->leftJoin('ui.user_audio', 'ua', 'WITH', 'ua.default_audio = 1');
        $q->where('ui.is_vocalist = 1 AND ui.is_active = 1');

        $q->leftJoin('ui.user_voice_tags', 'uvt');
        $q->leftJoin('uvt.voice_tag', 'vt');

        $results = [];

        if (!$request->get('search')) {
            $_GET[$form->getName()]['audio']  = true;
            $_GET[$form->getName()]['gender'] = 'f';
        }

        $q->andWhere('ui.gender = :gender');
        $params[':gender'] = 'f';

//        $form->bind($_GET[$form->getName()]);
        $form->handleRequest($request);

        $q->andWhere('ua.id IS NOT NULL');

        if (isset($params) && count($params) > 0) {
            $q->setParameters($params);
        }

        $paginator = $container->get('knp_paginator');

        $q->addOrderBy('ui.rated_count', 'DESC');
        $q->addOrderBy('ui.rating', 'DESC');

        $q->select('ui, ua, up');
        $q->leftJoin('ui.user_pref', 'up');
        $q->addSelect('uvt, vt');

        if (isset($params) && count($params) > 0) {
            $q->setParameters($params);
        }

        $query = $q->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->get('page', 1)/* page number */,
            20 // limit per page
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
            $qb->addOrderBy('ua.default_audio', 'ASC');
            $aResults = $qb->getQuery()->execute();

            foreach ($aResults as $audio) {
                $audioIds[]                                   = $audio->getId();
                $userAudios[$audio->getUserInfo()->getId()][] = $audio;
            }
        }

        // If user is logged in
        // - Get Audio likes
        // - Get user connects
        $audioLikes   = [];
        $userConnects = [];
        $favs         = [];

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
                $conn       = $this->get('database_connection');
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

        return [
            'form'          => $form->createView(),
            'pagination'    => $pagination,
            'audioLikes'    => $audioLikes,
            'userConnects'  => $userConnects,
            'freePlan'      => $freePlan,
            'userAudioList' => $userAudios,
            'favs'          => $favs,
        ];
    }

    /**
     * @Route("/male-singers-hire", name="maleSingersHire")
     * @Template()
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function maleSingersHireAction(Request $request, ContainerInterface $container)
    {
        $em           = $this->getDoctrine()->getManager();
        $user         = $this->getUser();
        $userInfoRepo = $em->getRepository('App:UserInfo');
        $page         = $request->get('page', 1);

        // Get fee options
        $projectYml = $this->getProjectConfigData();

        $form = $this->createForm(new VocalistSearchType($projectYml['budget'], $em));

        $q = $userInfoRepo->createQueryBuilder('ui');
        $q->leftJoin('ui.user_audio', 'ua', 'WITH', 'ua.default_audio = 1');
        $q->where('ui.is_vocalist = 1 AND ui.is_active = 1');

        $q->leftJoin('ui.user_voice_tags', 'uvt');
        $q->leftJoin('uvt.voice_tag', 'vt');

        $results = [];

        if (!$request->get('search')) {
            $_GET[$form->getName()]['audio']  = true;
            $_GET[$form->getName()]['gender'] = 'm';
        }

        $q->andWhere('ui.gender = :gender');
        $params[':gender'] = 'm';

//        $form->bind($_GET[$form->getName()]);
        $form->handleRequest($request);

        $q->andWhere('ua.id IS NOT NULL');

        if (isset($params) && count($params) > 0) {
            $q->setParameters($params);
        }

        $paginator = $container->get('knp_paginator');

        $q->addOrderBy('ui.rated_count', 'DESC');
        $q->addOrderBy('ui.rating', 'DESC');

        $q->select('ui, ua, up');
        $q->leftJoin('ui.user_pref', 'up');
        $q->addSelect('uvt, vt');

        if (isset($params) && count($params) > 0) {
            $q->setParameters($params);
        }

        $query = $q->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->get('page', 1)/* page number */,
            20 // limit per page
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
            $qb->addOrderBy('ua.default_audio', 'ASC');
            $aResults = $qb->getQuery()->execute();

            foreach ($aResults as $audio) {
                $audioIds[]                                   = $audio->getId();
                $userAudios[$audio->getUserInfo()->getId()][] = $audio;
            }
        }

        // If user is logged in
        // - Get Audio likes
        // - Get user connects
        $audioLikes   = [];
        $userConnects = [];
        $favs         = [];

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
                $conn       = $this->get('database_connection');
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

        return [
            'form'          => $form->createView(),
            'pagination'    => $pagination,
            'audioLikes'    => $audioLikes,
            'userConnects'  => $userConnects,
            'freePlan'      => $freePlan,
            'userAudioList' => $userAudios,
            'favs'          => $favs,
        ];
    }
}
