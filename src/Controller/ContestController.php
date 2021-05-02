<?php

namespace App\Controller;

//use JMS\SecurityExtraBundle\Annotation\Secure;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Parser;
use App\Entity\EntryVote;
use App\Entity\Project;
use App\Entity\ProjectAudio;
use App\Entity\ProjectBid;
use App\Entity\ProjectEscrow;
use App\Entity\SubscriptionPlan;
use App\Entity\UserInfo;
use App\Entity\UserWalletTransaction;
use App\Event\JustCreatedEvent;
use App\Form\Type\LocationType;
use App\Form\Type\NewProjectContestType;
use App\Form\Type\ProjectBidType;
use App\Form\Type\ProjectLyricType;
use App\Form\Type\ProjectSearchType;
use App\Form\Type\PublishType;
use App\Repository\SubscriptionPlanRepository;

class ContestController extends AbstractController
{
    /**
     * @Route("/contests_comingsoon/{filter}", defaults={"filter" = "latest"}, name="contests")
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $em          = $this->getDoctrine()->getManager();
        $projectRepo = $em->getRepository('App:Project');

        $ymlParser  = new Parser();
        $file       = $this->getParameter('kernel.project_dir') . '/config/packages/project.yml';
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

        $q = $projectRepo->createQueryBuilder('p');
        $q->select('p, pa, ui');
        $q->innerJoin('p.user_info', 'ui');
        $q->leftJoin('p.project_audio', 'pa', 'WITH', "pa.flag = '" . \App\Entity\ProjectAudio::FLAG_FEATURED . "'");
        $q->andWhere('p.is_active = true');
        $q->andWhere('p.bids_due >= :bidsDue');
        $q->andWhere('p.publish_type = :publishType');
        $q->andWhere('p.project_type = :projectType');
        $q->andWhere('p.employee_user_info is null');

        $params = [
            ':publishType' => Project::PUBLISH_PUBLIC,
            ':projectType' => Project::PROJECT_TYPE_CONTEST,
            ':bidsDue'     => $date,
        ];

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

        $form->handleRequest($request);

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

                if (count($data['vocal_characteristic']) > 0) {
                    $vocalIds = [];
                    foreach ($data['vocal_characteristic'] as $vocal) {
                        $vocalIds[] = $vocal->getId();
                    }
                    $q->innerJoin('p.vocalCharacteristics', 'vc');
                    $q->andWhere($q->expr()->in('vc.id', $vocalIds));
                }

                if (count($data['vocal_style']) > 0) {
                    $vocalIds = [];
                    foreach ($data['vocal_style'] as $vocal) {
                        $vocalIds[] = $vocal->getId();
                    }
                    $q->innerJoin('p.vocalStyles', 'vs');
                    $q->andWhere($q->expr()->in('vs.id', $vocalIds));
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
            }
        }

        if (isset($params) && count($params) > 0) {
            $q->setParameters($params);
        }

        $query = $q->getQuery();

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $this->get('request')->query->get('page', 1)/*page number*/,
            20// limit per page
        );

        return [
            'form'       => $form->createView(),
            'pagination' => $pagination,
            'filter'     => $filter,
        ];
    }

    /**
     * @Route("/new/contest/{uuid}", name="contest_new", defaults={"uuid" = ""})
     * @Template()
     */
    public function newAction(Request $request, $uuid)
    {
        $user = $this->getUser();
        $em   = $this->getDoctrine()->getManager();

        $projectRepo      = $em->getRepository('App:Project');
        $projectAudioRepo = $em->getRepository('App:ProjectAudio');

        $defaultProjectAudio = false;
        $project             = new Project();

        if (!empty($uuid)) {
            $project = $projectRepo->getProjectByUuid($uuid);

            if (!$project) {
                throw $this->createNotFoundException('Invalid contest');
            }

            if ($project->getProjectType() == Project::PROJECT_TYPE_PAID) {
                return $this->redirect($this->generateUrl('project_new', ['uuid' => $project->getUuid()]));
            }

            // Check if logged in user created gig
            if ($project->getUserInfo()->getId() != $user->getId()) {
                throw $this->createNotFoundException('Permission denied');
            }

            // If gig has been awarded, redirect to studio
            if ($project->getProjectBid()) {
                return $this->redirect($this->generateUrl('project_studio', ['uuid' => $project->getUuid()]));
            }

            // Get project featured audio
            $defaultProjectAudio = $projectAudioRepo->findOneBy([
                'project' => $project->getId(),
                'flag'    => ProjectAudio::FLAG_FEATURED,
            ]);
        }

        // Edit project form
        $english = $em->getRepository('App:Language')
                    ->findOneByTitle('English');
        // Get budget options
        $ymlParser  = new Parser();
        $file       = $this->getParameter('kernel.project_dir') . '/config/packages/project.yml';
        $projectYml = $ymlParser->parse(file_get_contents($file));

        $form         = $this->createForm(NewProjectContestType::class, $project, [
                                            'english' => $english,
                                            'budget' => $projectYml['contest_budget']

            ]);
        $locationForm = $this->createForm(LocationType::class);

        /**
         * Handle owner saving functions
         * Before saving, check permission
         */
        if ($request->isMethod('POST')) {
            $updatedAt = $project->getUpdatedAt();

            $form->handleRequest($request);
            $locationForm->handleRequest($request);

            if ($request->get('location')) {
                if (!$locationForm->get('city')->getData()) {
                    $locationForm->get('city')->addError(new FormError('Invalid city'));
                }
            }

            if ($form->get('royalty')->getData() > 0 &&
                    !$form->get('royalty_mechanical')->getData() &&
                    !$form->get('royalty_performance')->getData()) {
                $form->get('royalty')->addError(new FormError('Select royalty type'));
            }

            if ($form->get('royalty')->getData() == 0 &&
                    ($form->get('royalty_mechanical')->getData() ||
                    $form->get('royalty_performance')->getData())) {
                $form->get('royalty')->addError(new FormError('Select royalty amount'));
            }

            // Get budget and set correct fields
            if ($form->get('budget_from')->getData() == 0) {
                $form->get('budget_from')->addError(new FormError('Min amount is $150'));
            }

            // If contest and no audio, throw error
            if ($form->isValid() && $locationForm->isValid() && ($project->getId() || $request->get('audio_file'))) {
                $project->setUserInfo($user);

                $project->setProjectType(Project::PROJECT_TYPE_CONTEST);

                // Get budget and set correct fields

                $project->setBudgetTo(0);

                // Remove contact details
                $project->cleanDescription();

                if ($request->get('location')) {
                    $values = $locationForm->getData();
                    $project->setCity($values['city']);
                    $project->setState($values['state']);
                    $project->setCountry($values['country']);
                    $project->setLocationLat($values['location_lat']);
                    $project->setLocationLng($values['location_lng']);
                }

                $em->persist($project);
                $em->flush();

                // Attempt to save file
                if ($request->get('audio_file')) {
                    $projectAudio = $projectAudioRepo->saveUploadedFile(
                        $project->getId(),
                        $user->getId(),
                        $request->get('audio_title'),
                        $request->get('audio_file'),
                        ProjectAudio::FLAG_FEATURED
                    );

                    if (!$projectAudio) {
                        $this->get('session')->getFlashBag()->add('error', 'There was a issue with your uploaded audio. Please try again');
                        return $this->redirect($this->generateUrl('contest_new', ['uuid' => $project->getUuid()]));
                    }

                    if ($defaultProjectAudio) {
                        $em->remove($defaultProjectAudio);
                    }
                    $em->flush();

                    $defaultProjectAudio = $projectAudio;
                }

                if ($request->get('save')) {
                    $this->get('session')->getFlashBag()->add('notice', 'Contest has been saved');
                    return $this->redirect($this->generateUrl('contest_new', ['uuid' => $project->getUuid()]));
                }

                if ($request->get('next')) {
                    return $this->redirect($this->generateUrl('contest_new_publish', ['uuid' => $project->getUuid()]));
                }
            } elseif (!$project->getId() && !$request->get('audio_file')) {
                $request->query->set('error', 'Audio file is required');
            } else {
                $request->query->set('error', 'Please fix the error(s) below');
            }
        } else {
            if (!$project->getId()) {
                $form->get('budget_from')->setData(0);
            }
        }

        return $this->render('Contest/new.html.twig', [
            'project'             => $project,
            'defaultProjectAudio' => $defaultProjectAudio,
            'form'                => $form->createView(),
            'locationForm'        => $locationForm->createView(),
        ]);
    }

//     * @Secure(roles="ROLE_USER")
    /**
     * Publish newly created project
     * @IsGranted("ROLE_USER")
     *
     * @Route("/new/contest/{uuid}/publish", name="contest_new_publish")
     * @Template
     *
     * @param Request $request
     * @param string  $uuid
     */
    public function publishAction(Request $request, $uuid)
    {
        /** @var UserInfo $user */
        $user = $this->getUser();
        $em   = $this->getDoctrine()->getManager();

        $projectRepo = $em->getRepository('App:Project');

        /** @var Project $project */
        $project = $projectRepo->getProjectByUuid($uuid);

        if (!$project) {
            throw $this->createNotFoundException('Invalid gig');
        }

        if ($project->getProjectType() == Project::PROJECT_TYPE_PAID) {
            return $this->redirect($this->generateUrl('project_new', ['uuid' => $project->getUuid()]));
        }

        // If project is published already
        if ($project->getPublishedAt()) {
            return $this->redirect($this->generateUrl('contest_view', ['uuid' => $project->getUuid()]));
        } elseif ($project->getPaymentStatus() !== Project::PAYMENT_STATUS_PENDING) {
            return $this->redirect($this->generateUrl('contest_publish_confirm', ['uuid' => $project->getUuid()]));
        }

        // Check if logged in user created gig
        if ($project->getUserInfo()->getId() != $user->getId()) {
            throw $this->createNotFoundException('Permission denied');
        }

        // If gig has been awarded, redirect to studio
        if ($project->getProjectBid()) {
            return $this->redirect($this->generateUrl('project_studio', ['uuid' => $project->getUuid()]));
        }

        $form = $this->createForm(new PublishType([], []), $project);

        // If post method, we are saving options form
        if ($request->getMethod() == 'POST') {
            $form->bind($request);
            if ($form->isValid()) {
                if (!$form->get('publish_type')->getData()) {
                    $project->setPublishType(Project::PUBLISH_PUBLIC);
                }

                $calculator = $this->get('vocalizr_app.project_price_calculator');

                if (!$calculator->getProjectTotalPrice($user->isSubscribed() ? 'PRO' : 'FREE', $project)) {
                    $project->setPaymentStatus(Project::PAYMENT_STATUS_PAID);
                }

                $em->flush();

                return new JsonResponse(['success' => true]);
            } else {
                return new JsonResponse(['errors' => $form->getErrorsAsString()]);
            }
        }

        /** @var SubscriptionPlanRepository $planRepo */
        $planRepo = $em->getRepository(SubscriptionPlan::class);

        $subscriptionPlan   = $planRepo->getActiveSubscription($user->getId());
        $prices             = $planRepo->getFeaturePrices();

        return [
            'paypal'           => $this->get('service.paypal'),
            'project'          => $project,
            'form'             => $form->createView(),
            'subscriptionPlan' => $subscriptionPlan,
            'prices'           => $prices,
        ];
    }

//     * @Secure(roles="ROLE_USER")
    /**
     * Confirm the publishing of a project
     * @IsGranted("ROLE_USER")
     *
     * @Route("/new/contest/{uuid}/publish/confirm", name="contest_publish_confirm")
     *
     * @param Request $request
     * @param string  $uuid
     */
    public function publishConfirmAction(Request $request, $uuid)
    {
        /** @var UserInfo $user */
        $user = $this->getUser();
        $em   = $this->getDoctrine()->getManager();

        $projectRepo = $em->getRepository('App:Project');

        /** @var Project $project */
        $project = $projectRepo->getProjectByUuid($uuid);

        if (!$project) {
            throw $this->createNotFoundException('Invalid gig');
        }

        if ($project->getProjectType() == Project::PROJECT_TYPE_PAID) {
            return $this->redirect($this->generateUrl('project_publish_confirm', ['uuid' => $project->getUuid()]));
        }

        // Check if logged in user created gig
        if ($project->getUserInfo()->getId() != $user->getId()) {
            throw $this->createNotFoundException('Permission denied');
        }

        if ($project->getPublishedAt()) {
            return $this->redirect($this->generateUrl('contest_view', ['uuid' => $project->getUuid()]));
        }

        // If gig has been awarded, redirect to studio
        if ($project->getProjectBid()) {
            return $this->redirect($this->generateUrl('project_studio', ['uuid' => $project->getUuid()]));
        }

        if ($project->getPaymentStatus() === Project::PAYMENT_STATUS_PENDING) {
            $this->get('session')->getFlashBag()->add('error', 'Please wait a minute while we receive payment for a project.');
            return $this->redirect($this->generateUrl('contest_new_publish', ['uuid' => $uuid]));
        }

        if ($project->getBudgetFrom() > 0) {
            $project->setBudgetTo($project->getBudgetFrom());
        } else {
            $project->setBudgetFrom($project->getBudgetTo());
        }

        // Set bids due
        $dt = new \DateTime();
        $dt->modify('+14 days');
        $project->setBidsDue($dt);

        // If featured
        if ($project->getFeatured()) {
            $project->setFeaturedAt(new \DateTime());
        }

        if ($project->getToFavorites()) {
            $this->notifyFavorites($project);
        }

        $project->setIsActive(true);
        $project->setPublishedAt(new \DateTime());

        $em->flush();

        $this->get('session')->getFlashBag()->add('notice', 'Your contest has been successfully published');
        $this->get('session')->getFlashBag()->add('just_published', true);

        $this->get('event_dispatcher')->dispatch(
            'contest_or_gig.just_created',
            new JustCreatedEvent(JustCreatedEvent::TYPE_CONTEST, $user, $project)
        );

        return $this->redirect($this->generateUrl('contest_view', ['uuid' => $uuid]));
    }

    /**
     * Calculate project fees
     *
     * @param Project $project
     * @param array   $subscriptionPlan
     *
     * @return int
     */
    private function calculateProjectFee($project, $subscriptionPlan)
    {
        $projectPrice = 0;
        if ($project->getPublishType() == Project::PUBLISH_PRIVATE) {
            $projectPrice += $subscriptionPlan['project_private_fee'];
        }
        if ($project->getHighlight()) {
            $projectPrice += $subscriptionPlan['project_highlight_fee'];
        }
        if ($project->getFeatured()) {
            $projectPrice += $subscriptionPlan['project_feature_fee'];
        }
        /*
        if ($project->getShowInNews()) {
            $projectPrice += $subscriptionPlan['project_announce_fee'];
        }
         *
         */
        return $projectPrice;
    }

    /**
     * View contest
     *
     * @Route("/contest/{uuid}", name="contest_view")
     * @Template
     *
     * @param Request $request
     */
    public function viewAction(Request $request)
    {
        $uuid              = $request->get('uuid', false);
        $user              = $this->getUser();
        $em                = $this->getDoctrine()->getManager();
        $userAudioRepo     = $em->getRepository('App:UserAudio');
        $projectAudioRepo  = $em->getRepository('App:ProjectAudio');
        $displayIntroModal = false;
        $helper            = $this->get('service.helper');

        // Set in session if they have viewed this contest
        $request->getSession()->set('contest_' . $uuid, true);

        // Make sure project is valid
        if (!$uuid) {
            throw $this->createNotFoundException('Invalid contest');
        }

        $project = $em->getRepository('App:Project')
                    ->getProjectByUuid($uuid);

        if (!$project) {
            throw $this->createNotFoundException('Invalid contest');
        }

        if (!$project->getIsActive()) {
            throw $this->createNotFoundException('Contest no longer exists');
        }

        if ($project->getProjectType() == Project::PROJECT_TYPE_PAID) {
            return $this->redirect($this->generateUrl('project_view', ['uuid' => $project->getUuid()]), 301);
        }

        // Make sure contest is published
        if (!$project->getPublishedAt()) {
            if ($project->getUserInfo()->getId() == $user->getId()) {
                return $this->redirect($this->generateUrl('contest_new', ['uuid' => $project->getUuid()]));
            } else {
                return $this->redirect($this->generateUrl('dashboard'));
            }
        }

        // Get project featured audio
        $defaultProjectAudio = $projectAudioRepo->findOneBy([
            'project' => $project->getId(),
            'flag'    => ProjectAudio::FLAG_FEATURED,
        ]);

        // Project bid form
        $projectBid = new ProjectBid();
        $bidForm    = $this->createForm(new ProjectBidType(), $projectBid);

        // Get bid stats (total bids, avg bid amount)
        $bidStats                 = $em->getRepository('App:ProjectBid')->getBidStats($project->getId());
        $bidStats['avgBidAmount'] = $bidStats['avgBidAmount'] / 100;

        if (!$request->get('filter')) {
            if ($project->getSfs()) {
                $request->query->set('filter', 'votes');
            } else {
                $request->query->set('filter', 'default');
            }
            $request->query->set('dir', 'desc');
        }

        $filters = [
            'page'       => $request->get('page', 1),
            'maxResults' => 20,
        ];
        $shortlistBids = [];
        $hiddenBids    = [];
        if ($request->get('filter')) {
            $filters['orderBy'] = [$request->get('filter'), $request->get('dir', 'desc')];
        }
        if ($user && $user->getId() == $project->getUserInfo()->getId()) {
            $filters['owner'] = true;
            $shortlistBids    = $em->getRepository('App:ProjectBid')->getContestShortlistBids($project);
            $hiddenBids       = $em->getRepository('App:ProjectBid')->getContestHiddenBids($project);
        }

        $projectAwarded = $em->getRepository('App:ProjectBid')->isProjectAwarded($project->getId());

        $projectAudioDownload = false;
        if ($this->getUser()) {
            $this->markProjectRead($project);

            // See if project audio has been downloaded
            $projectAudioDownload = $em->getRepository('App:ProjectAudioDownload')->
                    findOneBy(['project_audio' => $defaultProjectAudio, 'user_info' => $user]);
        }

        // if this the project owner get the message threads for this project
        $activeThreads = null;
        if ($user == $project->getUserInfo()) {
            $threads = $em->getRepository('App:MessageThread')
                        ->findThreadsForUser($user, $project);
            foreach ($threads as $thread) {
                $activeThreads[$thread->getBidder()->getId()] = $thread->getUuid();
            }
        }

        $userBid          = null;
        $subscriptionPlan = null;
        if ($user) {
            $userBid = $em->getRepository('App:ProjectBid')->findOneBy([
                'user_info' => $user->getId(),
                'project'   => $project->getId(),
            ]);

            if ($userBid) {
                $subscriptionPlan['free'] = $em->getRepository('App:SubscriptionPlan')->findOneBy(['static_key' => 'FREE']);
                $subscriptionPlan['pro']  = $em->getRepository('App:SubscriptionPlan')->findOneBy(['static_key' => 'PRO']);
            }
        }

        $entry    = $request->get('entry');
        $entryBid = false;
        if ($entry) {
            // Find bid via entry uid
            $entryBid = $em->getRepository('App:ProjectBid')->getContestBidOne($entry, $project);
        }

        $bidVotes = [];
        if ($project->getSfs()) {
            // Get votes from cookies
            $cookieKey   = 'votes_' . $uuid;
            $cookie      = $_COOKIE;
            $cookieVotes = [];
            if (isset($cookie[$cookieKey])) {
                $cookieVotes = json_decode($cookie[$cookieKey], true);
            }

            if ($cookieVotes) {
                $bidVotes = array_merge($bidVotes, $cookieVotes);
            }

            $ip      = $this->container->get('request')->getClientIp();
            $browser = $_SERVER['HTTP_USER_AGENT'];

            $ipRange  = explode('.', $ip);
            $ipSearch = $ipRange[0] . '.' . $ipRange[1] . '.' . $ipRange[2];
            $ipSearch = $ipRange[0] . '.' . $ipRange[1];

            // get vote entries
            $qb = $em->getRepository('App:EntryVote')
                    ->createQueryBuilder('ev')
                    ->select('ev, pb')
                    ->innerJoin('ev.project_bid', 'pb')
                    ->where('(pb.project = :project AND ev.ip_addr LIKE :ip)');
            $params = [
                'ip'      => $ipSearch . '%',
                'project' => $project,
            ];
            if ($user) {
                $qb->orWhere('pb.project = :project AND  ev.user_info = :user');
                $params['user'] = $user;
            }
            $qb->setParameters($params);
            $entryVotes = $qb->getQuery()->execute();
            if ($entryVotes) {
                foreach ($entryVotes as $ev) {
                    $bidVotes[$ev->getProjectBid()->getUuid()] = true;
                }
            }
        }

        return [
            'project'              => $project,
            'projectAwarded'       => $projectAwarded,
            'defaultProjectAudio'  => $defaultProjectAudio,
            'bidStats'             => $bidStats,
            'bidForm'              => $bidForm->createView(),
            'activeThreads'        => $activeThreads,
            'projectAudioDownload' => $projectAudioDownload,
            'shortlistBids'        => $shortlistBids,
            'hiddenBids'           => $hiddenBids,
            'userBid'              => $userBid,
            'bidVotes'             => $bidVotes,
            'entryBid'             => $entryBid,
            'subscriptionPlan'     => $subscriptionPlan,
        ];
    }

    /**
     * Bids view
     *
     * @Route("/contest/{uuid}/bids/{page}/{filter}/{dir}", defaults={"page" = "1"}, name="contest_view_bids")
     * @Template
     *
     * @param Request $request
     */
    public function bidsAction($uuid, $page, $filter, $dir)
    {
        $request = $this->getRequest();
        $user    = $this->getUser();
        $em      = $this->getDoctrine()->getManager();

        if (!$uuid) {
            $uuid = $request->get('uuid', false);
            $page = $request->get('page');
        }

        $project = $em->getRepository('App:Project')
                    ->getProjectByUuid($uuid);

        if (!$project) {
            throw $this->createNotFoundException('Invalid contest');
        }

        if (!$request->get('filter')) {
            if ($project->getSfs()) {
                $request->query->set('filter', 'votes');
            } else {
                $request->query->set('filter', 'default');
            }
            $request->query->set('dir', 'desc');
        }

        $filters = [
            'page'       => $request->get('page', 1),
            'maxResults' => 20,
        ];
        if ($request->get('filter')) {
            $filters['orderBy'] = [$filter, $dir];
        }
        if ($user && $user->getId() == $project->getUserInfo()->getId()) {
            $filters['owner'] = true;
        }

        $bids      = $em->getRepository('App:ProjectBid')->getContestBids($project->getId(), $filters);
        $totalBids = $em->getRepository('App:ProjectBid')->getContestTotalBids($project->getId(), $filters);

        $maxPage = ceil($totalBids / $filters['maxResults']);

        $bidVotes = [];
        if ($project->getSfs()) {
            // Get votes from cookies
            $cookieKey   = 'votes_' . $uuid;
            $cookie      = $_COOKIE;
            $cookieVotes = [];
            if (isset($cookie[$cookieKey])) {
                $cookieVotes = json_decode($cookie[$cookieKey], true);
            }

            if ($cookieVotes) {
                $bidVotes = array_merge($bidVotes, $cookieVotes);
            }

            $ip       = $_SERVER['REMOTE_ADDR'];
            $browser  = $_SERVER['HTTP_USER_AGENT'];
            $ipRange  = explode('.', $ip);
            $ipSearch = $ipRange[0] . '.' . $ipRange[1] . '.' . $ipRange[2];
            $ipSearch = $ipRange[0] . '.' . $ipRange[1];

            // get vote entries
            $qb = $em->getRepository('App:EntryVote')
                    ->createQueryBuilder('ev')
                    ->select('ev, pb')
                    ->innerJoin('ev.project_bid', 'pb')
                    ->where('(pb.project = :project AND ev.ip_addr LIKE :ip)');
            $params = [
                'ip'      => $ipSearch . '%',
                'project' => $project,
            ];
            if ($user) {
                $qb->orWhere('pb.project = :project AND  ev.user_info = :user');
                $params['user'] = $user;
            }
            $qb->setParameters($params);
            $entryVotes = $qb->getQuery()->execute();
            if ($entryVotes) {
                foreach ($entryVotes as $ev) {
                    $bidVotes[$ev->getProjectBid()->getUuid()] = true;
                }
            }
        }

        return $this->render('@VocalizrApp/Contest/bids.html.twig', [
            'project'   => $project,
            'bids'      => $bids,
            'totalBids' => $totalBids,
            'maxPage'   => $maxPage,
            'page'      => $page,
            'filter'    => $filter,
            'dir'       => $dir,
            'bidVotes'  => $bidVotes,
        ]);
    }

    /**
     * Owner Bids view
     *
     * @Route("/contest/{uuid}/obids/{page}/{filter}/{dir}", defaults={"page" = "1"}, name="contest_view_owner_bids")
     * @Template
     *
     * @param Request $request
     */
    public function ownerBidsAction($uuid, $page, $filter, $dir)
    {
        $request = $this->getRequest();
        $user    = $this->getUser();
        $em      = $this->getDoctrine()->getManager();

        if (!$uuid) {
            $uuid = $request->get('uuid', false);
            $page = $request->get('page');
        }

        $project = $em->getRepository('App:Project')
                    ->getProjectByUuid($uuid);

        if (!$project) {
            throw $this->createNotFoundException('Invalid contest');
        }

        if (!$request->get('filter')) {
            if ($project->getSfs()) {
                $request->query->set('filter', 'votes');
            } else {
                $request->query->set('filter', 'default');
            }
            $request->query->set('dir', 'desc');
        }

        $filters = [
            'page'       => $request->get('page', 1),
            'maxResults' => 20,
        ];
        if ($request->get('filter')) {
            $filters['orderBy'] = [$filter, $dir];
        }
        if ($user && $user->getId() == $project->getUserInfo()->getId()) {
            $filters['owner'] = true;
        }

        $bids      = $em->getRepository('App:ProjectBid')->getContestBids($project->getId(), $filters);
        $totalBids = $em->getRepository('App:ProjectBid')->getContestTotalBids($project->getId(), $filters);

        $maxPage = ceil($totalBids / $filters['maxResults']);

        $bidVotes = [];
        if ($project->getSfs()) {
            // Get votes from cookies
            $cookieKey   = 'votes_' . $uuid;
            $cookie      = $_COOKIE;
            $cookieVotes = [];
            if (isset($cookie[$cookieKey])) {
                $cookieVotes = json_decode($cookie[$cookieKey], true);
            }

            if ($cookieVotes) {
                $bidVotes = array_merge($bidVotes, $cookieVotes);
            }

            $ip       = $this->container->get('request')->getClientIp();
            $browser  = $_SERVER['HTTP_USER_AGENT'];
            $ipRange  = explode('.', $ip);
            $ipSearch = $ipRange[0] . '.' . $ipRange[1] . '.' . $ipRange[2];
            $ipSearch = $ipRange[0] . '.' . $ipRange[1];

            // get vote entries
            $qb = $em->getRepository('App:EntryVote')
                    ->createQueryBuilder('ev')
                    ->select('ev, pb')
                    ->innerJoin('ev.project_bid', 'pb')
                    ->where('(pb.project = :project AND ev.ip_addr LIKE :ip)');
            $params = [
                'ip'      => $ipSearch . '%',
                'project' => $project,
            ];
            if ($user) {
                $qb->orWhere('pb.project = :project AND  ev.user_info = :user');
                $params['user'] = $user;
            }
            $qb->setParameters($params);
            $entryVotes = $qb->getQuery()->execute();
            if ($entryVotes) {
                foreach ($entryVotes as $ev) {
                    $bidVotes[$ev->getProjectBid()->getUuid()] = true;
                }
            }
        }

        return [
            'project'   => $project,
            'bids'      => $bids,
            'totalBids' => $totalBids,
            'maxPage'   => $maxPage,
            'page'      => $page,
            'filter'    => $filter,
            'dir'       => $dir,
            'bidVotes'  => $bidVotes,
            'projectAwarded' => (bool)$project->getAwardedAt()
        ];
    }

//     * @Secure(roles="ROLE_USER")
    /**
     * Edit contest
     *
     * @IsGranted("ROLE_USER")
     *
     * @Route("/contest/{uuid}/edit", name="contest_edit")
     * @Template()
     */
    public function editAction(Request $request)
    {
        $this->request    = $request;
        $uuid             = $request->get('uuid', false);
        $user             = $this->user             = $this->getUser();
        $em               = $this->em               = $this->getDoctrine()->getManager();
        $projectAudioRepo = $em->getRepository('App:ProjectAudio');

        // Make sure project is valid
        if (!$uuid) {
            throw $this->createNotFoundException('Invalid contest');
        }

        $project = $this->project = $em->getRepository('App:Project')
                    ->getProjectByUuid($uuid);

        if (!$project) {
            throw $this->createNotFoundException('Invalid contest');
        }

        if ($project->getProjectType() == Project::PROJECT_TYPE_PAID) {
            return $this->redirect($this->generateUrl('project_edit', ['uuid' => $project->getUuid()]));
        }

        // Check if logged in user created gig
        if ($project->getUserInfo()->getId() != $user->getId()) {
            throw $this->createNotFoundException('Permission denied');
        }

        // If gig has been awarded, redirect to studio
        if ($project->getProjectBid()) {
            return $this->redirect($this->generateUrl('project_studio', ['uuid' => $project->getUuid()]));
        }

        // If gig has not been published yet
        if (!$project->getPublishedAt()) {
            return $this->redirect($this->generateUrl('contest_new', ['uuid' => $project->getUuid()]));
        }

        // Get project featured audio
        $defaultProjectAudio = $projectAudioRepo->findOneBy([
            'project' => $project->getId(),
            'flag'    => ProjectAudio::FLAG_FEATURED,
        ]);

        // Get user favorite vocalists count
        $returnCountResult = true;
        $favoriteCount     = $em->getRepository('App:UserInfo')
                ->getUserFavoritesForInviting($user->getId(), $project, $returnCountResult);

        // Edit project form
        $english = $em->getRepository('App:Language')
                    ->findOneByTitle('English');
        // Get budget options
        $ymlParser  = new Parser();
        $file       = $this->getParameter('kernel.project_dir') . '/config/packages/project.yml';
        $projectYml = $ymlParser->parse(file_get_contents($file));

        $form         = $this->createForm(new \App\Form\Type\EditProjectContestType($english), $project);
        $locationForm = $this->createForm(new LocationType());

        // Edit lyrics form
        $lyricForm = $this->createForm(new ProjectLyricType(), $project);

        /**
         * Handle owner saving functions
         * Before saving, check permission
         */
        if ($request->isMethod('POST') && $request->get('save')) {
            /**
             * Saving requirements form
             */
            if ($request->get('save') == 'requirements') {
                $updatedAt = $project->getUpdatedAt();

                $form->bind($request);
                $locationForm->bind($request);

                if ($request->get('location')) {
                    if (!$locationForm->get('city')->getData()) {
                        $locationForm->get('city')->addError(new FormError('Invalid city'));
                    }
                }

                if ($form->get('royalty')->getData() > 0 &&
                    !$form->get('royalty_mechanical')->getData() &&
                    !$form->get('royalty_performance')->getData()) {
                    $form->get('royalty')->addError(new FormError('Select royalty type'));
                }

                if ($form->get('royalty')->getData() == 0 &&
                    ($form->get('royalty_mechanical')->getData() ||
                        $form->get('royalty_performance')->getData())) {
                    $form->get('royalty')->addError(new FormError('Select royalty amount'));
                }

                if ($form->isValid() && $locationForm->isValid()) {

                    // If file exists, this means they have uploaded audio
                    if ($request->get('audio_file')) {
                        // Attempt to save file
                        $projectAudio = $projectAudioRepo
                                ->saveUploadedFile(
                                    $project->getId(),
                                    $user->getId(),
                                    $request->get('audio_title'),
                                    $request->get('audio_file'),
                                    ProjectAudio::FLAG_FEATURED
                                );

                        if (!$projectAudio) {
                            $this->get('session')->getFlashBag()->add('error', 'There was a issue with your uploaded audio. Please try again');
                            return $this->redirect($this->generateUrl('contest_edit', ['uuid' => $project->getUuid()]));
                        }

                        if ($defaultProjectAudio) {
                            // Remove any project audio downloads
                            $q = $em->getRepository('App:ProjectAudioDownload')
                                    ->createQueryBuilder('pad')
                                    ->delete()
                                    ->where('pad.project_audio = :projectAudio');
                            $q->setParameter('projectAudio', $defaultProjectAudio);
                            $query = $q->getQuery();
                            $query->execute();

                            $em->remove($defaultProjectAudio);
                        }
                        $em->flush();

                        $defaultProjectAudio = $projectAudio;
                    }

                    // Remove contact details
                    $project->cleanDescription();

                    if ($request->get('location')) {
                        $values = $locationForm->getData();
                        $project->setCity($values['city']);
                        $project->setState($values['state']);
                        $project->setCountry($values['country']);
                        $project->setLocationLat($values['location_lat']);
                        $project->setLocationLng($values['location_lng']);
                    }

                    $em->persist($project);
                    $em->flush();

                    $request->query->set('notice', 'Changes saved');
                } else {
                    $request->query->set('error', 'Please fix the error(s) below');
                }
            }
        } else {
            $form->get('lyrics_needed')->setData(1);
            if ($project->getLyrics()) {
                $form->get('lyrics_needed')->setData(0);
            }
        }

        $filters = [];
        if ($request->get('filter')) {
            $filters['orderBy'] = [$request->get('filter'), $request->get('dir', 'desc')];
        }
        // Get bids & bid stats (total bids, avg bid amount)
        $bids     = $em->getRepository('App:ProjectBid')->getProjectBids($project->getId(), $filters);
        $bidStats = $em->getRepository('App:ProjectBid')->getBidStats($project->getId());

        $projectAwarded = $em->getRepository('App:ProjectBid')->isProjectAwarded($project->getId());

        $projectContracts = $em->getRepository('App:ProjectContract')->findBy([
            'project' => $project->getId(),
        ], [
            'created_at' => 'DESC',
        ]);

        return [
            'form'                => $form->createView(),
            'locationForm'        => $locationForm->createView(),
            'lyricForm'           => $lyricForm->createView(),
            'project'             => $project,
            'projectAwarded'      => $projectAwarded,
            'projectContracts'    => $projectContracts,
            'defaultProjectAudio' => $defaultProjectAudio,
            'bidStats'            => $bidStats,
            'favoriteCount'       => $favoriteCount,
            'bids'                => $bids,
        ];
    }

    /**
     * Show the widget that has information about the projects current status
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param type                                      $uuid
     * @Template()
     */
    public function projectStatusWidgetAction(Request $request, $uuid)
    {
        $em     = $this->getDoctrine()->getManager();
        $user   = $this->getUser();
        $paypal = $this->get('service.paypal');

        // Make sure project is valid
        if (!$uuid) {
            throw $this->createNotFoundException('Invalid gig');
        }

        $project = $this->project = $em->getRepository('App:Project')
                    ->getProjectByUuid($uuid);

        if (!$project) {
            throw $this->createNotFoundException('Invalid gig');
        }

        $projectAwarded = $em->getRepository('App:ProjectBid')->isProjectAwarded($project->getId());

        // Get bids & bid stats (total bids, avg bid amount)
        $bids     = $em->getRepository('App:ProjectBid')->getProjectBids($project->getId());
        $bidStats = $em->getRepository('App:ProjectBid')->getBidStats($project->getId());

        $publishForm = $this->createFormBuilder($project)
            ->add('publish_type', 'choice', [
                'label'   => 'PUBLISHING OPTIONS',
                'choices' => [Project::PUBLISH_PUBLIC => ucwords(Project::PUBLISH_PUBLIC),
                    Project::PUBLISH_PRIVATE          => ucwords(Project::PUBLISH_PRIVATE), ],
                'expanded'          => true,
                'multiple'          => false,
                'preferred_choices' => [Project::PUBLISH_PUBLIC],
            ])
            ->add('to_favorites', null, [
                'label'    => 'NOTIFY YOUR FAVORITES',
                'required' => false,
            ])
            ->add('restrict_to_preferences', null, [
                'label'    => 'RESTRICT TO GIG PREFERENCES',
                'required' => false,
            ])
            ->getForm();

        if (!$project->getPublishType()) {
            $publishForm->get('publish_type')->setData(Project::PUBLISH_PUBLIC);
        }

        $userBid                = false;
        $userMatchesPreferences = [];
        $restrictBid            = false;

        // Check bid hasn't past, if not, do additional checks
        if ($project->getIsActive() && time() < $project->getBidsDue()->getTimestamp()) {
            if ($user) {
                // Check if project has restrictions
                $userMatchesPreferences = $this->userMeetProjectPreferences($project);
                if (count($userMatchesPreferences) > 0) {
                    $restrictBid = true;
                }

                // If bid isn't restricted and If user doesn't own gig, find out if they had bidded already
                if (!$restrictBid && $project->getUserInfo()->getId() != $user->getId()) {
                    $userBid = $em->getRepository('App:ProjectBid')->findOneBy([
                        'user_info' => $user->getId(),
                        'project'   => $project->getId(),
                    ]);
                }
            }
        } else {
            $userBid = true;
        }

        // get users default audio
        $defaultAudio = $em->getRepository('App:UserAudio')->findOneBy([
            'user_info'     => $this->getUser(),
            'default_audio' => true,
        ]);

        $projectBid = new ProjectBid();
        $bidForm    = $this->createForm(new ProjectBidType(), $projectBid);

        // If owner get downloads
        $audioDownloads = false;
        if ($user && ($project->isOwner($user) || $user->getIsAdmin())) {
            $defaultProjectAudio = $em->getRepository('App:ProjectAudio')->findOneBy([
                'project' => $project->getId(),
                'flag'    => ProjectAudio::FLAG_FEATURED,
            ]);
            $audioDownloads = $em->getRepository('App:ProjectAudioDownload')->findBy(['project_audio' => $defaultProjectAudio]);
        }

        $templateData = [
            'project'                => $project,
            'projectAwarded'         => $projectAwarded,
            'userBid'                => $userBid,
            'userMatchesPreferences' => $userMatchesPreferences,
            'restrictBid'            => $restrictBid,
            'publishForm'            => $publishForm->createView(),
            'bidStats'               => $bidStats,
            'fromPage'               => $request->get('fromPage'),
            'defaultAudio'           => $defaultAudio,
            'bidForm'                => $bidForm->createView(),
            'paypal'                 => $paypal,
            'audioDownloads'         => $audioDownloads,
        ];

        // if ajax request return json response
        if ($request->isXmlHttpRequest()) {
            $jsonResponse = [
                'success' => true,
                'html'    => $this->renderView(
                    'VocalizrAppBundle:Contest:projectStatusWidget.html.twig',
                    $templateData
                ),
            ];
            return new Response(json_encode($jsonResponse));
        }

        return $templateData;
    }

    //     * @Secure(roles="ROLE_USER")
    /**
     * @Route("/contest/{uuid}/savePref", name="contest_save_prefs")
     * @IsGranted("ROLE_USER")
     *
     * @param Request $request
     */
    public function savePrefsAction(Request $request, $uuid)
    {
        $em   = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        // Make sure project is valid
        if (!$uuid) {
            throw $this->createNotFoundException('Invalid gig');
        }

        $project = $this->project = $em->getRepository('App:Project')
                    ->getProjectByUuid($uuid);

        if (!$project) {
            throw $this->createNotFoundException('Invalid gig');
        }

        // Check if logged in user created gig
        if ($project->getUserInfo()->getId() != $user->getId()) {
            throw $this->createNotFoundException('Permission denied');
        }

        $publishForm = $this->createFormBuilder($project)
            ->add('publish_type', 'choice', [
                'label'   => 'PUBLISHING OPTIONS',
                'choices' => [Project::PUBLISH_PUBLIC => ucwords(Project::PUBLISH_PUBLIC),
                    Project::PUBLISH_PRIVATE          => ucwords(Project::PUBLISH_PRIVATE), ],
                'expanded' => true,
                'multiple' => false,
            ])
            ->add('to_favorites', null, [
                'label'    => 'NOTIFY YOUR FAVORITES',
                'required' => false,
            ])
            ->add('restrict_to_preferences', null, [
                'label'    => 'RESTRICT TO GIG PREFERENCES',
                'required' => false,
            ])
            ->getForm();

        if ($request->isMethod('POST')) {
            $publishForm->bind($request);

            if ($publishForm->isValid()) {
                if ($project->getPublishType() == Project::PUBLISH_PRIVATE) {
                    $project->setShowInNews(false);
                }

                $em->flush();

                $jsonResponse = ['success' => true];
            }
            $jsonResponse = ['success' => false];
        }

        return new Response(json_encode($jsonResponse));
    }


//     * @Secure(roles="ROLE_USER")
    /**
     * Publish the contest with the set preferences
     *
     * @Route("/contest/{uuid}/publish", name="contest_publish")
     * @IsGranted("ROLE_USER")
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function publishProjectAction(Request $request, $uuid)
    {
        $em   = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        // Make sure project is valid
        if (!$uuid) {
            throw $this->createNotFoundException('Invalid gig');
        }

        $project = $this->project = $em->getRepository('App:Project')
                    ->getProjectByUuid($uuid);

        if (!$project) {
            throw $this->createNotFoundException('Invalid gig');
        }

        // Check if logged in user created gig
        if ($project->getUserInfo()->getId() != $user->getId()) {
            throw $this->createNotFoundException('Permission denied');
        }

        $publishForm = $this->createFormBuilder($project)
            ->add('publish_type', 'choice', [
                'label'   => 'PUBLISHING OPTIONS',
                'choices' => [Project::PUBLISH_PUBLIC => ucwords(Project::PUBLISH_PUBLIC),
                    Project::PUBLISH_PRIVATE          => ucwords(Project::PUBLISH_PRIVATE), ],
                'expanded' => true,
                'multiple' => false,
                'data'     => Project::PUBLISH_PUBLIC,
            ])
            ->add('to_favorites', null, [
                'label'    => 'NOTIFY YOUR FAVORITES',
                'required' => false,
            ])
            ->add('restrict_to_preferences', null, [
                'label'    => 'RESTRICT TO GIG PREFERENCES',
                'required' => false,
            ])
            ->getForm();

        if ($request->isMethod('POST')) {
            $publishForm->bind($request);

            if ($publishForm->isValid()) {
                if ($project->getPublishType() == Project::PUBLISH_PRIVATE) {
                    $project->setShowInNews(false);
                }

                // Set bids due
                $dt = new \DateTime();
                $dt->modify('+13 days');
                $dt->modify('+23 hours');
                $dt->modify('+59 minutes');
                $project->setBidsDue($dt);

                if ($project->getToFavorites() === true) {
                    $this->notifyFavorites($project);
                }

                $project->setIsActive(true);
                $project->setPublishedAt(new \DateTime());

                $amount = $project->getBudgetTo() * 100;

                // Add to project escrow
                $pe = new ProjectEscrow();
                $pe->setFee(0);
                $pe->setAmount($amount);
                $pe->setUserInfo($user);
                $em->persist($pe);

                $project->setProjectEscrow($pe);

                // Create transactions in user wallet
                // Remove amount for project and put into escrow
                $uwt = new UserWalletTransaction();
                $uwt->setUserInfo($user);
                $uwt->setAmount('-' . $amount); // Minus amount
                $uwt->setCurrency($this->container->getParameter('default_currency'));
                $description = 'Escrow payment to contest {project}';
                $uwt->setDescription($description);
                $data = [
                    'projectTitle' => $project->getTitle(),
                    'projectUuid'  => $project->getUuid(),
                    'projectType'  => Project::PROJECT_TYPE_CONTEST,
                ];
                $uwt->setData(json_encode($data));
                $em->persist($uwt);

                $em->flush();

                return $this->forward(
                    'VocalizrAppBundle:Contest:projectStatusWidget',
                    [
                        'uuid'     => $project->getUuid(),
                        'fromPage' => $request->get('fromPage'), ]
                );
            }
            return new Response(json_encode(['success' => false, 'error' => $publishForm->getErrorsAsString()]));
        }

        return new Response(json_encode([]));
    }

//     * @Secure(roles="ROLE_USER")
    /**
     * @Route("/contest/{uuid}/bid", name="contest_bid")
     *
     * @param Request $request
     * @IsGranted("ROLE_USER")
     *
     * @return Response
     */
    public function bidAction(Request $request)
    {
        $uuid = $request->get('uuid', false);
        $user = $this->getUser();
        $em   = $this->getDoctrine()->getManager();

        // Make sure user is logged in
        $securityContext = $this->container->get('security.context');
        if (!$securityContext->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->forward('VocalizrAppBundle:Default:error', [
                'error' => 'You need to be logged in to place a bid',
            ]);
        }

        $project = $em->getRepository('App:Project')
                    ->getProjectByUuid($uuid);

        if (!$project) {
            return $this->forward('VocalizrAppBundle:Default:error', [
                'error' => 'Invalid contest',
            ]);
        }
        if ($project->getProRequired() && !$user->getIsCertified()) {
            return $this->forward('VocalizrAppBundle:Default:error', [
                'error' => 'This Job is locket to Certified Pro only.',
            ]);
        }

        if (!$this->get('vocalizr_app.user_restriction')->canBid()) {
            if ($request->isXmlHttpRequest()) {
                return $this->render('include/panel/bid_limit_panel.html.twig');
            } else {
                $request->getSession()->set('bid_modal', true);
                $redirectUrl = $request->headers->get('referrer', $this->generateUrl('project_view', ['uuid' => $uuid]));
                return $this->redirect($redirectUrl);
            }
        }

        $userBid                = false;
        $userMatchesPreferences = [];
        $restrictBid            = false;

        // Check bid hasn't past, if not, do additional checks
        if (time() < $project->getBidsDue()->getTimestamp()) {
            // Check if project has restrictions
            $userMatchesPreferences = $this->userMeetProjectPreferences($project);
            if (count($userMatchesPreferences) > 0) {
                $restrictBid = true;
            }

            // If bid isn't restricted and If user doesn't own gig, find out if they had bidded already
            if (!$restrictBid && $project->getUserInfo()->getId() != $user->getId()) {
                $userBid = $em->getRepository('App:ProjectBid')->findOneBy([
                    'user_info' => $user->getId(),
                    'project'   => $project->getId(),
                ]);
            }
        } else {
            // If time has past
            if (time() > $project->getBidsDue()->getTimestamp()) {
                return $this->forward('VocalizrAppBundle:Default:error', [
                    'error' => 'Sorry. Entries on this contest is now closed',
                ]);
            }
        }

        // If user has already bidded or time has past
        if ($userBid) {
            // If time has past
            if (time() > $project->getBidsDue()->getTimestamp()) {
                return $this->forward('VocalizrAppBundle:Default:error', [
                    'error' => 'Sorry. Entries on this contest is now closed',
                ]);
            }

            if ($project->getSfs()) {
                return $this->forward('VocalizrAppBundle:Default:error', [
                    'error' => 'You have already submitted an entry for this contest',
                ]);
            }
        }

        // If bid is restricted and user wasn't invited
        $projectInvite = $em->getRepository('App:ProjectInvite')
                ->findOneBy([
                    'project'   => $project->getId(),
                    'user_info' => $user->getId(),
                ]);
        if ($restrictBid && !$projectInvite) {
            return $this->forward('VocalizrAppBundle:Default:error', [
                'error' => 'Sorry. Entries on this contewst are restricted to members who meet the contest requirements.',
            ]);
        }

        $projectBid = new ProjectBid();
        if ($userBid) {
            $projectBid = $userBid;
        }

        // Project bid form
        $projectBid->setProject($project);
        $projectBid->setUserInfo($user);
        $projectBid->setAmount($project->getBudgetTo() * 100);

        // If they have uploaded / recorded a file
        if ($bidAudioFile = $request->get('audio_file')) {
            $projectBid->setPath($bidAudioFile);

            // Check if upload directory exists, if not create it
            if (!is_dir($projectBid->getUploadRootDir())) {
                mkdir($projectBid->getUploadRootDir(), 0777, true);
            }

            // Move file to new directory from tmp dir
            $tmpDir = $this->container->get('service.helper')->getUploadTmpDir();
            if (!rename($tmpDir . '/' . $bidAudioFile, $projectBid->getAbsolutePath())) {
                $uploadError = true;
            }
        } else {
            $this->get('session')->getFlashBag()->add('error', 'You must upload audio file with entry.');
            return $this->redirect($this->generateUrl('contest_view', ['uuid' => $project->getUuid()]));
        }

        // If there was an upload error, display error
        if (isset($uploadError)) {
            $this->get('session')->getFlashBag()->add('error', 'There was a problem while processing your entry. Please try again.');
            return $this->redirect($this->generateUrl('contest_view', ['uuid' => $project->getUuid()]));
        }

        $em->persist($projectBid);
        $em->flush();

        // Convert uploaded file to 128bit
        $helper = $this->container->get('service.helper');
        $cmd    = '--abr 112 ' . $projectBid->getAbsolutePath() . ' ' . $projectBid->getAbsolutePath();
        $helper->execLame($cmd);

        // Generate waveform
        $this->container->get('service.helper')->
                execSfCmd('vocalizr:generate-waveform --project_bid ' . $projectBid->getId());

        if ($userBid) {
            $this->get('session')->getFlashBag()->add('notice', 'Your entry has been updated');
        } else {
            $this->get('session')->getFlashBag()->add('notice', 'Your entry has been submitted');
        }

        return $this->redirect($this->generateUrl('contest_view', ['uuid' => $project->getUuid()]));
    }

//     * @Secure(roles="ROLE_USER")
    /**
     * @Route("/contest/{uuid}/bid/{bidUuid}", name="contest_award")
     * @Template
     * @IsGranted("ROLE_USER")
     *
     * @param Request $request
     * @param string  $uuid
     * @param string  $bidUuid
     */
    public function awardAction(Request $request)
    {
        $uuid    = $request->get('uuid', false);
        $bidUuid = $request->get('bidUuid', false);
        $user    = $this->getUser();
        $em      = $this->getDoctrine()->getManager();
        $paypal  = $this->get('service.paypal');

        // Make sure project is valid
        if (!$uuid) {
            throw $this->createNotFoundException('Invalid Contest');
        }

        $project = $em->getRepository('App:Project')
                    ->getProjectByUuid($uuid);

        if (!$project) {
            throw $this->createNotFoundException('Invalid Contest');
        }
        if ($project->getProjectType() != Project::PROJECT_TYPE_CONTEST) {
            throw $this->createNotFoundException('Invalid Contest');
        }

        // Make sure they are the owner of the project
        if ($project->getUserInfo()->getId() != $user->getId()) {
            throw $this->createNotFoundException('Permission Denied');
        }

        // Make sure project hasn't already been awarded
        if ($project->getProjectBid()) {
            throw $this->createNotFoundException('Contest has already been awarded');
        }
        if ($projectAwarded = $em->getRepository('App:ProjectBid')->isProjectAwarded($project->getId())) {
            throw $this->createNotFoundException('Contest has already been awarded');
        }

        // Get project bid they are wanting to award
        $projectBid = $em->getRepository('App:ProjectBid')->getProjectBidByUuid($bidUuid);
        if (!$projectBid) {
            throw $this->createNotFoundException('Cannot award contest to invalid member');
        }

        $form = $this->createFormBuilder($project)
                ->add('employer_name', null, [
                    'label'       => 'Sign Agreement',
                    'attr'        => ['class' => 'form-control'],
                    'constraints' => [
                        new \Symfony\Component\Validator\Constraints\NotBlank([
                            'message' => 'Required',
                        ]),
                    ],
                ])->getForm();

        if ($request->getMethod() == 'POST' && $request->get('award')) {
            $form->bind($request);

            if ($form->isValid()) {
                /** @var ProjectBid $projectBid */
                $projectBid = $projectBid[0];

                $projectBid->setFlag('A'); // Awarded flag
                $project->setProjectBid($projectBid);
                $project->setEmployeeUserInfo($projectBid->getUserInfo());
                $project->setAwardedAt(new \DateTime());
                $em->flush();

                // Update escrow payment with bid winner
                $escrow = $project->getProjectEscrow();
                $escrow->setProjectBid($projectBid);
                $em->persist($escrow);
                $em->flush();

                // Send email to bidder saying payment has been made and awarded
                $dispatcher = $this->get('hip_mandrill.dispatcher');

                $message = new \Hip\MandrillBundle\Message();
                $message->setSubject('Congratulations! Your entry won on the contest on: ' . $project->getTitle());
                $message->setFromEmail('noreply@vocalizr.com');
                $message->setFromName('Vocalizr');
                $message
                    ->setTrackOpens(true)
                    ->setTrackClicks(true);

                $message->addTo($projectBid->getUserInfo()->getEmail());
                $body = $this->container->get('templating')->render('VocalizrAppBundle:Mail:contestAward.html.twig', [
                    'userInfo'   => $projectBid->getUserInfo(),
                    'project'    => $project,
                    'projectBid' => $projectBid,
                    'amount'     => $projectBid->getAmount() / 100,
                ]);
                $message->addGlobalMergeVar('BODY', $body);
                $dispatcher->send($message, 'default');

                $this->get('session')->getFlashBag()->add('notice', 'Successfully awarded contest to <strong>' . $projectBid->getUserInfo()->getDisplayName() . '</strong>');

                $referer = $this->generateUrl('project_studio', ['uuid' => $project->getUuid()]);

                return $this->redirect($referer);
            } else {
                $request->query->set('error', 'Please fix error below');
            }
        } else {
            if (!$user->isSubscribed()) {
                //$this->get('vocalizr_app.model.hint')->setSession(HintModel::HINT_CONTEST, $projectBid[0]->getAmount() / 20);
            }
        }

        return [
            'project' => $project,
            'bid'     => $projectBid,
            'form'    => $form->createView(),
        ];
    }
//     * @Secure(roles="ROLE_USER")
    /**
     * @Route("/contest/{uuid}/removeBid/{bidUuid}", name="contest_bid_remove")
     * @IsGranted("ROLE_USER")
     *
     * @param Request $request
     * @param string  $uuid
     * @param string  $bidUuid
     */
    public function removeBidAction(Request $request, $uuid, $bidUuid)
    {
        $em = $this->getDoctrine()->getManager();

        $projectBid = $em->getRepository('App:ProjectBid')
                ->findOneBy([
                    'uuid'      => $bidUuid,
                    'user_info' => $this->getUser(),
                ]);

        if ($projectBid) {
            // Delete vote entries
            $qb = $em->getRepository('App:EntryVote')
                    ->createQueryBuilder('ev')
                    ->delete()
                    ->where('ev.project_bid = :projectBid')
                    ->setParameter('projectBid', $projectBid);
            $qb->getQuery()->execute();

            $em->remove($projectBid);
            $em->flush();
        }

        $this->get('session')->getFlashBag()->add('notice', 'Your entry has been removed');
        return $this->redirect($this->generateUrl('contest_view', ['uuid' => $uuid]));
    }
//     * @Secure(roles="ROLE_USER")
    /**
     * @Template()
     * @IsGranted("ROLE_USER")
     */
    public function agreementAction($project, $projectBid)
    {
        $user = $this->getUser();
        $em   = $this->getDoctrine()->getManager();

        // get employer's membership
        $subscriptionPlan = $em->getRepository('App:SubscriptionPlan')
                ->getActiveSubscription($project->getUserInfo()->getId());

        // get employees membership
        $employeeSubscriptionPlan = $em->getRepository('App:SubscriptionPlan')
                ->getActiveSubscription($projectBid->getUserInfo()->getId());

        return [
            'project'                  => $project,
            'projectBid'               => $projectBid,
            'subscriptionPlan'         => $subscriptionPlan,
            'employeeSubscriptionPlan' => $employeeSubscriptionPlan,
        ];
    }

//     * @Secure(roles="ROLE_USER")
    /**
     * @Route("/contest/{uuid}/audio/download", name="contest_audio_download")
     *
     * @IsGranted("ROLE_USER")
     * @param Request $request
     * @param string  $uuid
     */
    public function audioDownloadAction(Request $request, $uuid)
    {
        $em   = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        // Get project
        $project = $em->getRepository('App:Project')
                ->findOneBy(['uuid' => $uuid]);

        if (!$project) {
            return $this->forward('VocalizrAppBundle:Default:error', [
                'error' => 'Invalid contest',
            ]);
        }

        if ($project->getProjectType() != Project::PROJECT_TYPE_CONTEST) {
            return $this->forward('VocalizrAppBundle:Default:error', [
                'error' => 'Permission denied',
            ]);
        }

        // Get project featured audio
        $audio = $em->getRepository('App:ProjectAudio')->findOneBy([
            'project' => $project->getId(),
            'flag'    => ProjectAudio::FLAG_FEATURED,
        ]);
        if (!$audio) {
            return $this->forward('VocalizrAppBundle:Default:error', [
                'error' => 'Invalid audio',
            ]);
        }

        if ($audio->getUserInfo()->getId() != $user->getId()) {
            // Record download
            $pad = $em->getRepository('App:ProjectAudioDownload')
                    ->findOneBy(['user_info' => $user, 'project_audio' => $audio]);
            if (!$pad) {
                $audio->setDownloadCount($audio->getDownloadCount() + 1);
                $em->persist($audio);

                $pad = new \App\Entity\ProjectAudioDownload();
                $pad->setUserInfo($user);
                $pad->setProjectAudio($audio);
                $em->persist($pad);
                $em->flush();
            }
        }

        $helper           = $this->get('service.helper');
        $downloadFilename = $audio->getUserInfo()->getUsernameOrDisplayName() . '-' . $project->getUuid() . '-contest-dl';
        $downloadFilename = $helper->slugify($downloadFilename) . '.mp3';
        @copy($audio->getAbsolutePath(), $this->get('kernel')->getRootDir() . '/../dl/' . $downloadFilename);

        header('Location: /dl/' . $downloadFilename);
        exit;
    }

//     * @Secure(roles="ROLE_USER")
    /**
     * @Route("/contest/{uuid}/sfs/audio/download", name="sfs_audio_download")
     *
     * @IsGranted("ROLE_USER")
     * @param Request $request
     * @param string  $uuid
     *
     * @Template()
     */
    public function sfsAudioDownloadAction(Request $request, $uuid)
    {
        $em   = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        // Get project
        $project = $em->getRepository('App:Project')
                ->findOneBy(['uuid' => $uuid, 'sfs' => true]);

        if (!$project) {
            return $this->forward('VocalizrAppBundle:Default:error', [
                'error' => 'Invalid contest',
            ]);
        }

        if ($project->getProjectType() != Project::PROJECT_TYPE_CONTEST) {
            return $this->forward('VocalizrAppBundle:Default:error', [
                'error' => 'Permission denied',
            ]);
        }

        if (!isset($_GET['agree'])) {
            return ['project' => $project];
        }

        // Get project featured audio
        $audio = $em->getRepository('App:ProjectAudio')->findOneBy([
            'project' => $project->getId(),
            'flag'    => ProjectAudio::FLAG_FEATURED,
        ]);
        if (!$audio) {
            return $this->forward('VocalizrAppBundle:Default:error', [
                'error' => 'Invalid audio',
            ]);
        }

        if ($audio->getUserInfo()->getId() != $user->getId()) {
            // Record download
            $pad = $em->getRepository('App:ProjectAudioDownload')
                    ->findOneBy(['user_info' => $user, 'project_audio' => $audio]);
            if (!$pad) {
                $audio->setDownloadCount($audio->getDownloadCount() + 1);
                $em->persist($audio);

                $pad = new \App\Entity\ProjectAudioDownload();
                $pad->setUserInfo($user);
                $pad->setProjectAudio($audio);
                $em->persist($pad);
                $em->flush();
            }
        }

        $helper           = $this->get('service.helper');
        $downloadFilename = $audio->getUserInfo()->getUsernameOrDisplayName() . '-' . $project->getUuid() . '-contest-dl';
        $downloadFilename = $helper->slugify($downloadFilename) . '.mp3';
        @copy($audio->getAbsolutePath(), $this->get('kernel')->getRootDir() . '/../dl/' . $downloadFilename);

        header('Location: /dl/' . $downloadFilename);
        exit;
    }

    /**
     * @Route("/contest/{uuid}/bid/upvote/{bidUuid}", name="contest_upvote_bid")
     *
     * @param string $id
     */
    public function upvoteBidAction($uuid, $bidUuid)
    {
        $request = $this->getRequest();
        if (!$request->isXmlHttpRequest()) {
            return $this->forward('VocalizrAppBundle:Default:error', [
                'error' => 'Invalid request',
            ]);
        }

        $contestViewed = $request->getSession()->get('contest_' . $uuid, false);

        if (!$contestViewed) {
            return new JsonResponse(['error' => 'You are unable to vote on this bid. Try refreshing the page']);
        }

        // If cookie is set, don't allow vote
        $cookieKey   = 'votes_' . $uuid;
        $cookie      = $_COOKIE;
        $cookieVotes = [];
        if (isset($cookie[$cookieKey])) {
            $cookieVotes = json_decode($cookie[$cookieKey], true);
        }
        if (isset($cookieVotes[$bidUuid])) {
            return new JsonResponse(['error' => 'You have already voted for this entry']);
        }

        $user    = $this->getUser();
        $ip      = $_SERVER['REMOTE_ADDR'];
        $browser = $_SERVER['HTTP_USER_AGENT'];
        $em      = $this->getDoctrine()->getManager();

        // Get project bid
        $projectBid = $em->getRepository('App:ProjectBid')
                ->findOneBy(['uuid' => $bidUuid]);

        if (!$projectBid) {
            return new JsonResponse(['error' => 'Invalid entry']);
        }
        $project = $projectBid->getProject();

        if ($project->getAwardedAt()) {
            return new JsonResponse(['error' => 'Contest has already been awarded']);
        }

        // Make sure project hasn't finished
        if (time() > $project->getBidsDue()->getTimestamp()) {
            return new JsonResponse(['error' => 'Contest has expired']);
        }

        $ipRange  = explode('.', $ip);
        $ipSearch = $ipRange[0] . '.' . $ipRange[1] . '.' . $ipRange[2];
        $ipSearch = $ipRange[0] . '.' . $ipRange[1];

        // get vote entries
        $qb = $em->getRepository('App:EntryVote')
                ->createQueryBuilder('ev')
                ->select('ev, pb')
                ->innerJoin('ev.project_bid', 'pb')
                ->where('(pb.id = :project_bid AND ev.ip_addr LIKE :ip)');
        $params = [
            'ip'          => $ipSearch . '%',
            'project_bid' => $projectBid->getId(),
        ];
        $qb->setParameters($params);
        $entryVotes = $qb->getQuery()->execute();
        if ($entryVotes) {
            return new JsonResponse(['error' => 'You have already up voted this entry']);
        }

        $voteCount = $projectBid->getVoteCount();

        $entryVote = new EntryVote();
        if ($user) {
            $entryVote->setUserInfo($user);
        }
        $entryVote->setProjectBid($projectBid);
        $entryVote->setIpAddr($ip);
        $entryVote->setBrowser($browser);
        $em->persist($entryVote);
        $voteCount++;

        $cookieVotes[$bidUuid] = true;
        $expire                = 60 * 60 * 24 * 30 * (int) 2 + time(); // Expire in 2 months
        setcookie($cookieKey, json_encode($cookieVotes), $expire, '/');

        $projectBid->setVoteCount($voteCount);

        $em->flush();

        return new JsonResponse(['count' => $projectBid->getVoteCount()]);
    }

    private function notifyFavorites($project)
    {
        $em   = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        // Get favorites depending on what they are lookign for
        $userInfoFavs = $em->getRepository('App:UserInfo')
                ->getUserFavoritesForInviting($this->getUser()->getId(), $project);

        if ($userInfoFavs) {
            $dispatcher = $this->get('hip_mandrill.dispatcher');
            $message    = new \Hip\MandrillBundle\Message();

            $favorites = $userInfoFavs[0]->getFavorites();
            foreach ($favorites as $favUserInfo) {

                // check if user has been invited to this project before
                // stops double click issues
                $q = $em->getRepository('App:ProjectInvite')
                        ->createQueryBuilder('pi')
                        ->select('count(pi)')
                        ->where('pi.project = :project')
                        ->andWhere('pi.user_info = :user_info')
                        ->setParameter('project', $project)
                        ->setParameter('user_info', $favUserInfo);
                $numResults = $q->getQuery()->getSingleScalarResult();
                if ($numResults > 0) {
                    continue;
                }

                $pi = new \App\Entity\ProjectInvite();
                $pi->setProject($project);
                $pi->setUserInfo($favUserInfo);
                $em->persist($pi);

                $userPref = $favUserInfo->getUserPref();
                if (is_null($userPref) || ($userPref && $userPref->getEmailProjectInvites())) {
                    if (!isset($message)) {
                        $message = new \Hip\MandrillBundle\Message();
                    }
                    $message->addTo($favUserInfo->getEmail());
                    $body = $this->container->get('templating')->render('VocalizrAppBundle:Mail:contestInvite.html.twig', [
                        'userInfo' => $favUserInfo,
                        'project'  => $project,
                    ]);
                    $message->addMergeVar($favUserInfo->getEmail(), 'BODY', $body);
                }
            }

            // If message is set, then send emails
            if (isset($message)) {
                $message
                    ->setSubject('Contest Invitation to "' . $project->getTitle() . '"')
                    ->setFromEmail('noreply@vocalizr.com')
                    ->setFromName('Vocalizr')
                    ->setPreserveRecipients(false)
                    ->setTrackOpens(true)
                    ->setTrackClicks(true);

                $dispatcher->send($message, 'default', [], true);
            }
        }
    }

    /**
     * Check if logged in user meets project preferences
     * return false if they don't
     *
     * @TODO Need to recode this to be more efficent
     *
     * @param Project $project
     *
     * @return array
     */
    private function userMeetProjectPreferences($project)
    {
        return $this->get('vocalizr_app.model.project')->getUserMeetProjectPreferencesArray($this->getUser(), $project);
    }

    private function markProjectRead($project)
    {
        $em = $this->getDoctrine()->getManager();

        if ($project->getUserInfo() == $this->getUser()) {
            $project->setEmployerReadAt(new \DateTime());
            $lastActivity = $project->getLastActivity();
            if (count($lastActivity) > 0
                    && $this->getUser() == $project->getUserInfo()
                    && $lastActivity['name'] == 'new bid') {
                $lastActivity['count'] = 0;
                $project->setLastActivity(json_encode($lastActivity));
            }
        } else {
            $project->setEmployeeReadAt(new \DateTime());
        }
        $em->flush($project);

        // if the project owner update all bids to read for this project and user
        if ($project->getUserInfo() == $this->getUser()) {
            $newDate = new \DateTime();
            $q       = $em->getRepository('App:ProjectBid')->createQueryBuilder('pb');
            $q->update()
                    ->set('pb.read_at', ':now')
                    ->andWhere('pb.project = :project')
                    ->andWhere('pb.read_at is null');
            $params = [
                ':project' => $project,
                ':now'     => new \DateTime(),
            ];
            $q->setParameters($params);
            $q->getQuery()->execute();
        }

        // udpate all activity for this project to read for this user
        $q = $em->getRepository('App:ProjectActivity')->createQueryBuilder('pa');
        $q->update()
                ->set('pa.activity_read', 1)
                ->where('pa.user_info = :user_info')
                ->andWhere('pa.project = :project');
        $params = [
            ':user_info' => $this->getUser(),
            ':project'   => $project,
        ];
        $q->setParameters($params);
        $q->getQuery()->execute();

        // update the invitation to read if there is one for this user and project
        $q = $em->getRepository('App:ProjectInvite')->createQueryBuilder('pi');
        $q->select('pi')
                ->where('pi.user_info = :user')
                ->andWhere('pi.project = :project')
                ->setParameter(':user', $this->getUser())
                ->setParameter(':project', $project);
        $invitations = $q->getQuery()->execute();
        if (count($invitations) > 0) {
            $invitation = $invitations[0];
            $invitation->setReadAt(new \DateTime());
            $em->flush($invitation);
        }

        // check to see if the user has any unread projects or invitations
        $q = $em->getRepository('App:Project')->createQueryBuilder('p');
        $q->select('count(p)')
                ->where('p.user_info = :user_info')
                ->andWhere('p.last_activity != :empty_activity')
                ->andWhere('p.employer_read_at is null')
                ->setParameter(':user_info', $this->getUser())
                ->setParameter(':empty_activity', '{}');
        $numEmployerUnread = $q->getQuery()->getSingleScalarResult();

        $q = $em->getRepository('App:Project')->createQueryBuilder('p');
        $q->select('count(p)')
                ->where('p.employee_user_info = :user_info')
                ->andWhere('p.last_activity != :empty_activity')
                ->andWhere('p.employee_read_at is null')
                ->setParameter(':user_info', $this->getUser())
                ->setParameter(':empty_activity', '{}');
        $numEmployeeUnread = $q->getQuery()->getSingleScalarResult();

        if ($this->getUser() && ($numEmployerUnread == 0 && $numEmployeeUnread == 0)) {
            $this->getUser()->setUnreadProjectActivity(false);
        }

        $q = $em->getRepository('App:ProjectInvite')->createQueryBuilder('pi');
        $q->select('count(pi)')
                ->where('pi.user_info = :user_info')
                ->andWhere('pi.read_at is null')
                ->setParameter(':user_info', $this->getUser());
        $numInvitesUnread = $q->getQuery()->getSingleScalarResult();
        if ($this->getUser() && $numInvitesUnread == 0) {
            $this->getUser()->setUnseenProjectInvitation(false);
        }

        // Set any notifications as read
        $qb = $em->getRepository('App:Notification')
                ->createQueryBuilder('n')
                ->update()
                ->set('n.notify_read', '1')
                ->where('n.user_info = :userInfo and n.project = :project');
        $qb->setParameters(['userInfo' => $this->getUser(), 'project' => $project]);
        $qb->getQuery()->execute();

        $em->getRepository('App:Notification')->updateUnreadCount($this->getUser());

        $em->flush();
    }
}
