<?php

namespace App\Controller;

use App\Entity\UserPref;
use Doctrine\ODM\MongoDB\DocumentManager;
use GuzzleHttp\Client;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\Project;
use App\Entity\ProjectAudio;
use App\Entity\UserAudio;
use App\Entity\UserInfo;
use App\Entity\UserVoiceTag;
use App\Entity\VocalizrActivity;
use App\Entity\VoiceTag;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\Security;
use Doctrine\ODM\MongoDB\MongoDBException;

class DashboardController extends AbstractController
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm)
    {
        $this->dm = $dm;
    }

    /**
     * @Route("/dashboard", name="dashboard")
     * @Template()
     *
     * @param Request  $request
     * @param Security $security
     *
     * @return Response
     * @throws MongoDBException
     */
    public function indexAction(Request $request, Security $security)
    {
        $em   = $this->getDoctrine()->getManager();

        /** @var UserInfo $user */
        $user = $this->getUser();

        // load the global activity items and items specific to this user
        // for display in the feed
        $activityFilter = $request->getSession()->get('activity_filter');
        if ($activityFilter === null) {
            $userPref = $em->getRepository(UserPref::class)->findOneBy(['user_info' => $this->getUser()->getId()]);

            if (!$userPref) {
                $userPref = new \App\Entity\UserPref();
                $userPref->setUserInfo($this->getUser());
                $userPref->setActivityFilter('all');
                $em->persist($userPref);
                $em->flush($userPref);
            } else {
                if (!$userPref->getActivityFilter()) {
                    $userPref->setActivityFilter('all');
                    $em->persist($userPref);
                    $em->flush($userPref);
                }
            }
            $request->getSession()->set('activity_filter', $userPref->getActivityFilter());
        }
        $activity = $em->getRepository(VocalizrActivity::Class)->findActivity($this->getUser(), ['filter' => $request->getSession()->get('activity_filter')]);

        // Update the activity to read
        /** VocalizrActivity $actovity */
        $activity = $em->getRepository(VocalizrActivity::class)->findOneBy(['user_info' => $this->getUser()]);
        if ($activity) {
            $activity->setActivityRead(true);
            $em->flush();
        }

        $activityMessage = new VocalizrActivity();
        $messageForm     = $this->createFormBuilder($activityMessage)
                    ->add('message_text', TextType::class, [
                        'mapped' => false,
                    ])
                    ->add('last_activity', HiddenType::class, [
                        'mapped' => false,
                    ])
                    ->getForm();

        $userSoundsLikesForm = $this->createFormBuilder($this->getUser())
                    ->add('sounds_like', null, [
                        'attr' => [
                            'class'       => 'tag-input',
                            'placeholder' => 'e.g. Beyonce or Justin Timberlake',
                        ],
                        'mapped' => false,
                    ])
                    ->getForm();

        $userCityForm = $this->createFormBuilder($this->getUser())
                    ->add('city', TextType::class, [
                        'attr' => [
                            'class'    => 'geo hide',
                            'data-geo' => 'locality',
                        ], ])
                    ->add('state', TextType::class, [
                        'attr' => [
                            'class'    => 'geo hide',
                            'data-geo' => 'administrative_area_level_1',
                        ], ])
                    ->add('location_lat', TextType::class, [
                        'attr' => [
                            'class'    => 'geo hide',
                            'data-geo' => 'lat',
                        ], ])
                    ->add('location_lng', TextType::class, [
                        'attr' => [
                            'class'    => 'geo hide',
                            'data-geo' => 'lng',
                        ], ])
//                    ->add('country', 'text', array(
//                        'attr' => array(
//                            'class' => 'geo hide',
//                            'data-geo' => 'country_short',
//                        )))
                    ->getForm();

        $userGenresForm = $this->createFormBuilder($this->getUser())
                    ->add('genres', null, [
                        'label' => 'Genres',
                        'attr'  => [
                            'class' => 'select2',
                        ],
                    ])
                    ->getForm();

        // grab a list of projects to show as featured
        $date = new \DateTime();
        $date->sub(new \DateInterval('P2D'));

        /**
         * Find lastest normal projects
         */
        $projects = [];
        $query    = $em->getRepository(Project::class)
                    ->createQueryBuilder('p');

        $query->select('p, pa, ui');
        $query->innerJoin('p.user_info', 'ui');
        $query->leftJoin('p.project_audio', 'pa', 'WITH', "pa.flag = '" . ProjectAudio::FLAG_FEATURED . "'");
        $query->addOrderBy('p.published_at', 'DESC');
        $query->andWhere('p.is_active = true');
        $query->andWhere('p.bids_due >= :bidsDue');
        $query->andWhere('p.publish_type = :publishType');
        $query->andWhere('p.employee_user_info = :employee_user_info');
        $query->andWhere('p.featured = 0');
        $params = [
            ':publishType' => Project::PUBLISH_PUBLIC,
            ':bidsDue'     => $date,
//            ':featured'     => false,
            ':employee_user_info'     => NULL,
        ];
        $query->addSelect('pbs');
        $query->leftJoin('p.project_bids', 'pbs', 'WITH', 'pbs.user_info = :userInfoId');
        $params[':userInfoId'] = $this->getUser();

        if (isset($params) && count($params) > 0) {
            $query->setParameters($params);
        }
        $query->setMaxResults(6);
        $projects = $query->getQuery()->execute();

        /**
         * Featured projects
         */
        $featuredProjects = [];
        $query            = $em->getRepository(Project::class)
                    ->createQueryBuilder('p');

        $query->select('p, pa, ui');
        $query->innerJoin('p.user_info', 'ui');
        $query->leftJoin('p.project_audio', 'pa', 'WITH', "pa.flag = '" . ProjectAudio::FLAG_FEATURED . "'");
        $query->orderBy('p.published_at', 'DESC');
        $query->andWhere('p.is_active = true');
        $query->andWhere('p.bids_due >= :bidsDue');
        $query->andWhere('p.publish_type = :publishType');
        $query->andWhere('p.employee_user_info is null AND p.featured = 1');
        $params = [
            ':publishType' => Project::PUBLISH_PUBLIC,
            ':bidsDue'     => $date,
        ];
        $query->addSelect('pbs');
        $query->leftJoin('p.project_bids', 'pbs', 'WITH', 'pbs.user_info = :userInfoId');
        $params[':userInfoId'] = $this->getUser();

        if (isset($params) && count($params) > 0) {
            $query->setParameters($params);
        }
        $query->setMaxResults(10);
        $featuredProjects = $query->getQuery()->execute();

        // check profile completion
        // if profile isn't completed, get data
        if ($this->getUser()->getCompletedProfile()) {
            $hasAudio      = true;
            $hasGenres     = true;
            $hasSoundsLike = true;
        } else {
            $q = $em->getRepository(UserVoiceTag::class)
                        ->createQueryBuilder('uvt')
                        ->select('COUNT(uvt.id)')
                        ->where('uvt.user_info = :user')
                        ->setParameter('user', $this->getUser());
            $query         = $q->getQuery();
            $hasSoundsLike = $query->getSingleScalarResult() > 0;

            $query = $em->getRepository(UserAudio::class)
                        ->createQueryBuilder('ua')
                        ->select('COUNT(ua.id)')
                        ->where('ua.user_info = :user')
                        ->setParameter('user', $this->getUser());
            $hasAudio = $query->getQuery()->getSingleScalarResult() > 0;

            $hasGenres = count($this->getUser()->getGenres()) > 0;
        }

        // Get all audio ids on this screen
        $audioLikes = [];

        $stats = [
            'profileViews' => 0,
            'audioLikes'   => 0,
            'audioPlays'   => 0,
        ];
        $timeAgo = '-30 days';
//
//       /* $profileViewStat = $this->dm->createQueryBuilder('App:ProfileView')
//            ->field('user_id')->equals($user->getId())
//            ->field('unique')->equals(false)
//            ->field('date')->gte(date('Y-m-d', strtotime($timeAgo)))
//            ->field('date')->lte(date('Y-m-d'))
//            ->group(['user_id' => 1], ['total' => 0])
//            ->reduce('function ( curr, result ) { result.total += curr.count;}')
//            ->getQuery()
//            ->execute();*/
//
//        // Get stats
//        $profileViewStat = $this->dm->createAggregationBuilder('App:ProfileView')
//            ->match()
//                ->field('user_id')->equals($user->getId())
//                ->field('unique')->equals(false)
//                ->field('date')->gte(date('Y-m-d', strtotime($timeAgo)))
//                ->field('date')->lte(date('Y-m-d'))
////            ->group(['user_id' => 1], ['total' => 0])
////            ->group()
////                ->field('user_id')->expression(1)
////                ->field('total')->expression(0)
////            ->reduce('function ( curr, result ) { result.total += curr.count;}')
////            ->getQuery()
//            ->execute();
//
//        if (count($profileViewStat)) {
//            $stats['profileViews'] = $profileViewStat[0]['total'];
//        }
//
//        $audioPlayStat = $this->dm->createQueryBuilder('App:AudioPlay')
//            ->field('user_id')->equals($user->getId())
//            ->field('date')->gte(date('Y-m-d', strtotime($timeAgo)))
//            ->field('date')->lte(date('Y-m-d'))
//            ->group(['user_id' => 1], ['total' => 0])
//            ->reduce('function ( curr, result ) { result.total += curr.count;}')
//            ->getQuery()
//            ->execute();
//
//        if (count($audioPlayStat)) {
//            $stats['audioPlays'] = $audioPlayStat[0]['total'];
//        }
//
//        $audioLikeStat = $this->dm->createQueryBuilder('App:AudioLike')
//            ->field('user_id')->equals($user->getId())
//            ->field('date')->gte(date('Y-m-d H:i:s', strtotime($timeAgo)))
//            ->field('date')->lte(date('Y-m-d H:i:s'))
//            ->getQuery()
//            ->execute()
//            ->count();
//
//        if (count($audioPlayStat)) {
//            $stats['audioLikes'] = $audioLikeStat;
//        }

        $data = [
            'activity'           => $activity,
            'hasSoundsLike'      => $hasSoundsLike,
            'hasAudio'           => $hasAudio,
            'hasGenres'          => $hasGenres,
            'projects'           => $projects,
            'userSoundsLikeForm' => $userSoundsLikesForm->createView(),
            'userCityForm'       => $userCityForm->createView(),
            'userGenresForm'     => $userGenresForm->createView(),
            'messageForm'        => $messageForm->createView(),
            'audioLikes'         => $audioLikes,
            'stats'              => $stats,
            'featuredProjects'   => $featuredProjects,
        ];

        // add to session that we want to prompt this new user to upgrade to PRO
        if ($request->getSession()->get('pro_prompt')) {
            $request->getSession()->remove('pro_prompt');
            $data['pro_prompt'] = true;
        } else {
            $data['pro_prompt'] = false;
        }

        // Check for sfs promotion
        $qb = $em->getRepository(Project::class)
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
//
//        $data['audiosByUser'] = $this->getUserAudios($activity);
//        $data['audiosByProject'] = $this->getFeaturedProjectAudios($activity);
        $activity = [];
        $data['audiosByUser'] = $this->getUserAudios($activity);
        $data['audiosByProject'] = $this->getFeaturedProjectAudios($activity);
        $data['audioLikes'] = $this->getUserLikes($data['audiosByUser']);
        $data['articles'] = [];


        return $this->render('Dashboard/index.html.twig', $data);
    }

    /**
     * @Route("/activity/{filter}", name="filtered_activity")
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param type                                      $filter
     */
    public function filteredActivity(Request $request, $filter)
    {
        $em   = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        $userPref = $em->getRepository('App:UserPref')->findOneBy(['user_info' => $this->getUser()->getId()]);
        if (!$userPref) {
            $userPref = new \App\Entity\UserPref();
            $userPref->setUserInfo($this->getUser());
        }
        $userPref->setActivityFilter($filter);
        $em->flush();
        $request->getSession()->set('activity_filter', $filter);

        $activity = $em->getRepository('App:VocalizrActivity')->findActivity($this->getUser(), ['filter' => $request->getSession()->get('activity_filter')]);

        // Update the activity to read
        $q = $em->getRepository('App:VocalizrActivity')->createQueryBuilder('va');
        $q->update()
                ->set('va.activity_read', 1)
                ->where('va.user_info = :user_info');
        $params = [
            ':user_info' => $this->getUser(),
        ];
        $q->setParameters($params);
        $q->getQuery()->execute();

        $userAudios =  $this->getUserAudios($activity);

        // Get all audio ids on this screen
        $audioLikes = $this->getUserLikes($userAudios);

        return new Response(json_encode([
            'success' => true,
            'filter'  => $filter,
            'html'    => $this->renderView('Dashboard/activity.html.twig', [
                'activity' => $activity,
                'audioLikes' => $audioLikes,
                'audiosByUser' => $userAudios,
                'audiosByProject' => $this->getFeaturedProjectAudios($activity),
            ]),
        ]));
    }

    /**
     * @Route("/dashboard/updateSoundsLike", name="dashboard_sounds_like")
     *
     * @param Request $request
     */
    public function updateUserSoundsLike(Request $request)
    {
        if (!$request->isMethod('POST')) {
            return new JsonResponse([
                    'success' => false,
                    'message' => 'Invalid request',
                ]);
        }

        $em   = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        $userSoundsLikesForm = $this->createFormBuilder($this->getUser())
                    ->add('sounds_like', null, [
                        'attr' => [
                            'class'       => 'tag-input',
                            'placeholder' => 'e.g. Beyonce or Justin Timberlake',
                        ],
                        'mapped' => false,
                    ])
                    ->getForm();

        $data = $userSoundsLikesForm->getData();

        $userSoundsLikesForm->handleRequest($request);

        $currentVoiceTags = $em->getRepository('App:UserVoiceTag')
                ->getVoiceTagIdsForUser($user->getId());
        $voiceTags = $userSoundsLikesForm->get('sounds_like')->getData();
        if (!empty($voiceTags)) {
            $voiceTags = explode(',', $voiceTags);

            foreach ($voiceTags as $tag) {
                if (empty($tag)) {
                    continue;
                }

                // check if the tag exists
                $newVoiceTag = $em->getRepository('App:VoiceTag')
                        ->findOneByName($tag);
                if (!$newVoiceTag) {
                    $newVoiceTag = new VoiceTag();
                    $newVoiceTag->setName(ucwords($tag));
                }
                $em->persist($newVoiceTag);

                if (!in_array(strtoupper($tag), $currentVoiceTags)) {
                    $newUserVoiceTag = new UserVoiceTag();
                    $newUserVoiceTag->setUserInfo($user);
                    $newUserVoiceTag->setVoiceTag($newVoiceTag);
                    $em->persist($newUserVoiceTag);
                    $data->addUserVoiceTag($newUserVoiceTag);
                }
                $key = array_search(strtoupper($tag), $currentVoiceTags);
                unset($currentVoiceTags[$key]);
            }
            // Now remove all voice tags that were left over (not used anymore)
            if (count($currentVoiceTags) > 0) {
                foreach ($currentVoiceTags as $userVoiceTagId => $voiceTag) {
                    $em->remove($em->getReference('App:UserVoiceTag', $userVoiceTagId));
                }
            }
            $em->persist($data);
            $this->checkProfileCompleteness();
            $em->flush();

            return new Response(json_encode(['success' => true]));
        } else {
            return new Response(json_encode(['success' => false,
                'message'                              => 'Required', ]));
        }
    }

    /**
     * @Route("/dashboard/updateUserCity", name="dashboard_user_city")
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function updateUserCity(Request $request)
    {
        if (!$request->isMethod('POST')) {
            return new JsonResponse(['success' => false,
                'message'                      => 'Invalid request', ]);
        }

        $em   = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        $userCityForm = $this->createFormBuilder($this->getUser())
                    ->add('city', TextType::class, [
                        'attr' => [
                            'class'    => 'hide',
                            'data-geo' => 'locality',
                        ], ])
                    ->add('state', TextType::class, [
                        'attr' => [
                            'class'    => 'hide',
                            'data-geo' => 'administrative_area_level_1',
                        ], ])
                    ->add('location_lat', TextType::class, [
                        'attr' => [
                            'class'    => 'hide',
                            'data-geo' => 'lat',
                        ], ])
                    ->add('location_lng', TextType::class, [
                        'attr' => [
                            'class'    => 'hide',
                            'data-geo' => 'lng',
                        ], ])
                    ->add('country', TextType::class, [
                        'attr' => [
                            'class'    => 'hide',
                            'data-geo' => 'country_short',
                        ], ])
                    ->getForm();

        $userCityForm->handleRequest($request);

        if ($user->getCity() == null && $request->get('location')) {
            return new Response(json_encode(['success' => false,
                'message'                              => 'Invalid city', ]));
        }

        if ($user->getCity() == null || $user->getLocationLat() == null) {
            return new Response(json_encode(['success' => false,
                'message'                              => 'Required', ]));
        }

        $this->checkProfileCompleteness();
        $em->flush();
        return new Response(json_encode(['success' => true]));
    }

    /**
     * @Route("/dashboard/updateUserGenres", name="dashboard_user_genre")
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function updateUserGenre(Request $request)
    {
        if (!$request->isMethod('POST')) {
            return new JsonResponse(['success' => false,
                'message'                      => 'Invalid request', ]);
        }

        $em   = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        $userInfoForm = $this->createFormBuilder($user)
                    ->add('genres', null, [
                        'label' => 'Genres',
                        'attr'  => [
                            'class' => 'select2',
                        ],
                    ])
                    ->getForm();

        $userInfoForm->bind($request);

        if (count($user->getGenres()) === 0) {
            return new Response(json_encode([
                'success' => false,
                'message' => 'Required', ]));
        }
        $this->checkProfileCompleteness();
        $em->flush();
        return new Response(json_encode(['success' => true]));
    }

    /**
     * @Route("/dashboard/updateAudio", name="dashboard_user_audio")
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function updateAudio(Request $request)
    {
        if (!$request->isMethod('POST')) {
            return new JsonResponse(['success' => false,
                'message'                      => 'Invalid request', ]);
        }

        if (!$request->get('sc_track_id') && !$request->get('audio_file')) {
            return new JsonResponse(['success' => false,
                'message'                      => 'Invalid request', ]);
        }

        $em            = $this->getDoctrine()->getManager();
        $user          = $this->getUser();
        $userAudioRepo = $em->getRepository('App:UserAudio');

        $subscriptionPlan = $em->getRepository('App:SubscriptionPlan')->getActiveSubscription($user->getId());

        // If they are using a soundcloud track
        if ($request->get('sc_track_id')) {
            // Make sure they haven't gone over subscription limit
            $userAudios = $userAudioRepo->getProfileTracksByUser($user->getId());

            // Check the amount against the subscription plan
            if (count($userAudios) >= $subscriptionPlan['user_audio_limit']) {
                return new JsonResponse(['success' => false,
                    'message'                      => 'You have reached your audio limit for your profile', ]);
            }

            if (!$scTrack = $em->getRepository('App:UserScTrack')->findOneBy(['sc_id' => $request->get('sc_track_id')])) {
                $request->query->set('error', 'There was a error while trying to assign your SoundCloud Track. Please try again.');
            }

            $defaultTrack = (count($userAudios) == 0); // If no tracks upload, set as default
            $userAudio    = $userAudioRepo->saveTrackFromSoundCloud($user->getId(), $scTrack, $defaultTrack);
            return new Response(json_encode(['success' => true]));
        }

        /**
         * Handle User Audio Upload
         */
        if ($request->get('audio_file')) {
            // Check how many files are currently stored
            $userAudios = $userAudioRepo->getProfileTracksByUser($user->getId());

            // Check the amount against the subscription plan
            if (count($userAudios) >= $subscriptionPlan['user_audio_limit']) {
                return new JsonResponse(['success' => false,
                    'message'                      => 'You have reached your audio limit for your profile', ]);
            }

            $fileName   = $request->get('audio_file');
            $title      = $request->get('audio_title');
            $uploadPath = $this->getParameter('kernel.project_dir') . DIRECTORY_SEPARATOR . '../uploads/audio/' . $user->getId() . '/';

            // Save audio and move uploaded file, if failed return error
            if (!$userAudio = $em->getRepository('App:UserAudio')
                            ->saveUploadedFile($user->getId(), $title, $fileName)) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'There was a problem when uploading your file. Please try again',
                ]);
            }
        }
        $this->checkProfileCompleteness();

        $em->flush();
        return new Response(json_encode(['success' => true]));
    }

    // Check if profile is completed, if so set user as completed profile
    private function checkProfileCompleteness()
    {
        $user = $this->getUser();
        $em   = $this->getDoctrine()->getManager();

        // Check if avatar is uploaded
        if (!$user->getPath()) {
            return false;
        }

        // Check if city is set
        if (!$user->getCity()) {
            return false;
        }

        // Check user audio
        if (!$user->getUserAudio()) {
            return false;
        }

        // Check sounds like
        if (!$user->getUserVocalStyles()) {
            return false;
        }

        // If producer, check geners
        if ($user->getIsProducer() && !$user->getGenres()) {
            return false;
        }

        // If we are here, profile is completed
        $user->setCompletedProfile(new \DateTime());
        $em->persist($user);
        $em->flush();
    }

    /**
     * @Route("/submitActivityMessage", name="submit_activity_message")
     */
    public function submitActivityMessageAction(Request $request)
    {

        // ensure we are here from a form submit
        if ($request->isMethod('POST')) {
            $em = $this->getDoctrine()->getManager();

            // construct the form
            $activityMessage = new VocalizrActivity();
            $messageForm     = $this->createFormBuilder($activityMessage)
                        ->add('message_text', TextType::class, [
                            'mapped' => false,
                        ])
                        ->add('last_activity', HiddenType::class, [
                            'mapped' => false,
                        ])
                        ->getForm();
            $messageForm->handleRequest($request);

            if ($messageForm->isValid()) {
                $activityMessage->setUserInfo(null);
                $activityMessage->setActivityType(VocalizrActivity::ACTIVITY_TYPE_MESSAGE);

                // set up the data
                $jsonData              = [];
                $jsonData['user_info'] = ['id' => $this->getUser()->getId(),
                    'username'                 => $this->getUser()->getUsername(), ];
                $jsonData['message'] = $messageForm['message_text']->getData();
                $activityMessage->setData(json_encode($jsonData));

                $em->persist($activityMessage);
                $em->flush();

                $lastActivity = $messageForm['last_activity']->getData();

                // load anything that has been added since the last activity grabbed by this user
                $activity = $em->getRepository('App:VocalizrActivity')
                               ->findActivity($this->getUser(), ['lastActivity' => $lastActivity]);

                // get the data for each activity
                $activityData = [];
                foreach ($activity as $activityItem) {
                    $activityData[] = ['id' => $activityItem['id'],
                        'activity_type'     => $activityItem['activity_type'],
                        'data'              => $activityItem['data'], ];
                }

                return new Response(json_encode(['success' => true,
                    'activityData'                         => $activityData,
                    'lastActivity'                         => $activityData[0]['id'], ]));
            } else {
                return new Response(json_encode(['success' => false,
                    'error'                                => 'Invalid access method', ]));
            }
        } else {
            return new Response(json_encode(['success' => false,
                'error'                                => 'Invalid access method', ]));
        }
    }

    /**
     * Javascript setInterval calls this function to grab new activity for the feed
     *
     * @Route("/pushNewActivity/{lastActivity}", name="push_new_activity")
     */
    public function pushNewActivityAction(Request $request, $lastActivity)
    {
        $em = $this->getDoctrine()->getManager();

        // load anything that has been added since the last activity grabbed by this user
        $activity = $em->getRepository('App:VocalizrActivity')
                       ->findActivity($this->getUser(), ['lastActivity' => $lastActivity]);

        // get the data for each activity
        $activityData = [];
        foreach ($activity as $activityItem) {
            $activityData[] = ['id' => $activityItem['id'],
                'activity_type'     => $activityItem['activity_type'],
                'data'              => $activityItem['data'], ];
        }

        $lastActivity = count($activityData) > 0 ? $activityData[0]['id'] : $lastActivity;

        return new Response(json_encode(['success' => true,
            'activityData'                         => $activityData,
            'lastActivity'                         => $lastActivity, ]));
    }

    /**
     * Gets the data pieces for the site that need to be updated by other user
     * actions
     *
     * @Route("/dataPing/{extra}/{uuid}", name="data_ping")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function dataPingAction(Request $request, $extra = null, $uuid = null)
    {
        $em   = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        if (!$user) {
            exit;
        }
        $responseData                 = [];
        $responseData['success']      = true;
        $responseData['numUnreadMsg'] = $this->getUser()->getNumUnreadMessages();
        if ($extra) {
            $responseData['extra'] = $extra;
            if ($extra == 'msg') {
                $threads      = [];
                $countThreads = $em->getRepository('App:MessageThread')
                        ->findThreadsForUserCount($user);

                $page       = $request->get('page');
                $maxResults = $page * 20;

                if ($countThreads) {
                    $threads = $em->getRepository('App:MessageThread')
                                ->findThreadsForUser($this->getUser(), null, 0, $maxResults);
                }

                if (!$uuid) {
                    $activeThread = $threads[0];
                } else {
                    $activeThread = $em->getRepository('App:MessageThread')
                            ->findOneBy(['uuid' => $uuid]);
                }

                // ensure this user can access this thread
                if ($activeThread) {
                    if ($activeThread->getEmployer() != $user &&
                        $activeThread->getBidder() != $user) {
                        $activeThread    = null;
                        $activeThreadBid = null;
                    } else {
                        // get the bid for the active thread
                        $activeThreadBid = $em->getRepository('App:ProjectBid')
                                              ->findOneBy(['project' => $activeThread->getProject(),
                                                  'user_info'        => $activeThread->getBidder(), ]);
                    }

                    // get the new messages for this thread
                    $messages = $em->getRepository('App:Message')
                                    ->findUnreadForThread($activeThread, $user);
                    $responseData['threadOpen'] = $activeThread->getIsOpen();
                    $responseData['messages']   = $this->renderView(
                        'App:Message:message.html.twig',[
                                'thread'   => $activeThread,
                                'messages' => $messages,
                            ]
                    );

                    // update the thread
                    if ($activeThread->getEmployer() == $user) {
                        $user->setNumUnreadMessages($user->getNumUnreadMessages() - $activeThread->getNumEmployerUnread());
                        $em->flush($user);
                        $activeThread->setNumEmployerUnread(0);
                        $activeThread->setEmployerLastRead(new \DateTime());
                    } else {
                        $user->setNumUnreadMessages($user->getNumUnreadMessages() - $activeThread->getNumBidderUnread());
                        $em->flush($user);
                        $activeThread->setNumBidderUnread(0);
                        $activeThread->setBidderLastRead(new \DateTime());
                    }
                    $em->flush($activeThread);

                    // set the messages to this user in this thread as read
                    $q = $em->getRepository('App:Message')->createQueryBuilder('m');
                    $q->update()
                            ->set('m.read_at', ':now')
                            ->where('m.to_user_info = :user_info')
                            ->andWhere('m.message_thread = :thread')
                            ->andWhere('m.read_at is null');
                    $params = [
                        ':now'       => new \DateTime(),
                        ':user_info' => $user,
                        ':thread'    => $activeThread,
                    ];
                    $q->setParameters($params);
                    $q->getQuery()->execute();
                }

                $responseData['threadsHtml'] = $this->renderView(
                    'Message:threadList.html.twig', [
                            'threads'      => $threads,
                            'countThreads' => $countThreads,
                            'maxResults'   => $maxResults,
                            'activeThread' => $activeThread,
                        ]
                );
            }
        }

        return new Response(json_encode($responseData));
    }

    /**
     * @Route("/dashboard/add-activity", name="add_dashboard_activities" )
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function addDashboardActivitiesAction(Request $request)
    {
        $limit = $request->get('limit', 50);
        $first = $request->get('first', 50);

        $activity = $this->getDoctrine()->getRepository('App:VocalizrActivity')
            ->findActivity(
                $this->getUser(),
                [ 'filter' => $request->getSession()->get('activity_filter') ],
                $limit,
                $first
            );

        $userAudios = $this->getUserAudios($activity);

        $html = $this->renderView('Dashboard/activity.html.twig', [
            'activity'        => $activity,
            'audioLikes'      => $this->getUserLikes($userAudios),
            'audiosByUser'    => $userAudios,
            'audiosByProject' => $this->getFeaturedProjectAudios($activity),
        ]);

        return new JsonResponse(['html' => $html]);
    }

    /**
     * @param array $activity
     * @return UserAudio[]
     */
    private function getUserAudios(&$activity)
    {
        $userIds = [];

        foreach ($activity as $item) {
            if ($item['activity_type'] !== VocalizrActivity::ACTIVITY_TYPE_NEW_MEMBER) {
                continue;
            }

            $userIds[] = $item['actioned_user_info']['id'];
        }

        if (empty($userIds)) {
            return [];
        }

        return $this->getDoctrine()->getRepository('App:UserAudio')
            ->getDefaultAudiosForUsers($userIds);
    }

    /**
     * @param array $activity
     * @return ProjectAudio[]
     */
    private function getFeaturedProjectAudios(&$activity)
    {
        foreach ($activity as $item) {
            if ($item['activity_type'] !== VocalizrActivity::ACTIVITY_TYPE_NEW_PROJECT) {
                continue;
            }

            $projectIds[] = $item['project']['id'];
        }

        if (empty($projectIds)) {
            return [];
        }

        return $this->getDoctrine()->getRepository('App:ProjectAudio')
            ->getFeaturedAudiosForProjects($projectIds)
        ;
    }

    /**
     * @param UserAudio[] $userAudios
     * @return array
     */
    private function getUserLikes($userAudios)
    {
        $user = $this->getUser();
        $audioLikes = [];

        if (!$user) {
            return $audioLikes;
        }

        $audioIds = [];
        foreach ($userAudios as $audio) {
            $audioIds[] = $audio->getId();
        }

        if ($audioIds) {
            $qb = $this->dm->createQueryBuilder('App:AudioLike')
                ->field('from_user_id')->equals($user->getId())
                ->field('audio_id')->in($audioIds)
            ;

            $results = $qb->getQuery()->execute();

            foreach ($results as $result) {
                $audioLikes[] = $result->getAudioId();
            }
        }

        return  $audioLikes;
    }
}
