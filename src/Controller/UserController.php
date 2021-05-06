<?php

namespace App\Controller;

use App\Entity\SubscriptionPlan;
use App\Entity\UserAudio;
use App\Entity\UserPref;
use App\Entity\UserScTrack;
use App\Entity\UserVocalCharacteristic;
use App\Entity\UserVocalStyle;
use App\Entity\VocalCharacteristic;
use App\Entity\VocalStyle;
use App\Form\Type\UserSearchType;
use App\Model\UserAudioModel;
use App\Model\UserInfoLanguageModel;
use App\Model\UserInfoModel;
use App\Service\HelperService;
use App\Service\MembershipSourceHelper;
use App\Service\StatisticsService;
use App\Service\StripeConfigurationProvider;
use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Stripe\Customer;
use Stripe\Stripe;
use Stripe\Subscription;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Entity\Counter;
use App\Entity\EmailChangeRequest;
use App\Entity\UserCancelSub;
use App\Entity\UserInfo;
use App\Entity\UserProProfile;
use App\Entity\UserReview;
use App\Entity\UserSubscription;
use App\Entity\UserVocalCharacteristicVote;
use App\Entity\UserVocalStyleVote;
use App\Entity\UserVoiceTag;
use App\Entity\VoiceTag;
use App\Exception\CreateSpotifyPlaylistException;
use App\Exception\RestoreSubscriptionException;
use App\Exception\UnsubscribeException;
use App\Exception\UserConnectionNotAllowedException;
use App\Form\Type\ProProfileType;
use App\Form\Type\UserEmailType;
use App\Form\Type\UserInfoType;
use App\Form\Type\UserPasswordType;
use App\Model\UserConnectModel;
use App\Model\UserSpotifyPlaylistModel;
use App\Model\UserVideoModel;
use App\Object\MembershipSourceObject;
use App\Repository\UserConnectInviteRepository;

class UserController extends AbstractController
{
    /**
     * @Route("/user", name="user_home")
     * @Template()
     */
    public function indexAction()
    {
        return [];
    }

    /**
     * @Route("/u/{username}", name="user_view")
     * @Template()
     *
     * @param Request                   $request
     * @param ContainerInterface        $container
     * @param StatisticsService         $statisticsService
     * @param UserAudioModel            $audioModel
     * @param UserVideoModel            $userVideoModel
     * @param UserSpotifyPlaylistModel  $userSpotifyPlaylistModel
     *
     * @return RedirectResponse|Response
     */
    public function viewAction(Request $request, ContainerInterface $container, StatisticsService $statisticsService, UserAudioModel $audioModel, UserVideoModel $userVideoModel, UserSpotifyPlaylistModel $userSpotifyPlaylistModel)
    {
        $em   = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        // Find user by username
        /** @var UserInfo|null $userInfo */
        $userInfo = $em->getRepository('App:UserInfo')
                ->getUserByUsername($request->get('username'));
        if (!$userInfo) {
            throw $this->createNotFoundException('User could not be found');
        }

        if (
            $container->getParameter('pro_profile_enabled') &&
            $userInfo->isProProfileEnabled() &&
            $userInfo->isSubscribed()
        ) {
            return $this->redirect($this->generateUrl('user_pro_profile_index', ['username' => $userInfo->getUsername()]));
        }

        $this->recordViewStat($userInfo, $statisticsService);

        // If logged in user
        $isUserFavorite = false;
        $isUserBlocked  = false;
        $userConnect    = false;

        if ($user) {
            // Only see if there is a connection between the 2 if it's not their own profile
            if ($user->getId() != $userInfo->getId()) {
                $userConnect = $em->getRepository('App:UserConnectInvite')
                        ->findOneBy([
                            'to'   => $user,
                            'from' => $userInfo,
                        ]);

                if (!$userConnect) {
                    $userConnect = $em->getRepository('App:UserConnectInvite')
                            ->findOneBy([
                                'to'   => $userInfo,
                                'from' => $user,
                            ]);
                }

                $isUserFavorite = $em->getRepository('App:UserInfo')
                        ->isUserFavorite($user->getId(), $userInfo->getId());

                $isUserBlocked = $em->getRepository('App:UserBlock')
                        ->isUserBlocked($user->getId(), $userInfo->getId());
            }
        }

        $reviewRepo = $em->getRepository('App:UserReview');

        $userReviewsByType = [];
        $reviewsCount      = $reviewRepo->getUserReviewsCount($userInfo);

        foreach (UserReview::$reviewTypes as $reviewType) {
            // Create query for reviews
            $reviewsQuery = $reviewRepo->getUserReviewsByTypeQb(
                $userInfo,
                $reviewType
            );

            $paginator  = $container->get('knp_paginator');
            $userReviewsByType[$reviewType] = $paginator->paginate(
                $reviewsQuery,
                1,
                10// limit per page
            );
        }


        // Get all user audio uploads
        $userAudio = $audioModel->getUserAudios($userInfo);

        // Get gigs in progress
        $workInProgress = $em->getRepository('App:Project')
                ->getProjectsInProgressByUserInvolved($userInfo->getId());

        // Get open gigs
        $openProjects = $em->getRepository('App:Project')
                ->getOpenProjectsByUser($userInfo, $this->getUser());

        $userTags = [];
        if ($userInfo->getIsVocalist()) {
            $userTags['voiceTag'] = $em->getRepository('App:UserVoiceTag')
                    ->getByUserJoinVotedUser($userInfo->getId(), $user ? $user->getId() : null);
            $userTags['vocalStyle'] = $em->getRepository('App:UserVocalStyle')
                    ->getByUserJoinVotedUser($userInfo->getId(), $user ? $user->getId() : null);
            $userTags['vocalCharacteristic'] = $em->getRepository('App:UserVocalCharacteristic')
                    ->getByUserJoinVotedUser($userInfo->getId(), $user ? $user->getId() : null);
        }

        // Get all audio ids on this screen
        $audioLikes = [];
        if ($user) {
            $dm = $container->get('doctrine_mongodb')->getManager();

            $audioIds = [];

            $defaultAudio = $userInfo->getUserAudio();
            if (count($defaultAudio) > 0) {
                $audioIds[] = $defaultAudio[0]->getId();
            }

            foreach ($userAudio as $audio) {
                $audioIds[] = $audio->getId();
            }
            if ($audioIds) {
                $qb = $dm->createQueryBuilder('App:AudioLike')
                        ->field('from_user_id')->equals($user->getId())
                        ->field('audio_id')->in($audioIds);
                $results = $qb->getQuery()->execute();

                foreach ($results as $result) {
                    $audioLikes[] = $result->getAudioId();
                }
            }
        }

        $freePlan = $em->getRepository('App:SubscriptionPlan')->findOneBy([
            'static_key' => 'FREE',
        ]);

        $userVideos = $userVideoModel->getUserVideos($userInfo);

        return $this->render('User/view.html.twig', [
            'userInfo'             => $userInfo,
            'isUserFavorite'       => $isUserFavorite,
            'openProjects'         => $openProjects,
            'reviewsByType'        => $userReviewsByType,
            'totalReviewsCount'    => $reviewsCount,
            'userTags'             => $userTags,
            'userAudio'            => $userAudio,
            'audioLikes'           => $audioLikes,
            'userConnect'          => $userConnect,
            'isUserBlocked'        => $isUserBlocked,
            'freePlan'             => $freePlan,
            'userVideos'           => $userVideos,
            'userVideosCount'      => $userInfo->getVideosCount(),
            'userSpotifyPlaylists' => $userSpotifyPlaylistModel->getSpotifyPlaylists($userInfo, 4),
        ]);
    }

    /**
     * @Route("/user/{id}/view-connections", name="user_view_connections")
     *
     * @param $id
     * @param Request $request
     *
     * @return Response
     */
    public function viewConnectionsAction($id, Request $request)
    {
        $offset = $request->get('offset', 0);
        $json   = $request->get('json', false);

        $userInfo = $this->get('vocalizr_app.model.user_info')->getObject($id);

        $viewerConnections = [];
        $em                = $this->getDoctrine()->getManager();
        $user              = $this->getUser();

        /** @var UserConnectInviteRepository $connectionsInviteRepository */
        $connectionsInviteRepository = $em->getRepository('App:UserConnectInvite');

        $connections = $connectionsInviteRepository->getConnections($userInfo, $offset);

        if ($user && $connections) {
            $userIds = [];
            // Get ids
            foreach ($connections as $connect) {
                $userIds[] = $connect->getConnectedUser($userInfo)->getId();
            }

            // See if logged in member has any of those connections
            $results = $connectionsInviteRepository->getUserConnectionsByIds($user, $userIds);

            if ($results) {
                foreach ($results as $result) {
                    $viewerConnections[$result->getConnectedUser($user)->getId()] = $result;
                }
            }
        }

        $html = $this->renderView('User:viewConnections.html.twig', [
            'connections'       => $connections,
            'viewerConnections' => $viewerConnections,
            'userInfo'          => $userInfo,
            'json'              => $json,
        ]);

        if ($json) {
            return new JsonResponse(['success' => true, 'html' => $html, 'count' => count($connections)]);
        }

        return new Response($html);
    }

    /**
     * Record stats whenever a user's profile has been viewed
     *
     * @param UserInfo          $userInfo
     * @param StatisticsService $statisticsService
     *
     * @return bool
     */
    public function recordViewStat(UserInfo $userInfo, StatisticsService $statisticsService)
    {
        return $statisticsService->recordProfileViewStat($userInfo, $this->getUser());
    }

    /**
     * @Route("/user/edit", name="user_edit")
     * @Template()
     *
     * @param Request                   $request
     * @param HelperService             $helper
     * @param UserInfoLanguageModel     $userInfoLanguageModel
     * @param UserAudioModel            $userAudioModel
     * @param UserVideoModel            $userVideoModel
     * @param UserSpotifyPlaylistModel  $userSpotifyPlaylistModel
     * @param ContainerInterface        $container
     *
     * @return RedirectResponse|Response
     */
    public function editAction(Request $request,
                               HelperService $helper,
                               UserInfoLanguageModel $userInfoLanguageModel,
                               UserAudioModel $userAudioModel,
                               UserVideoModel $userVideoModel,
                               UserSpotifyPlaylistModel $userSpotifyPlaylistModel,
                               ContainerInterface $container
    )
    {
        $em      = $this->getDoctrine()->getManager();
        /** @var UserInfo $user */
        $user    = $this->getUser();

        $userAudioRepo = $em->getRepository(UserAudio::class);

        $userLanguages = $userInfoLanguageModel->getLanguagesByUser($user);

        $form = $this->createForm(UserInfoType::class, $user, ['validation_groups' => ['user_edit'], 'languages' => $userLanguages]);

        $passwordForm = $this->createForm(UserPasswordType::class, [], ['validation_groups' => ['password_change']]);
        $emailForm    = $this->createForm(UserEmailType::class);

        $subscriptionPlan = $em->getRepository(SubscriptionPlan::class)->getActiveSubscription($user->getId());

        // get user preferences
        $userPref = $em->getRepository(UserPref::class)->findOneBy([
            'user_info' => $user,
        ]);
        if (!$userPref) {
            $userPref = new \App\Entity\UserPref();
            $userPref->setUserInfo($user);
        }

        // Get user voice tags
        $userVoiceTags = $em->getRepository(UserVoiceTag::class)->getUserVoiceTags($user->getId());

        // If they are subscribed, get membership from stripe
        $membership = false;
        $cu         = false;
        $userSub    = false;
        if ($user->isSubscribed()) {
            $userSub = $em->getRepository(UserSubscription::class)->findOneBy([
                'user_info' => $user,
                'is_active' => 1,
            ], [
                'next_payment_date' => 'DESC',
                'id'                => 'DESC'
            ]);

            if ($userSub) {
                if ($userSub->getPaypalSubscrId()) {
                } else {
                    $stripeApiKey = $container->getParameter('stripe_api_key');
                    Stripe::setApiKey($stripeApiKey);

                    try {
                        $customer   = Customer::retrieve($user->getStripeCustId());

                        $membership = Subscription::retrieve($userSub->getStripeSubscrId());

                        // Retrieve the customer and expand their default source
                        $cu = Customer::retrieve(
                            ['id' => $user->getStripeCustId(), 'expand' => ['default_source']]
                        );
                    } catch (\Exception $e) {
                        $membership = false;
                    }
                }
            }
        }

        $proProfile = null;
        $proPageForm = null;

        /**
         * Handle Pro Profile edit tab
         */
        if ($user->isSubscribed() && $container->getParameter('pro_profile_enabled')) {
            if ($user->getProProfile()) {
                $proProfile = $user->getProProfile();
            } else {
                $proProfile = new UserProProfile();
                $proProfile->setUserInfo($user);
            }

            $proPageForm = $this->createForm(new ProProfileType(), $proProfile);

            if ($request->get('pro_profile_type') !== null) {
                $proPageForm->handleRequest($request);

                if ($proPageForm->isValid()) {
                    $user->setProProfile($proProfile);
                    $em->flush();
//                    $this->get('session')->getFlashBag()->add('success', 'Pro profile changes saved');

                    return $this->redirect($this->generateUrl('user_edit') . '#pro-profile-page');
                }
            }

        }

        /**
         * Handle User Audio Upload
         */
        if ($request->get('audio_file')) {
            // Check how many files are currently stored
            $userAudios = $userAudioRepo->getProfileTracksByUser($user->getId());
            // Check the amount against the subscription plan
            if (count($userAudios) >= $subscriptionPlan['user_audio_limit']) {
                $request->query->set('error', 'You have reached your user audio limit of <strong>' . $subscriptionPlan['user_audio_limit'] . '</strong>');
            } else {
                $fileName   = $request->get('audio_file');
                $title      = $request->get('title');
                $uploadPath = $this->getParameter('kernel.project_dir') . '/public/uploads/audio/' . $user->getId() . '/';

                // Save audio and move uploaded file
                $userAudio = $userAudioModel->saveUploadedAudio(
                    $user,
                    $title,
                    $fileName
                );

                if ($userAudio) {
                    $request->query->set('notice', 'Track <strong>' . $userAudio->getTitle() . '</strong> has been uploaded to your profile');
                } else {
                    $request->query->set('error', 'There was a problem when uploading your file. Please try again');
                }
            }
        }

        /**
         * Handle Assigning track from SoundCloud
         */
        if ($request->get('sc_track_id', false)) {
            // Make sure they haven't gone over subscription limit
            $userAudios = $userAudioRepo->getProfileTracksByUser($user->getId());

            // Check the amount against the subscription plan
            if (count($userAudios) >= $subscriptionPlan['user_audio_limit']) {
                $request->query->set('error', 'You have reached your user audio limit of <strong>' . $subscriptionPlan['user_audio_limit'] . '</strong>');
            } else {
                if (!$scTrack = $em->getRepository(UserScTrack::Class)->findOneBy(['sc_id' => $request->get('sc_track_id')])) {
                    $request->query->set('error', 'There was a error while trying to assign your SoundCloud Track. Please try again.');
                } else {
                    /**
                     * -------TODO------
                     * Check with soundcloud that the song still exists
                     */
                    $defaultTrack = (count($userAudios) == 0); // If no tracks upload, set as default
                    $userAudio    = $userAudioRepo->saveTrackFromSoundCloud($user->getId(), $scTrack, $defaultTrack);
                    $request->query->set('notice', 'Track <strong>' . $scTrack->getTitle() . '</strong> successfully assigned to your profile');
                }
            }
        }

        /**
         * Handle Remove Avatar
         */
        if ($request->get('remove_avatar')) {
            $user->removeUpload();
            $user->setPath(null);
            $em->persist($user);
            $em->flush();

            $request->query->set('notice', 'Avatar removed');
        }

        /**
         * Remove Track
         */
        if ($request->get('remove_track')) {
            $userAudioId = $request->get('remove_track');
            // Get user audio
            if ($userAudio = $userAudioRepo->findOneBy(['id' => $userAudioId, 'user_info' => $user->getId()])) {
                // If deleted track is default, mark latest track as default
                if ($userAudio->getDefaultAudio()) {
                    $userAudioRepo->setLatestAudioToDefault($user->getId());
                }

                $em->remove($userAudio);
                $em->flush();

                $request->query->set('notice', 'Track <strong>' . $userAudio->getTitle() . '</strong> removed from profile');
            }
        }

        /**
         * Update Featured track
         */
        if ($request->get('mark_featured')) {
            $userAudioId = $request->get('mark_featured');
            // Get user audio
            if ($userAudio = $userAudioRepo->findOneBy(['id' => $userAudioId, 'user_info' => $user->getId()])) {
                $userAudioRepo->setAudioAsDefaultAudio($user->getId(), $userAudioId);
                $request->query->set('notice', 'Track <strong>' . $userAudio->getTitle() . '</strong> is now featured on your profile');
            }
        }

        /**
         * Save General profile information
         */
        if ($request->get('save')) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $userInfo  = $form->getData();
                $languages = $form->get('languages')->getData();

                if (!$userInfo->getIsVocalist() && !$userInfo->getIsProducer()) {
                    $this->get('session')->getFlashBag()->add('error', 'You need to be either a Producer or Vocalist. Please select one');
                    return $this->redirect($this->generateUrl('user_edit'));
                }

                $vocalistFee = $_POST[$form->getName()]['vocalist_fee'];
                if ($vocalistFee) {
                    $vocalistFee = $helper->getMoneyAsInt($vocalistFee);
                    $userInfo->setVocalistFee($vocalistFee * 100);
                }
                $producerFee = $_POST[$form->getName()]['producer_fee'];
                if ($producerFee) {
                    $producerFee = $helper->getMoneyAsInt($producerFee);
                    $userInfo->setProducerFee($producerFee * 100);
                }

                // Get current voice tags
                $currentVoiceTags = $em->getRepository(UserVoiceTag::class)
                        ->getVoiceTagIdsForUser($user->getId());
                $unusedVoiceTags = $currentVoiceTags;

                $voiceTags = $form->get('sounds_like')->getData();
                if (!empty($voiceTags)) {
                    $voiceTags = explode(',', $voiceTags);

                    foreach ($voiceTags as $tag) {
                        if (empty($tag)) {
                            continue;
                        }

                        $tag = trim($tag);
                        $tag = strip_tags($tag);

                        // check if the tag exists
                        $newVoiceTag = $em->getRepository(VoiceTag::class)
                                ->findOneByName($tag);
                        if (!$newVoiceTag) {
                            $newVoiceTag = new VoiceTag();
                            $newVoiceTag->setName(ucwords($tag));
                        }
                        $em->persist($newVoiceTag);

                        $foundTag = false;
                        foreach ($currentVoiceTags as $currentTag) {
                            if (strtoupper($currentTag) == strtoupper($tag)) {
                                $foundTag = true;
                            }
                        }

                        if (!$foundTag) {
                            $newUserVoiceTag = new UserVoiceTag();
                            $newUserVoiceTag->setUserInfo($user);
                            $newUserVoiceTag->setVoiceTag($newVoiceTag);
                            $em->persist($newUserVoiceTag);
                            $userInfo->addUserVoiceTag($newUserVoiceTag);
                            $currentVoiceTags[$newVoiceTag->getId()] = strtoupper($tag);
                        }
                        $key = array_search(strtoupper($tag), $currentVoiceTags);
                        unset($unusedVoiceTags[$key]);
                    }

                    // Now remove all voice tags that were left over (not used anymore)
                    if (count($unusedVoiceTags) > 0) {
                        foreach ($unusedVoiceTags as $userVoiceTagId => $voiceTag) {
                            $em->remove($em->getReference(UserVoiceTag::class, $userVoiceTagId));
                            $em->flush();
                        }
                    }
                }

                /**
                 * Save Vocal Characteristics
                 */
                // Get current vocal characteristics
                $currentVocalChars = $em->getRepository(UserVocalCharacteristic::class)
                        ->getVocalCharacteristicIdsForUser($user->getId());

                foreach ($form->get('vocal_characteristics')->getData() as $vocalCharacteristic) {
                    // If doesn't exist for user, add vocal char
                    if (!in_array($vocalCharacteristic->getId(), $currentVocalChars)) {
                        $entity = new \App\Entity\UserVocalCharacteristic();
                        $entity->setUserInfo($user);
                        $entity->setVocalCharacteristic($vocalCharacteristic);
                        $em->persist($entity);
                        $userInfo->addUserVocalCharacteristic($entity);
                    }
                    $key = array_search($vocalCharacteristic->getId(), $currentVocalChars);
                    unset($currentVocalChars[$key]);
                }
                // Now remove all vocal chars that were left over (not used anymore)
                if (count($currentVocalChars) > 0) {
                    foreach ($currentVocalChars as $userVocalCharId => $vocalCharId) {
                        $em->remove($em->getReference(UserVocalCharacteristic::class, $userVocalCharId));
                        $em->flush();
                    }
                }

                /**
                 * Save Vocal Styles
                 */
                // Get current vocal styles
                $currentVocalStyles = $em->getRepository(UserVocalStyle::Class)
                        ->getVocalStyleIdsForUser($user->getId());

                foreach ($form->get('vocal_styles')->getData() as $vocalStyle) {
                    // If doesn't exist for user, add vocal char
                    if (!in_array($vocalStyle->getId(), $currentVocalStyles)) {
                        $entity = new \App\Entity\UserVocalStyle();
                        $entity->setUserInfo($user);
                        $entity->setVocalStyle($vocalStyle);
                        $em->persist($entity);
                        $userInfo->addUserVocalStyle($entity);
                    }
                    $key = array_search($vocalStyle->getId(), $currentVocalStyles);
                    unset($currentVocalStyles[$key]);
                }
                // Now remove all vocal styles that were left over (not used anymore)
                if (count($currentVocalStyles) > 0) {
                    foreach ($currentVocalStyles as $userVocalStyleId => $vocalStyleId) {
                        $em->remove($em->getReference(UserVocalStyle::class, $userVocalStyleId));
                        $em->flush();
                    }
                }
                /*
                if ($request->get('location')) {
                    $values = $locationForm->getData();
                    $data->setCity($values['city']);
                    $data->setState($values['state']);
                    $data->setCountry($values['country']);
                    $data->setLocationLat($values['location_lat']);
                    $data->setLocationLng($values['location_lng']);
                }
                 *
                 */
                // remmove contact details etc
                $userInfo->cleanProfile();

                if ($userInfo->getCity()) {
                    $userInfo->setCity(ucwords($userInfo->getCity()));
                }

                $userInfo = $userInfoLanguageModel->setUserInfoLanguages($userInfo, $languages);
                if (!$userInfo->isRegistrationFinished()) {
                    $userInfo->setRegistrationFinished(true);
                }

                $em->persist($userInfo);
                $em->flush();

                $user = $userInfo;

                // Set vocalist / producer fee for displaying purposes
                if ($vocalistFee = $user->getVocalistFee()) {
                    $user->setVocalistFee($vocalistFee / 100);
                }
                if ($producerFee = $user->getProducerFee()) {
                    $user->setProducerFee($producerFee / 100);
                }

                $request->query->set('notice', 'Changes saved');

                $userVoiceTags = $voiceTags;
            } else {
                $request->query->set('error', 'Please fix errors below');
            }
        } else {
            // Load data into the form
            $vocalChars = $em->getRepository(VocalCharacteristic::class)->getByUserInfoId($user->getId());
            $choices    = new \Doctrine\Common\Collections\ArrayCollection($vocalChars);
            $form->get('vocal_characteristics')->setData($choices);

            $vocalStyles = $em->getRepository(VocalStyle::class)->getByUserInfoId($user->getId());
            $choices     = new \Doctrine\Common\Collections\ArrayCollection($vocalStyles);
            $form->get('vocal_styles')->setData($choices);

            $form->get('sounds_like')->setData(implode(',', $userVoiceTags));

            if ($vocalistFee = $user->getVocalistFee()) {
                $user->setVocalistFee($vocalistFee / 100);
            }
            if ($producerFee = $user->getProducerFee()) {
                $user->setProducerFee($producerFee / 100);
            }
        }

        /**
         * Save password
         */
        if ($request->get('save_password')) {
            $passwordForm->handleRequest($request);

            if ($passwordForm->isValid()) {
                $userInfo = $passwordForm->getData();
                $encoder  = new MessageDigestPasswordEncoder('sha1', false, 1);

                // Make sure current password matches
                $currentPassword = $encoder->encodePassword($userInfo['current_password'], $user->getSalt());

                if ($currentPassword == $user->getPassword()) {
                    $password = $encoder->encodePassword($userInfo['password'], $user->getSalt());

                    $user->setPassword($password);

                    $em->persist($user);
                    $em->flush();

                    $request->query->set('notice', 'Changes saved');
                } else {
                    $request->query->set('error', 'Please fix error(s) below');
                    $error = new FormError('Incorrect');
                    $passwordForm->get('current_password')->addError($error);
                }
            } else {
                $request->query->set('error', 'Please fix error(s) below');
            }
        }

        /**
         * Update email address
         * Sends a request for them to confirm
         */
        if ($request->get('save_email')) {
            $emailForm->handleRequest($request);

            if ($emailForm->isValid()) {
                $userInfo = $emailForm->getData();

                $emailChangeRequestRepo = $em->getRepository('App:EmailChangeRequest');

                $userEmail          = $em->getRepository('App:UserInfo')->findFirstByEmail($userInfo['email']);
                $emailChangeRequest = $emailChangeRequestRepo->findOneByEmail($userInfo['email']);

                // Check if email doesn't exist
                if ((!$userEmail && !$emailChangeRequest) ||
                        (!$userEmail && $emailChangeRequest && $emailChangeRequest->getUserInfo()->getId() == $user->getId())) {
                    $em->getRepository('App:EmailChangeRequest');
                    $emailRequest = $em->getRepository('App:EmailChangeRequest')->findOneBy(['user_info' => $user->getId()]);

                    // Add email change request
                    // If one already exists, just resend.
                    if (!$emailRequest) {
                        $emailRequest = new EmailChangeRequest();
                        $emailRequest->setUserInfo($user);
                    }

                    $emailRequest->setEmail($userInfo['email']);
                    $key = sha1(time() . rand(0, 9999) . $user->getId());
                    $emailRequest->setUniqueKey(sha1($key));
                    $em->persist($emailRequest);
                    $em->flush();

                    // Send email
                    $dispatcher = $this->get('hip_mandrill.dispatcher');
                    $message    = new \Hip\MandrillBundle\Message();
                    $message
                        ->addTo($emailRequest->getEmail())
                        ->addGlobalMergeVar('USER', $user->getUsernameOrFirstName())
                        ->addGlobalMergeVar('CURRENTEMAIL', $user->getEmail())
                        ->addGlobalMergeVar('NEWEMAIL', $emailRequest->getEmail())
                        ->addGlobalMergeVar('CONFIRMURL', $this->generateUrl('confirm_email', [
                            'key' => $emailRequest->getUniqueKey(),
                        ], true))
                        ->setTrackOpens(true)
                        ->setTrackClicks(true);

                    $dispatcher->send($message, 'member-change-email-request');

                    $request->query->set('notice', 'Please check ' . $userInfo['email'] . ' to confirm your email change');
                } else {
                    $request->query->set('error', 'You cannot use that email address as it already exists in the system');
                }
            } else {
                $request->query->set('error', 'Please fix error(s) below');
            }
        }

        if ($request->get('save_pref')) {
            $userPref->setEmailNewProjects(($request->get('email_new_projects') ? true : false));
            //$userPref->setEmailNewCollabs(($request->get('email_new_collabs') ? true : false));
            $userPref->setEmailProjectInvites(($request->get('email_project_invites') ? true : false));
            $userPref->setEmailTagVoting(($request->get('email_tag_voting') ? true : false));
            $userPref->setConnectRestrictSubscribed(($request->get('connect_restrict_subscribed') ? true : false));
            $userPref->setConnectRestrictCertified(($request->get('connect_restrict_certified') ? true : false));
            $userPref->setConnectAccept(($request->get('connect_accept') ? true : false));
            $userPref->setEmailConnections(($request->get('email_connections') ? true : false));
            $userPref->setEmailMessages(($request->get('email_messages') ? true : false));
            //$userPref->setEmailVocalistSuggestions(($request->get('email_vocalist_suggestions') ? true : false));
            $em->persist($userPref);
            $em->flush();

            $request->query->set('notice', 'Changes saved');
        }

        /**
         * Upload audio track to sound cloud
         */
        if ($request->get('soundcloud_upload')) {
            // Make sure audio exists
            $userAudioId = $request->get('soundcloud_upload');
            if ($userAudio = $userAudioRepo->findOneBy(['user_info' => $user->getId(), 'id' => $userAudioId, 'sc_id' => null])) {
                $userAudio->setSyncQueued(true);
                $userAudio->setSyncResult(null);
                $em->persist($userAudio);
                $em->flush();

                // Execute background job to sync audio file with soundcloud account
                exec('php ' . $this->get('kernel')->getRootDir() . 'console vocalizr:sc-sync ' . $request->get('soundcloud_upload') . ' > /dev/null 2>&1 &');
            }
            $request->query->set('notice', 'Soundcloud Upload started for <strong>' . $userAudio->getTitle() . '</strong>');
        }

        // Get user audio tracks
        $userAudios = $userAudioRepo->getProfileTracksByUser($user->getId());

        $user_audio_limit = $subscriptionPlan ? $subscriptionPlan['user_audio_limit'] : null;
        // Only display what subscription plan allows
        $userAudios = array_slice($userAudios, 0, $user_audio_limit);

        if (!$user->getCompletedProfile()) {
            $this->checkProfileCompleteness();
        }

        return $this->render('User/edit.html.twig', [
            'user'          => $user,
            'userVoiceTags' => $userVoiceTags,
            'form'          => $form->createView(),
            //'locationForm' => $locationForm->createView(),
            'proProfileEnabled'    => $this->getParameter('pro_profile_enabled'),
            'passwordForm'         => $passwordForm->createView(),
            'proPageForm'          => $proPageForm ? $proPageForm->createView() : null,
            'proProfile'           => $proProfile,
            'emailForm'            => $emailForm->createView(),
            'subscriptionPlan'     => $subscriptionPlan,
            'userAudios'           => $userAudios,
            'userPref'             => $userPref,
            'membership'           => $membership,
            'cu'                   => $cu,
            'userSub'              => $userSub,
            'userVideos'           => $userVideoModel->getUserVideos($user, 0, 10),
            'userVideosCount'      => $user->getVideosCount(),
            'userSpotifyPlaylists' => $userSpotifyPlaylistModel->getSpotifyPlaylists($user, 4),
        ]);
    }

    /**
     * @Route("/user/save-avatar", name="user_save_avatar")
     *
     * @param Request        $request
     * @param HelperService  $helper
     * @param UserInfoModel  $userInfoModel
     *
     * @return JsonResponse
     * @throws \Exception
     */
    public function saveAvatarAction(Request $request, HelperService $helper, UserInfoModel $userInfoModel)
    {
        $uploadDir = $helper->getUploadTmpDir();
        /** @var UserInfo $user */
        $user      = $this->getUser();
        $em        = $this->getDoctrine()->getManager();
        $f         = $request->get('f');

        if (!$f) {
            return new JsonResponse(['error' => 'Invalid request']);
        }

        $pathInfo = pathinfo($f);

        if (!$pathInfo['extension']) {
            return new JsonResponse(['error' => 'Error while saving (1)']);
        }

        // Check file still exists
        if (!file_exists($uploadDir . '/' . $f)) {
            return new JsonResponse(['error' => 'Error while saving (2)']);
        }

        // Generate user avatar filename
        $avatarFilename = sha1(uniqid(mt_rand(), true));
        $avatarFilename = $avatarFilename . '.' . $pathInfo['extension'];

        $destinationPath = $user->getUploadRootDir() . $avatarFilename;

        // Attempt to copy file from tmp directory
        if (!copy($uploadDir . '/' . $f, $destinationPath)) {
            return new JsonResponse(['error' => 'Error while saving (3)']);
        }
        // Remove file from tmp dir
        unlink($uploadDir . '/' . $f);

        // Remove existing upload if any
        $user->removeUpload();
        $em->persist($user);

        $user->setPath($avatarFilename);
        $em->persist($user);
        $em->flush();

        $userInfoModel->generateThumbnails($user);

        if (!$user->getCompletedProfile()) {
            $this->checkProfileCompleteness();
        }

        return new JsonResponse(['img' => $avatarFilename]);
    }

    /**
     * @Route("/user/edit/background", name="user_upload_pro_background")
     * @Route("/user/edit/image/{id}", name="user_upload_pro_image")
     *
     * @param Request $request
     * @param ContainerInterface $container
     *
     * @return JsonResponse
     */
    public function updateBackgroundImage(Request $request, ContainerInterface $container)
    {
        if (!$container->getParameter('pro_profile_enabled')) {
            throw new AccessDeniedHttpException('Pro profile is disabled in configuration');
        }

        /** @var UserInfo $user */
        $user      = $this->getUser();
        $em        = $this->getDoctrine()->getManager();

        $proProfile = $user->getProProfile();
        if (!$proProfile) {
            $user->setProProfile($proProfile = new UserProProfile());
            $proProfile->setUserInfo($user);
        }

        $file     = $request->files->get('file');
        $filePath = $request->get('name');

        /** @var UploadedFile $file */
        if (is_null($file)) {
            return new JsonResponse(['error' => 'Error while saving (2, no file in request)']);
        }

        $imageProcessor = $this->get('vocalizr_app.service.process_image');

        $route = $request->get('_route');

        if ($route === 'user_upload_pro_background') {
            $filename = $imageProcessor->processUploadedImage($file->getPathname(), $filePath, [
                UserProProfile::BACKGROUND_NETWORK_DIRECTORY => [
                    'processing' => [
                        'thumbnail' => ['width' => 1920, 'height' => 575],
                    ],
                    'quality'   => 75,
            ]]);

            if ($proProfile->getHeroImage()) {
                $imageProcessor->removeImage($proProfile->getHeroImageWebPath());
            }

            $proProfile->setHeroImage($filename);
            $img = $proProfile->getHeroImageWebPath();
        } else {
            $imageIndex = $request->get('id');
            if ($imageIndex === null) {
                return new JsonResponse(['error' => 'Image index not found in request']);
            }
            $imageIndex--;

            $filename = $imageProcessor->processUploadedImage($file->getPathname(), $filePath, [
                UserProProfile::ABOUT_ME_NETWORK_DIRECTORY => ['quality' => 93],
                UserProProfile::ABOUT_ME_THUMBNAIL_NETWORK_DIRECTORY => [
                    'processing' => [
                        'thumbnail' => ['width' => 766, 'height' => 482],
                    ]
                ],
            ]);

            if ($proProfile->getAboutMeImageWebPath($imageIndex)) {
                $imageProcessor->removeImage($proProfile->getAboutMeImageWebPath($imageIndex));
                $imageProcessor->removeImage($proProfile->getAboutMeThumbnailImageWebPath($imageIndex));
            }

            $proProfile->setAboutMeImage($imageIndex, $filename);
            $img = $proProfile->getAboutMeThumbnailImageWebPath($imageIndex);
        }

        $em->flush();

        @unlink($file->getPathname());

        return new JsonResponse(['img' => $img]);
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
     * @Template()
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function completeProfileModalAction(Request $request)
    {
        return [];
    }

    /**
     * Assign audio track to user profile
     * JSON reply
     *
     * @Route("/user/assign/audio", name="user_assign_audio")
     *
     * @param Request $request
     */
    public function assignAudioTrack(Request $request)
    {
        $user = $this->getUser();
        $em   = $this->getDoctrine()->getManager();

        if (!$request->isMethod('POST')) {
            return new JsonResponse(['error' => 'Invalid request']);
        }

        if (!$request->get('file') && !$request->get('sc_id')) {
            return new JsonResponse(['error' => 'Invalid request']);
        }

        if ($request->get('file')) {
            $fileName = $request->get('file');
            $title    = $request->get('title');

            $uploadPath = $this->get('kernel')->getRootDir() . DIRECTORY_SEPARATOR . '../uploads/audio/' . $user->getId() . '/';

            // Save audio and move uploaded file
            if (!$userAudio = $em->getRepository('App:UserAudio')
                    ->saveUploadedFile($user->getId(), $title, $fileName)) {
                return new JsonResponse(['error' => 'Failed to assign audio to your profile. Try uploading again.']);
            }
        }

        /**
         * Handle Assigning track from SoundCloud
         */
        if ($request->get('sc_id')) {
            if (!$scTrack = $em->getRepository('App:UserScTrack')->findOneBy(['sc_id' => $request->get('sc_id')])) {
                return new JsonResponse(['error' => 'Failed to assign audio to your profile. Try uploading again.']);
            }

            $defaultTrack = true;
            $userAudio    = $em->getRepository('App:UserAudio')->saveTrackFromSoundCloud($user->getId(), $scTrack, $defaultTrack);
        }

        return new JsonResponse(['success' => true]);
    }

    /**
     * User favorites
     *
     * @Route("/user/favorites", name="user_favorites")
     * @Route("/user/favorites/del/{username}", name="user_favorite_del", defaults={"action" = "delete"})
     * @Template()
     *
     * @param Request $request
     * @param ContainerInterface $container
     *
     * @return array
     */
    public function favoritesAction(Request $request, ContainerInterface $container)
    {
        $em           = $this->getDoctrine()->getManager();
        $user         = $this->getUser();
        $userInfoRepo = $em->getRepository('App:UserInfo');

        $form = $this->createForm(UserSearchType::class);

        // If user has requested to delete their favorite
        if ($request->get('action') && $username = $request->get('username')) {
            // Find favorite by username
            if ($deleteUserFav = $userInfoRepo->findOneBy(['username' => $username])) {
                $user->removeFavorite($deleteUserFav);
                $request->query->set('notice', $deleteUserFav->getUsername() . ' is no longer your favorite');
                $em->persist($user);
                $em->flush();
            }
        }

        // Get query for pager to find favorites for logged in user
        $q = $userInfoRepo->createQueryBuilder('ui')
            ->select('ui, up')
            ->leftJoin('ui.user_pref', 'up')
            ->where('ui.id IN (SELECT f.id FROM App:UserInfo ui2 INNER JOIN ui2.favorites f WHERE ui2.id = :userInfoId)')
            ->setParameter(':userInfoId', $user->getId())
            ->andWhere('ui.is_active = 1')
        ;

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
                    $q->addSelect('uvt, vt');
                    $q->innerJoin('ui.user_voice_tags', 'uvt');
                    $q->innerJoin('uvt.voice_tag', 'vt');
                    $q->andWhere('vt.name = :soundsLike');
                    $params[':soundsLike'] = $data['sounds_like'];
                }

                if ($data['studio_access']) {
                    $q->andWhere('ui.studio_access = 1');
                }

                // RH - Remove this as we don't want anyone to show in search that doesn't have
                //    - an audio file
                // if ($data['audio']) {
                //     $q->andWhere('ua.id IS NOT NULL');
                // }

                if (isset($data['vocal_characteristic']) && count($data['vocal_characteristic']) > 0) {
                    $vocalIds = [];
                    foreach ($data['vocal_characteristic'] as $vocal) {
                        $vocalIds[] = $vocal->getId();
                    }
                    $q->innerJoin('ui.vocalCharacteristics', 'vc');
                    $q->andWhere($q->expr()->in('vc.id', $vocalIds));
                }

                if (isset($data['vocal_style']) && count($data['vocal_style']) > 0) {
                    $vocalIds = [];
                    foreach ($data['vocal_style'] as $vocal) {
                        $vocalIds[] = $vocal->getId();
                    }
                    $q->innerJoin('ui.vocalStyles', 'vs');
                    $q->andWhere($q->expr()->in('vs.id', $vocalIds));
                }

                if ($data['city']) {
                    $q->andWhere($q->expr()->like('ui.city', ':city'));
                    $params[':city'] = '%' . $data['city'] . '%';
                }
            }
        } else {
            // Default query
            $q->orderBy('ui.last_login', 'DESC');
        }

        if (isset($params) && count($params) > 0) {
            $q->setParameters($params);
        }

        $paginator  = $container->get('knp_paginator');
        $pagination = $paginator->paginate(
            $q->getQuery(),
            $request->query->get('page', 1)/*page number*/,
            10// limit per page
        );

        // If user is logged in
        // - Get Audio likes
        // - Get user connects
        $userConnects = [];
        $userIds      = [];

        $userAudios = [];
        $audioIds   = [];

        foreach ($pagination as $result) {
            $userIds[] = $result->getId();
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

        // Get all audio ids on this screen
        $audioLikes = [];
        $dm         = $container->get('doctrine_mongodb')->getManager();

        if ($audioIds) {
            $qb = $dm->createQueryBuilder('App:AudioLike')
                            ->field('from_user_id')->equals($user->getId())
                            ->field('audio_id')->in($audioIds);
            $results = $qb->getQuery()->execute();

            foreach ($results as $result) {
                $audioLikes[] = $result->getAudioId();
            }
        }

        $freePlan = $em->getRepository('App:SubscriptionPlan')->findOneBy([
            'static_key' => 'FREE',
        ]);

        return $this->render('User/favorites.html.twig', [
            'pagination'    => $pagination,
            'form'          => $form->createView(),
            'audioLikes'    => $audioLikes,
            'userConnects'  => $userConnects,
            'freePlan'      => $freePlan,
            'userAudioList' => $userAudios,
        ]);
    }

    /**
     * Add/remove user as favorite
     * AJAX call
     *
     * @Route("/user/favorite/{username}", name="user_favorite")
     */
    public function favoriteAction(Request $request)
    {
        $em           = $this->getDoctrine()->getManager();
        $user         = $this->getUser();
        $userInfoRepo = $em->getRepository('App:UserInfo');

        // Check for user info id
        if (!$username = $request->get('username')) {
            return new JsonResponse(['error' => 'Invalid Username']);
        }

        // Make sure user they want to follow exists
        if (!$userFav = $userInfoRepo->findOneBy(['username' => $username])) {
            return new JsonResponse(['error' => 'Invalid User']);
        }

        // If they are already a fav, then remove
        if ($userInfoRepo->isUserFavorite($user->getId(), $userFav->getId())) {
            $user->removeFavorite($userFav);
            $em->persist($user);
            $em->flush();
            return new JsonResponse(['success' => 'removed']);
        }

        // Otherwise add them
        $user->addFavorite($userFav);
        $em->persist($user);
        $em->flush();

        return new JsonResponse(['success' => 'added']);
    }

    /**
     * Add/remove user from block list
     * AJAX call
     *
     * @Route("/user/block/{username}", name="user_block")
     */
    public function blockAction(Request $request)
    {
        $em            = $this->getDoctrine()->getManager();
        $user          = $this->getUser();
        $userInfoRepo  = $em->getRepository('App:UserInfo');
        $userBlockRepo = $em->getRepository('App:UserBlock');

        // Check for user info id
        if (!$username = $request->get('username')) {
            return new JsonResponse(['error' => 'Invalid Username']);
        }

        // Make sure user they want to follow exists
        if (!$userBlock = $userInfoRepo->findOneBy(['username' => $username])) {
            return new JsonResponse(['error' => 'Invalid User']);
        }

        // If they are already a blocked, then remove
        $userBlocked = $userBlockRepo->findOneBy(['user_info' => $user, 'block_user' => $userBlock]);
        if ($userBlocked) {
            $em->remove($userBlocked);
            $em->flush();
            return new JsonResponse(['success' => 'removed']);
        }

        // Find any chats, and close related to that user
        $em->getRepository('App:MessageThread')
                ->closeOpenThreadsBetweenUsers($user, $userBlock);

        // remove any open project bids
        $qb = $em->createQueryBuilder();
        $qb->delete('App:ProjectBid', 'pb');
        $qb->where('pb.user_info = :user AND pb.flag IS NULL');
        $qb->setParameter('user', $user);
        $qb->getQuery()->execute();

        // add them
        $obj = new \App\Entity\UserBlock();
        $obj->setUserInfo($user);
        $obj->setBlockUser($userBlock);
        $em->persist($obj);
        $em->flush();

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['success' => 'added']);
        } else {
            $this->get('session')->getFlashBag()->add('success', 'Member added to your block list');
            return $this->redirect($this->generateUrl('user_view', ['username' => $userBlock->getUsername()]));
        }
    }

    /**
     * User tag vote
     * Ajax call
     *
     * @Route("/tag-vote", name="user_tag_vote")
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function userTagVoteAction(Request $request)
    {
        $securityContext = $this->get('security.context');

        if (false === $this->get('security.context')->isGranted('ROLE_USER')) {
            return new JsonResponse(['error' => 'You are required to be logged in to vote on member tags']);
        }

        $user = $this->getUser();
        $em   = $this->getDoctrine()->getManager();

        $id   = $request->get('id');
        $type = $request->get('type');

        $count      = 0;
        $add        = true;
        $notAllowed = false;

        if (!$id || !$type) {
            return new JsonResponse([
                'count'   => $count,
                'success' => false,
            ]);
        }

        // Get tag type
        if ($type == 'voiceTag') {
            /** @var UserVoiceTag $userVoiceTag */
            $userVoiceTag = $em->getRepository('App:UserVoiceTag')
                    ->getByIdJoinVotedUser($id, $user->getId());
            // Does tag exist and make sure it's not the logged in user trying
            // to vote for themself
            if ($userVoiceTag && $userVoiceTag->getUserInfo()->getId() != $user->getId()) {
                // If vote doesn't exist, then add
                if (count($userVoiceTag->getUserVoiceTagVotes()) == 0) {
                    $userVoiceTag->setAgree($userVoiceTag->getAgree() + 1);
                    $em->persist($userVoiceTag);

                    // Add vote
                    $vote = new \App\Entity\UserVoiceTagVote();
                    $vote->setFromUserInfo($user);
                    $vote->setUserVoiceTag($userVoiceTag);
                    $em->persist($vote);
                }
                // Otherwise remove vote
                else {
                    $userVoiceTag->setAgree($userVoiceTag->getAgree() - 1);

                    // Delete vote
                    $userVoiceTagVote = $userVoiceTag->getUserVoiceTagVotes();
                    $em->remove($userVoiceTagVote[0]);
                    $em->persist($userVoiceTag);
                    $add = false;
                }

                $count = $userVoiceTag->getAgree();
                $em->flush();
            } else {
                $notAllowed = true;
            }
        }
        if ($type == 'vocalStyle') {
            $userVocalStyle = $em->getRepository('App:UserVocalStyle')
                    ->getByIdJoinVotedUser($id, $user->getId());
            // Does tag exist and make sure it's not the logged in user trying
            // to vote for themself
            if ($userVocalStyle && $userVocalStyle->getUserInfo()->getId() != $user->getId()) {
                // If vote doesn't exist, then add
                if (count($userVocalStyle->getUserVocalStyleVotes()) == 0) {
                    $userVocalStyle->setAgree($userVocalStyle->getAgree() + 1);
                    $em->persist($userVocalStyle);

                    // Add vote
                    $vote = new UserVocalStyleVote();
                    $vote->setFromUserInfo($user);
                    $vote->setUserVocalStyle($userVocalStyle);
                    $em->persist($vote);
                }
                // Otherwise remove vote
                else {
                    $userVocalStyle->setAgree($userVocalStyle->getAgree() - 1);

                    // Delete vote
                    $userVocalStyleVote = $userVocalStyle->getUserVocalStyleVotes();
                    $em->remove($userVocalStyleVote[0]);
                    $em->persist($userVocalStyle);
                    $add = false;
                }

                $count = $userVocalStyle->getAgree();
                $em->flush();
            } else {
                $notAllowed = true;
            }
        }

        if ($type == 'vocalCharacteristic') {
            $userVocalCharacteristic = $em->getRepository('App:UserVocalCharacteristic')
                    ->getByIdJoinVotedUser($id, $user->getId());
            // Does tag exist and make sure it's not the logged in user trying
            // to vote for themself
            if ($userVocalCharacteristic && $userVocalCharacteristic->getUserInfo()->getId() != $user->getId()) {
                // If vote doesn't exist, then add
                if (count($userVocalCharacteristic->getUserVocalCharacteristicVotes()) == 0) {
                    $userVocalCharacteristic->setAgree($userVocalCharacteristic->getAgree() + 1);
                    $em->persist($userVocalCharacteristic);

                    // Add vote
                    $vote = new UserVocalCharacteristicVote();
                    $vote->setFromUserInfo($user);
                    $vote->setUserVocalCharacteristic($userVocalCharacteristic);
                    $em->persist($vote);
                }
                // Otherwise remove vote
                else {
                    $userVocalCharacteristic->setAgree($userVocalCharacteristic->getAgree() - 1);

                    // Delete vote
                    $userVocalCharacteristicVote = $userVocalCharacteristic->getUserVocalCharacteristicVotes();
                    $em->remove($userVocalCharacteristicVote[0]);
                    $em->persist($userVocalCharacteristic);
                    $add = false;
                }

                $count = $userVocalCharacteristic->getAgree();
                $em->flush();
            } else {
                $notAllowed = true;
            }
        }

        if ($notAllowed) {
            return new JsonResponse(['success' => false]);
        }

        return new JsonResponse([
            'count'      => $count,
            'success'    => true,
            'vote_added' => $add,
        ]);
    }

    /**
     * Stream user specific audio for audio player
     * Get's slug and finds audio file and reads file to output
     *
     * @Route("/audio/{slug}", name="user_audio")
     * @param Request $request
     * @param HelperService $helper
     */
    public function audioAction(Request $request, HelperService $helper, Packages $assetsManager)
    {
        $em      = $this->getDoctrine()->getManager();
        $user    = $this->getUser();
//        $helper  = $this->get('service.helper');

        // Get user audio by slug
        $userAudio = $em->getRepository('App:UserAudio')->findOneBy(['slug' => $request->get('slug')]);

        if (!$userAudio) {
            throw $this->createNotFoundException('Audio file not found 1');
        }

        $file = $userAudio->getAbsolutePath();
        if (!file_exists($file)) {
            throw $this->createNotFoundException('Audio file not found 2');
        }

        // redirect to actual file
        header('Location: '. $assetsManager->getUrl('uploads/audio/user/' . $userAudio->getUserInfo()->getId() . '/' . $userAudio->getPath()) );
        exit;

        $helper->streamAudio($file);
    }

    /**
     * Update preference for email collabs
     *
     * @Route("/user/pref/emailCollabs")
     */
    public function prefEmailCollabsAction(Request $request)
    {
        $em       = $this->getDoctrine()->getEntityManager();
        $userPref = $em->getRepository('App:UserPref')->findOneBy([
            'user_info' => $this->getUser(),
        ]);
        if (!$userPref) {
            $userPref = new \App\Entity\UserPref();
        }

        if ($request->get('emailCollabs') == 'true') {
            $userPref->setEmailNewCollabs(true);
        } else {
            $userPref->setEmailNewCollabs(false);
        }
        $em->persist($userPref);
        $em->flush();
        return new JsonResponse(['success' => true]);
    }

    /**
     * @Route("/user/upgrade", name="user_upgrade")
     * @Template()
     *
     * @param Request                $request
     * @param MembershipSourceHelper $tracker
     * @param ContainerInterface     $container
     *
     * @return RedirectResponse|Response
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function upgradeAction(Request $request, MembershipSourceHelper $tracker, ContainerInterface $container)
    {
        /** @var UserInfo $user */
        $user    = $this->getUser();
        $em      = $this->getDoctrine()->getManager();

        $userSource = $tracker->handleRequest($request, MembershipSourceObject::STATUS_UPGRADE_PAGE);

        $annual = $request->get('plan', '') == 'yearly';

        if ($request->get('source') && $request->isMethod('GET')) {
            return $this->redirect($this->generateUrl(
                $request->get('_route'),
                array_diff_key($request->query->all(), array_flip(['source', 'return']))
            ));
        }

        $proPlan = $em->getRepository('App:SubscriptionPlan')->findOneBy([
            'static_key' => 'PRO',
        ]);

        // Check current users subscription and if they have met their quota
        $currentPlan = $em->getRepository('App:SubscriptionPlan')->getActiveSubscription($this->getUser()->getId());

        if ($request->isMethod('POST') && $currentPlan['static_key'] != 'PRO') {
            $newPlan      = $em->getRepository('App:SubscriptionPlan')->findOneBy(['static_key' => 'PRO']);
            $token        = $_POST['stripeToken'];
            $stripeApiKey = $container->getParameter('stripe_api_key');
            Stripe::setApiKey($stripeApiKey);


            $monthPlan = 'price_1H25V0FqlChUDt08cnY2JJns';
            $yearPlan  = 'price_1H4HjMFqlChUDt08QIJf11x7';

            // Which plan will they go on
            $plan = $monthPlan;
            if (isset($_POST['freq'])) {
                if ($_POST['freq'] == 'yearly') {
                    $plan = $yearPlan;
                }
            }

            if ($user->getStripeCustId()) {
                $customer = Customer::retrieve($user->getStripeCustId());

                // update the customers card details to the ones just entered - in case old card is expired
                $customer->source = $token; // obtained with Checkout
                $customer->save();

                $result = Subscription::create(['plan' => $plan]);
                $subId  = $result['id'];
            } else {
                $result = Customer::create([
                    'source' => $token,
                    'plan'   => $plan,
                    'email'  => $user->getEmail(),
                ]);
                $subId = Subscription::all(['customer' => $result->id, 'limit' => 1])[0]['id'];
                $this->getUser()->setStripeCustId($result['id']);
            }

            $userSubscription = new \App\Entity\UserSubscription();
            $userSubscription->setUserInfo($this->getUser());
            $userSubscription->setStripeSubscrId($subId);
            $userSubscription->setPaypalSubscrId(null);
            $userSubscription->setSubscriptionPlan($newPlan);
            $userSubscription->setIsActive(true);
            $userSubscription->setDateCommenced(new \DateTime());
            $userSubscription->setSource($userSource->getSource());
            $em->persist($userSubscription);

            $this->getUser()->setSubscriptionPlan($newPlan);

            $tracker->setStatus(MembershipSourceObject::STATUS_START_PAYING);

            return $this->redirect($this->generateUrl('paypal_pro_success') . '?stripe=1');
        }
        $em->flush();

        // See if they have any user sub, depends if we do trial or not
        $userSub = $em->getRepository('App:UserSubscription')->findOneBy(['user_info' => $this->getUser()]);

        $data = [
            'currentPlan' => $currentPlan,
            'proPlan'     => $proPlan,
            'userSub'     => $userSub,
            'annual'      => $annual,
            'user_source' => $userSource,
        ];

        return $this->render('User/upgrade.html.twig', $data);
    }

    /**
     * @Route("/user/updateCard", name="user_update_card")
     *
     * @param Request            $request
     * @param ContainerInterface $container
     *
     * @return Response
     */
    public function updateCardAction(Request $request, ContainerInterface $container)
    {
        $token        = $_POST['stripeToken'];
        $stripeApiKey = $container->getParameter('stripe_api_key');
        Stripe::setApiKey($stripeApiKey);

        $user = $this->getUser();

        if (isset($_POST['stripeToken'])) {
            try {
                $cu     = Customer::retrieve($user->getStripeCustId()); // stored in your application
                $cu->source = $token; // obtained with Checkout
                $cu->save();

                $success = 'Your card details have been updated!';

                $this->get('session')->getFlashBag()->add('notice', $success);

                return $this->redirect($this->generateUrl('user_edit') . '#membership');
            } catch (\Stripe\Error\Card $e) {

            // Use the variable $error to save any errors
                // To be displayed to the customer later in the page
                $body  = $e->getJsonBody();
                $err   = $body['error'];
                $error = $err['message'];
                $this->get('session')->getFlashBag()->add('error', $error);
                return $this->redirect($this->generateUrl('user_edit') . '#membership');
            }
        }
    }

    /**
     * Prompt user with popup to send connection
     *
     * @Route("/user/connect/{username}/request", name="user_connect_request")
     * @Template()
     *
     * @param Request                       $request
     * @param ContainerInterface            $container
     * @param UserConnectModel              $connectionModel
     * @param StripeConfigurationProvider   $stripeConfig
     *
     * @return Response
     */
    public function connectRequestAction(Request $request, ContainerInterface $container, UserConnectModel $connectionModel, StripeConfigurationProvider $stripeConfig)
    {

        $em                    = $this->getDoctrine()->getManager();
        /** @var UserInfo $user */
        $user                  = $this->getUser();
        $userConnectInviteRepo = $em->getRepository('App:UserConnectInvite');

        // Get user who we are wanting to connect with
        $toUser = $em->getRepository('App:UserInfo')->findOneBy([
            'username'  => $request->get('username'),
            'is_active' => true,
        ]);
        if (!$toUser) {
            return $this->forward('App:Default:error', [
                'error' => 'Invalid member',
            ]);
        }

//        $connectionModel = $this->get('vocalizr_app.model.user_connect');

        $violations = $connectionModel->validateConnectionAttempt($user, $toUser);

        if ($violations) {
            $violations = new UserConnectionNotAllowedException($violations);

            if ($violations->hasViolation(UserConnectModel::CONSTRAINT_LIMIT_REACHED)) {
//                $stripeConfig = $this->get('vocalizr_app.stripe_configuration_provider');
                $priceValues = $stripeConfig->getProductPriceValues('extend_connections_limit');

                return $this->render('User/include/extend_connections.html.twig', [
                    'options' => $priceValues,
                ], new Response('', 400));
            }

            return $this->forward('App:Default:error', [
                'error'      => 'This connection is not allowed',
                'violations' => $violations,
            ]);
        }

        return $this->render('User/connectRequest.html.twig', [
            'toUser'           => $toUser,
            'subscriptionPlan' => $user->getSubscriptionPlan(),
            'defaultMessage'   => $container->getParameter('connect_default_msg'),
            'connectCount'     => $connectionModel->getConnectionsLeft($user),
        ]);
    }

    /**
     * Request connection request
     *
     * @Route("/user/connect/{username}", name="user_connect")
     *
     * @param Request           $request
     * @param UserConnectModel  $connectionModel
     */
    public function connectAction(Request $request, UserConnectModel $connectionModel)
    {
        $em                    = $this->getDoctrine()->getManager();
        $user                  = $this->getUser();

        // Get user who we are wanting to connect with
        $toUser = $em->getRepository('App:UserInfo')->findOneBy([
            'username'  => $request->get('username'),
            'is_active' => true,
        ]);
        if (!$toUser) {
            return $this->forward('App:Default:error', [
                'error' => 'Invalid member',
            ]);
        }

//        $connectionModel = $this->get('vocalizr_app.model.user_connect');

        try {
            $connectionModel->requestConnection($user, $toUser, $request->get('message'));
        } catch (UserConnectionNotAllowedException $e) {
            if ($e->hasViolation(UserConnectModel::CONSTRAINT_NOT_SUBSCRIBED)) {
                return $this->render('include/panel/unlimited_connections_panel.html.twig');
            }

            if ($e->getViolations()) {
                return $this->forward('App:Default:error', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return new JsonResponse(['success' => true]);
    }

    /**
     * Cancel an connect request
     *
     * @Route("/user/connect/{username}/cancel", name="user_connect_cancel")
     *
     * @param Request $request
     */
    public function connectCancelAction(Request $request)
    {
        $em                    = $this->getDoctrine()->getManager();
        $user                  = $this->getUser();
        $userConnectInviteRepo = $em->getRepository('App:UserConnectInvite');

        // Get user who we are wanting to connect with
        $connectUser = $em->getRepository('App:UserInfo')->findOneBy([
            'username'  => $request->get('username'),
            'is_active' => true,
        ]);
        if (!$connectUser) {
            return $this->forward('App:Default:error', [
                'error' => 'Invalid member',
            ]);
        }

        // Check to see if there has been a request
        $connect = $userConnectInviteRepo->findOneBy([
            'to'           => $connectUser,
            'from'         => $user,
            'connected_at' => null,
        ]);

        if (!$connect) {
            return $this->forward('App:Default:error', [
                'error' => 'Connection request has already been accepted',
            ]);
        }

        $em->remove($connect);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    /**
     * Accept connection invite
     *
     * @Route("/user/connect/{username}/accept", name="user_connect_accept")
     *
     * @param Request $request
     */
    public function connectAcceptAction(Request $request)
    {
        $em                    = $this->getDoctrine()->getManager();
        $user                  = $this->getUser();
        $userConnectInviteRepo = $em->getRepository('App:UserConnectInvite');

        // Get user who we are wanting to connect with
        $connectUser = $em->getRepository('App:UserInfo')->findOneBy([
            'username'  => $request->get('username'),
            'is_active' => true,
        ]);
        if (!$connectUser) {
            return $this->forward('App:Default:error', [
                'error' => 'Invalid member',
            ]);
        }

        // Check to see if there has been a request
        $connect = $userConnectInviteRepo->findOneBy([
            'from'         => $connectUser,
            'to'           => $user,
            'connected_at' => null,
        ]);

        if (!$connect) {
            return $this->forward('App:Default:error', [
                'error' => 'There is no connection invite to accept',
            ]);
        }

        $connect->setConnectedAt(new \DateTime());
        $connect->setStatus(true);
        $em->persist($connect);
        $em->flush();

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['success' => true, 'message' => 'You now are connected with ' . $connectUser->getDisplayName()]);
        }

        $this->get('session')->getFlashBag()->add('success', 'You are now connected with ' . $connectUser->getDisplayName());

        $em->getRepository('App:Notification')
                ->updateUnreadCount($connectUser);

        return $this->redirect($this->generateUrl('connections'));
    }

    /**
     * Ignore connection invite
     *
     * @Route("/user/connect/{username}/ignore", name="user_connect_ignore")
     *
     * @param Request $request
     */
    public function connectIgnoreAction(Request $request)
    {
        $em                    = $this->getDoctrine()->getManager();
        $user                  = $this->getUser();
        $userConnectInviteRepo = $em->getRepository('App:UserConnectInvite');

        // Get user who we are wanting to connect with
        $connectUser = $em->getRepository('App:UserInfo')->findOneBy([
            'username'  => $request->get('username'),
            'is_active' => true,
        ]);
        if (!$connectUser) {
            return $this->forward('App:Default:error', [
                'error' => 'Invalid member',
            ]);
        }

        // Check to see if there has been a request
        $connect = $userConnectInviteRepo->findOneBy([
            'from'         => $connectUser,
            'to'           => $user,
            'connected_at' => null,
        ]);

        if (!$connect) {
            return $this->forward('App:Default:error', [
                'error' => 'There is no connection invite to accept',
            ]);
        }

        $connect->setStatus(false);
        $em->persist($connect);

        // Events will remove notification and update counts

        $em->flush();

        return new JsonResponse(['success' => true, 'message' => 'Invite has been ignored']);
    }

    /**
     * @Route("/user/membership/cancel", name="user_cancel_membership")
     *
     * @param Request $request
     */
    public function membershipCancelAction(Request $request)
    {
        $em   = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        /** @var Session $session */
        $session = $request->getSession();

        if (!$user->isSubscribed()) {
            throw $this->createNotFoundException('You are not subscribed to a membership to cancel');
        }

        /** @var UserSubscription|null $userSub - Find user subscription */
        $userSub = $em->getRepository('App:UserSubscription')->findOneBy([
            'user_info' => $user,
            'is_active' => 1,
        ]);
        if (!$userSub) {
            throw $this->createNotFoundException('Unable to find subscription - please contact support');
        }

        $reason = $request->get('reason');
        $reason = is_null($reason) ? $reason : trim($reason);

        $userCancelSub = new UserCancelSub();
        $userCancelSub->setUserInfo($user);
        $userCancelSub->setReason($reason);
        $em->persist($userCancelSub);

        try {
            $this->get('vocalizr_app.model.user_info')->unsubscribe($user, true, true);
            $session->getFlashBag()->add('success', 'Your membership has been cancelled');

            return $this->redirect($this->generateUrl('user_edit') . '#membership');

        } catch (UnsubscribeException $e) {
            $message = 'Unable to cancel subscription - please contact support: ' . $e->getMessage();
            $session->getFlashBag()->add('error', $message);

            throw $this->createNotFoundException('Unable to cancel subscription - please contact support: ' . $e->getMessage(), $e);
        }
    }

    /**
     * Generate connect button
     *
     * @Template()
     */
    public function connectButtonAction($userInfo, $userConnect = null, $type = 'small')
    {
        $em   = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        $className = 'btn-sm';
        if ($type == 'large') {
            $className = 'btn-lg';
        }

        return  $this->render('User/connectButton.html.twig', [
            'className'   => $className,
            'user'        => $userInfo,
            'userConnect' => $userConnect,
        ]);
    }

    /**
     * @Route("/user/membership/resume", name="user_membership_resume")
     * @Template()
     * @param ContainerInterface $container
     *
     * @return RedirectResponse
     */
    public function resumeMembershipAction(ContainerInterface $container)
    {
        $em   = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        if (!$user->isSubscribed()) {
            throw $this->createNotFoundException('You are not subscribed to a membership to cancel');
        }

        // Find user subscription
        /** @var UserSubscription $userSub */
        $userSub = $em->getRepository('App:UserSubscription')->findOneBy([
            'user_info' => $user,
            'is_active' => 1,
        ]);
        if (!$userSub) {
            throw $this->createNotFoundException('Unable to find subscription - please contact support');
        }

        $stripeApiKey = $container->getParameter('stripe_api_key');
        Stripe::setApiKey($stripeApiKey);

        try {
            $customer = Customer::retrieve($user->getStripeCustId());

            /** @var Subscription $membership */
            $membership = Subscription::retrieve($userSub->getStripeSubscrId());

            if ($membership->status === Subscription::STATUS_CANCELED) {
                throw new RestoreSubscriptionException('Subscription has already been cancelled and cannot be restored.');
            }

            $membership->plan                   = $membership->plan->id;
            $membership['cancel_at_period_end'] = false;

            $membership->save();

            $this->get('session')->getFlashBag()->add('notice', 'Your PRO membership subscription has been resumed.');
        } catch (RestoreSubscriptionException $exception) {
            $this->get('session')->getFlashBag()->add('error', $exception->getMessage());
        } catch (\Exception $exception) {
            $this->get('session')->getFlashBag()->add('error', 'Something went wrong during resuming your subscription. Please contact support');
        } catch (\Error $exception) {
            $this->get('session')->getFlashBag()->add('error', 'Something went wrong during resuming your subscription. Please contact support');
        }

        return $this->redirect($this->generateUrl('user_edit') . '#membership');
    }

    /**
     * @Route("/user/membership/dontCancel", name="user_membership_dont_cancel")
     * @Template()
     *
     * @param ContainerInterface $container
     *
     * @return array|Response
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function dontCancelMembershipModalAction(ContainerInterface $container)
    {
        $em   = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        if (!$user->isSubscribed()) {
            return $this->forward('App:Default:error', [
                'error' => 'You are not subscribed to a membership to cancel',
            ]);
        }

        // Find user subscription
        $userSub = $em->getRepository('App:UserSubscription')->findOneBy([
            'user_info' => $user,
            'is_active' => 1,
        ]);
        if (!$userSub) {
            return $this->forward('App:Default:error', [
                'error' => 'Unable to find subscription - please contact support',
            ]);
        }

        $stripeApiKey = $container->getParameter('stripe_api_key');
        Stripe::setApiKey($stripeApiKey);

        $customer   = Customer::retrieve($user->getStripeCustId());
        $membership = Subscription::retrieve($userSub->getStripeSubscrId());

        return [
            'customer'   => $customer,
            'membership' => $membership,
        ];
    }

    /**
     * @Route("/user/membership/cancel/confirm", name="user_cancel_confirm")
     * @Template()
     * @param ContainerInterface $container
     * @return array|Response
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function cancelModalAction(ContainerInterface $container)
    {
        $em   = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        if (!$user->isSubscribed()) {
            return $this->forward('App:Default:error', [
                'error' => 'You are not subscribed to a membership to cancel',
            ]);
        }

        // Find user subscription
        $userSub = $em->getRepository('App:UserSubscription')->findOneBy([
            'user_info' => $user,
            'is_active' => 1,
        ]);
        if (!$userSub) {
            return $this->forward('App:Default:error', [
                'error' => 'Unable to find subscription - please contact support',
            ]);
        }

        $membership = false;
        $customer   = false;
        if ($userSub->getStripeSubscrId()) {
            $stripeApiKey = $container->getParameter('stripe_api_key');
            Stripe::setApiKey($stripeApiKey);

            $customer   = Customer::retrieve($user->getStripeCustId());
            $membership = Subscription::retrieve($userSub->getStripeSubscrId());
        }

        return [
            'customer'   => $customer,
            'membership' => $membership,
        ];
    }

    /**
     * @Route("/user/engine", name="user_engine")
     * @Template()
     *
     * @param Request $request
     *
     * @return array
     */
    public function engineAction(Request $request)
    {
        $em   = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        // Get orders for user
        $q = $em->getRepository('App:EngineOrder')->createQueryBuilder('eo')
                ->select('eo, ep')
                ->innerJoin('eo.engine_product', 'ep')
                ->where('eo.user_info = :user')
                ->orderBy('eo.created_at', 'DESC')
                ->setParameter('user', $user);
        $orders = $q->getQuery()->execute();

        if ($request->get('success')) {
            $request->query->set('notice', 'Your order has been placed. A Vocalizr Engineer will be in contact soon with any questions.');
        }

        return [
            'orders' => $orders,
        ];
    }

    /**
     * @Route("/user/edit/save-video", name="user_edit_save_video")
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function saveUserVideoAction(Request $request)
    {
        $link = $request->get('video_link');

        /** @var UserVideoModel $videoModel */
        $videoModel = $this->get('vocalizr_app.model.user_video');

        $video = $videoModel->createUserVideo($link, $this->getUser());

        if (is_null($video)) {
            $this->get('session')->setFlash('error', 'Incorrect link');
        } else {
            $this->get('session')->setFlash('notice', 'Video was successfully added');
        }

        return $this->redirect($this->generateUrl('user_edit') . $request->get('return_tab', '#video'));
    }

    /**
     * @Route("/u-profile/get-user-video", name="user_get_user_video")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getUserVideosAction(Request $request)
    {
        $id     = $request->get('id');
        $offset = $request->get('offset');
        $limit  = $request->get('limit');

        $userInfo = $this->get('vocalizr_app.model.user_info')->getObject($id);

        if (is_null($userInfo)) {
            throw new NotFoundHttpException();
        }

        $userVideos = $this->get('vocalizr_app.model.user_video')->getUserVideos($userInfo, $offset, $limit);

        if (count($userVideos) > 0) {
            if ($request->get('edit')) {
                $html = $this->renderView('User:edit_videos.html.twig', [
                    'userVideos' => $userVideos,
                ]);
            } else {
                $html = $this->renderView('User:view_videos.html.twig', [
                    'userVideos' => $userVideos,
                ]);
            }
        } else {
            $html = '<h3>There is no more videos yet</h3>';
        }

        return new JsonResponse([
            'count' => count($userVideos),
            'html'  => $html,
        ]);
    }

    /**
     * @Route("/user/edit/remove-user-video/{id}", name="user_edit_remove_video")
     *
     * @param $id
     *
     * @return RedirectResponse
     */
    public function removeUserVideoAction($id)
    {
        $userInfo = $this->getUser();

        /** @var UserVideoModel $videoModel */
        $videoModel = $this->get('vocalizr_app.model.user_video');

        $video = $videoModel->getObject($id);

        if (is_null($userInfo)) {
            throw new NotFoundHttpException();
        }

        if ($userInfo != $video->getUserInfo()) {
            $this->get('session')->setFlash('error', 'Something wrong');
        } else {
            $videoModel->removeObject($video);
            $this->get('session')->setFlash('notice', 'Video was successfully removed');
        }

        return $this->redirect($this->generateUrl('user_edit') . '#video');
    }

    /**
     * @Route("/user/edit/sort-user-video", name="user_edit_sort_video")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function sortUserVideoAction(Request $request)
    {
        $data = $request->get('data');

        /** @var UserVideoModel $videoModel */
        $videoModel = $this->get('vocalizr_app.model.user_video');

        $videoModel->sortVideo(json_decode($data));

        return new JsonResponse(['success' => true]);
    }

    /**
     * @Route("user/edit/update-spotify-id/", name="user_edit_update_spotify_user_id")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function updateUserSpotifyIdAction(Request $request)
    {
        /** @var UserInfo $user */
        $user = $this->getUser();

        $id = $request->get('spotifyId');

        if (is_null($user) || is_null($id)) {
            throw new AccessDeniedException();
        }

        if ($id === '') {
            $this->get('vocalizr_app.model.user_spotify_playlist')->removeAllUserPlaylists($user);
            $user->setUserSpotifyId(null);
            $this->get('vocalizr_app.model.user_info')->updateObject($user);
            return new JsonResponse(['success' => true]);
        }

        $user->setUserSpotifyId($id);

        $this->get('vocalizr_app.model.user_info')->updateObject($user);

        return new JsonResponse(['success' => true]);
    }

    /**
     * @Route("user/edit/add-spotify-playlist/", name="user_edit_add_spotify_playlist")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function addUserSpotifyPlaylistAction(Request $request)
    {
        $id = $request->get('id');

        $offset = $request->get('offset', 0);
        $edit   = $request->get('edit', 0);

        /** @var UserInfo $user */
        $user = $this->get('vocalizr_app.model.user_info')->getObject($id);

        if (is_null($user)) {
            throw new AccessDeniedException();
        }

        $link = $request->get('link');

        /** @var UserSpotifyPlaylistModel $playlistModel */
        $playlistModel = $this->get('vocalizr_app.model.user_spotify_playlist');

        if (!is_null($link)) {
            try {
                $playlist = $playlistModel->createSpotifyPlaylist($user, $link);
            } catch (CreateSpotifyPlaylistException $exception) {
                return new JsonResponse([
                    'success' => false,
                    'error'   => 'Incorrect link: ' . $exception->getMessage(),
                ]);
            }
        }

        $userSpotifyPlaylists = $playlistModel->getSpotifyPlaylists($user, 4, $offset);

        $html = $this->renderView('User:user_spotify_playlists.html.twig', [
            'userSpotifyPlaylists' => $userSpotifyPlaylists,
            'edit'                 => $edit,
            'user'                 => $user,
        ]);

        return new JsonResponse(['success' => true, 'html' => $html, 'count' => count($userSpotifyPlaylists)]);
    }

    /**
     * @Route("user/edit/remove-spotify-playlist/{id}", name="user_edit_remove_spotify_playlist")
     *
     * @param int $id
     *
     * @return RedirectResponse
     */
    public function removeUserSpotifyPlaylistAction($id)
    {
        $userInfo = $this->getUser();

        /** @var UserSpotifyPlaylistModel $playlistModel */
        $playlistModel = $this->get('vocalizr_app.model.user_spotify_playlist');

        $playlist = $playlistModel->getObject($id);

        if (is_null($userInfo)) {
            throw new NotFoundHttpException();
        }

        if ($userInfo != $playlist->getUserInfo()) {
            $this->get('session')->setFlash('error', 'Something wrong');
        } else {
            $playlistModel->removeObject($playlist);
            $this->get('session')->setFlash('notice', 'Spotify playlist was successfully removed');
        }

        return $this->redirect($this->generateUrl('user_edit') . '#spotify');
    }

    /**
     * @Route("user/reviews/get-more", name="user_view_load_more_review")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getMoreUserReviewAction(Request $request)
    {
        $id   = $request->get('id');
        $type = $request->get('type');
        $page = $request->get('page');
        /** @var UserInfo $user */
        $user = $this->get('vocalizr_app.model.user_info')->getObject($id);

        if (!$user || !$page || !in_array($type, UserReview::$reviewTypes)) {
            throw new BadRequestHttpException('Bad request.');
        }

        $reviewsQuery = $this->getDoctrine()->getRepository('App:UserReview')->getUserReviewsByTypeQb($user, $type);

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $reviewsQuery,
            $page,
            10
        );

        $html = $this->renderView('User:view_reviews.html.twig', [
            'reviews' => $pagination,
        ]);

        return new JsonResponse(['html' => $html, 'hide' => ($pagination->getTotalItemCount() <= $page * 10), 'success' => true]);
    }

    /**
     * @Route("user/restriction-check", name="user_restriction_check")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function userRestrictionCheckAction(Request $request)
    {
        $service = $this->get('vocalizr_app.user_restriction');

        $data   = [];
        $can    = false;
        $status = 200;

        switch ($request->get('type')) {
            case 'bid':
                $can = $service->canBid();
                break;
            default:
                $data['error'] = 'Wrong type specified';
                $status        = 400;

        }

        $data['can'] = $can;

        return new JsonResponse($data, $status);
    }

    /**
     * PARTIALS
     * ======================================
     */

    /**
     * Partial
     * This partial will list projects that need to be accepted or declined by user
     *
     * @Template()
     */
    public function _projectsAwardedNeedResponseAction()
    {
        $user            = $this->getUser();
        $em              = $this->getDoctrine()->getManager();
        $projectsAwarded = $em->getRepository('App:ProjectBid')
                ->getProjectsAwardedNeedAction($user->getId());

        return ['projectsAwarded' => $projectsAwarded];
    }
}
