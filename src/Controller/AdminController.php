<?php

namespace App\Controller;

use App\Entity\AdminActionAudit;
use App\Form\Type\ArticleType;
use App\Form\Type\AuthorType;
use App\Model\UserInfoModel;
use App\Service\StripeManager;
use App\Service\UserRestrictionService;
use Doctrine\ORM\EntityManager;
use Slot\MandrillBundle\Message;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use App\Entity\Project;
use App\Entity\UserCertification;
use App\Entity\UserInfo;
use App\Entity\UserStripeIdentity;
use App\Entity\UserSubscription;
use App\Entity\VocalizrActivity;
use App\Exception\UnsubscribeException;
use App\Form\Type\Admin\SubscriptionIdsCsvType;
use App\Form\Type\Admin\SubscriptionType;
use App\Object\SubscriptionIdsCsvObject;
use App\Service\MembershipSourceHelper;


/**
 * Class AdminController
 * @package App\Controller
 *
 * @method UserInfo getUser
 */
class AdminController extends AbstractController
{
    /**
     * @Route("/admin", name="admin")
     * @Template()
     *
     * @return RedirectResponse|Response
     */
    public function indexAction()
    {
        // check the logged in user is an admin
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            return $this->redirect($this->generateUrl('dashboard'));
        }

        $em = $this->getDoctrine()->getManager();

        // determine total number of users
        $q = $em->getRepository('App:UserInfo')
                ->createQueryBuilder('ui')
                ->select('count(ui)')
                ->where('ui.is_active = true');
        $numUsers = $q->getQuery()->getSingleScalarResult();

        // determine total number of producers
        $q = $em->getRepository('App:UserInfo')
                ->createQueryBuilder('ui')
                ->select('count(ui)')
                ->where('ui.is_active = true')
                ->andWhere('ui.is_producer = true');
        $numProducers = $q->getQuery()->getSingleScalarResult();

        // determine total number of pro users
        $q = $em->getRepository('App:UserInfo')
                ->createQueryBuilder('ui')
                ->select('count(ui)')
                ->where('ui.is_active = true')
                ->andWhere('ui.subscription_plan IS NOT NULL');
        $proUsers = $q->getQuery()->getSingleScalarResult();

        $q = $em->getRepository('App:UserSubscription')
                ->createQueryBuilder('ui')
                ->select('count(ui)')
                ->where('ui.is_active = true')
                ->andWhere('ui.stripe_subscr_id IS NOT NULL');
        $proStripeUsers = $q->getQuery()->getSingleScalarResult();

        $q = $em->getRepository('App:UserSubscription')
                ->createQueryBuilder('ui')
                ->select('count(ui)')
                ->where('ui.is_active = true')
                ->andWhere("ui.paypal_subscr_id != '0'");
        $proPaypalUsers = $q->getQuery()->getSingleScalarResult();

        // determine total number of vocalists
        $q = $em->getRepository('App:UserInfo')
                ->createQueryBuilder('ui')
                ->select('count(ui)')
                ->where('ui.is_active = true')
                ->andWhere('ui.is_vocalist = true');
        $numVocalists = $q->getQuery()->getSingleScalarResult();

        $today = new \DateTime();
        // determine number of gigs currently published
        $q = $em->getRepository('App:Project')
                ->createQueryBuilder('p')
                ->select('count(p)')
                ->where('p.publish_type is not null')
                ->andWhere('p.published_at is not null')
                ->andWhere('p.project_bid is null')
                ->andWhere('p.bids_due >= :today')
                ->setParameter('today', $today);
        $numGigsCurrentlyPublished = $q->getQuery()->getSingleScalarResult();

        // determine number of gigs in the studio currently
        $q = $em->getRepository('App:Project')
                ->createQueryBuilder('p')
                ->select('count(p)')
                ->where('p.publish_type is not null')
                ->andWhere('p.project_bid is not null')
                ->andWhere('p.is_complete = false');
        $numGigsInStudio = $q->getQuery()->getSingleScalarResult();

        // set the defaults
        $range     = 'week';
        $startDate = new \DateTime();
        $startDate->sub(new \DateInterval('P12W'));
        $endDate = new \DateTime();

        return $this->render('Admin/index.html.twig', [
            'users'          => $numUsers,
            'vocalists'      => $numVocalists,
            'producers'      => $numProducers,
            'proUsers'       => $proUsers,
            'proStripeUsers' => $proStripeUsers,
            'proPaypalUsers' => $proPaypalUsers,
            'published_gigs' => $numGigsCurrentlyPublished,
            'gigs_in_studio' => $numGigsInStudio,
            'range'          => $range,
            'startDate'      => $startDate,
            'endDate'        => $endDate
        ]);
    }

    /**
     * @Route("/admin/users", name="admin_users")
     * @Template()
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function adminUsersAction(Request $request)
    {
        // check the logged in user is an admin
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            return $this->redirect($this->generateUrl('dashboard'));
        }

        $subscriptionType = $this->createForm(SubscriptionType::class);

        return $this->render('Admin/adminUsers.html.twig', [
            'subscription_form' => $subscriptionType->createView(),
        ]);
    }

    /**
     * @Route("/admin/users/search", name="admin_users_search")
     * @Template()
     * @param Request $request
     * @return Response
     */
    public function adminUserSearchAction(Request $request)
    {

        // check the logged in user is an admin
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            $responseData = [
                'success' => false,
                'message' => 'Invalid Access',
            ];
            return new Response(json_encode($responseData));
        }

        $em = $this->getDoctrine()->getManager();

        $searchTerm = trim($request->get('s'));

        if ($searchTerm == '') {
            $responseData = [
                'success' => false,
                'message'              => 'No search string defined',
            ];
            return new Response(json_encode($responseData));
        }

        $searchFields = [];
        $ppSearchFields = [];
        $isExactSearch = false;
        // handle checkboxes
        foreach ($request->request->all() as $name => $value) {
            if (!in_array($value, ['on', 1, true])) {
                continue;
            }

            if (substr($name, 0, 10) === "search_in_") {
                $name = substr($name, 10);
                if (in_array($name, ['email', 'username', 'display_name'])) {
                    $searchFields[] = $name;
                }
            } elseif ($name === "exact_search") {
                $isExactSearch = true;
            } elseif (substr($name, 0, 10) === "search_pp_") {
                $name = substr($name, 10);
                if (in_array($name, ['email', 'subscription_id', 'transaction_id'])) {
                    $ppSearchFields[] = $name;
                }
            }
        }

        $results = $em->getRepository('App:UserInfo')
            ->findUser($searchTerm, $searchFields, $isExactSearch, $ppSearchFields)
        ;

        $jsonResponse = [
            'success'    => true,
            'numResults' => count($results),
            'html'       => $this->renderView(
                'Admin/adminUsersResults.html.twig',
                ['results' => $results]
            ),
        ];
        return new Response(json_encode($jsonResponse));
    }

    /**
     * @Route("/admin/user/verify/{id}", name="admin_user_verify")
     * @Route("/admin/user/unverify/{id}", name="admin_user_unverify")
     *
     * @param Request $request
     * @param int $id
     */
    public function adminUserVerifyAction(Request $request, $id)
    {
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            throw new AccessDeniedHttpException();
        }
        $em = $this->getDoctrine()->getManager();
        $userModel = $this->get('vocalizr_app.model.user_info');
        $user = $userModel->getObject($id);

        $setVerified = ($request->get('_route') === 'admin_user_verify');

        // Do nothing if user verification status is already set as required.
        if ($user->isVerified() !== $setVerified) {
            $identity = new UserStripeIdentity();
            $identity
                ->setUser($user)
                ->setVerified($setVerified)
                ->setCustom(true)
            ;

            $user->getUserIdentity()->add($identity);

            $em->persist($identity);
            $em->flush();
        }

        return new JsonResponse([
            'success' => true,
            'userId'  => $id,
            'html'    => $this->renderView(
                'Admin/adminUsersResults.html.twig',
                ['results' => [$user]]
            ),
        ]);
    }

    /**
     * @Route("/admin/user/activate/{id}", name="admin_user_activate")
     * @Route("/admin/user/deactivate/{id}", name="admin_user_deactivate")
     * @param Request $request
     * @param $id
     * @param UserInfoModel $userModel
     * @return JsonResponse|Response
     */
    public function adminUserActivateAction(Request $request, $id, UserInfoModel $userModel)
    {
        // check the logged in user is an admin
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid Access',
            ]);
        }

        $em = $this->getDoctrine()->getManager();

//        $userModel = $this->get('vocalizr_app.model.user_info');

        // load the user
        /** @var UserInfo $userInfo */
        $userInfo = $em->getRepository('App:UserInfo')
                ->findOneBy(['id' => $id]);
        if (!$userInfo) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid user',
            ]);
        }
        $userInfo->setActivationEventSuppressed(true);

        // create the admin audit object for recording this action
        $audit = new \App\Entity\AdminActionAudit();
        $audit->setUserInfo($userInfo);
        $audit->setActioner($this->getUser());

        if ($userInfo->getIsActive()) {
            // Deactivation
            $userModel->deactivate($userInfo);
            $audit->setAction('deactivate');
        } else {
            $userInfo->setIsActive(true);
            $audit->setAction('activate');
        }

        $em->persist($userInfo);
        $em->persist($audit);
        $em->flush();

        $results   = [];
        $results[] = $userInfo;

        $jsonResponse = [
            'success' => true,
            'userId'  => $userInfo->getId(),
            'html'    => $this->renderView(
                'Admin/adminUsersResults.html.twig',
                ['results' => $results]
            ),
        ];
        return new Response(json_encode($jsonResponse));
    }

    /**
     * @Route("/admin/user/upgrade/{id}", name="admin_user_upgrade")
     * @Route("/admin/user/downgrade/{id}", name="admin_user_downgrade")
     * @param Request       $request
     * @param int           $id
     * @param UserInfoModel $userModel
     * @return JsonResponse
     */
    public function adminUserProAction(Request $request, $id, UserInfoModel $userModel)
    {
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            throw new AccessDeniedHttpException();
        }
        $em = $this->getDoctrine()->getManager();
//        $userModel = $this->get('vocalizr_app.model.user_info');
        $user = $userModel->getObject($id);

        $responseData = ['userId' => $id];

        if ($request->get('_route') === 'admin_user_upgrade') {
            $subscription = new UserSubscription();
            $type         = $this->createForm(SubscriptionType::class, $subscription);
            $type->handleRequest($request);
            $plan = $em->getRepository('App:SubscriptionPlan')->findOneBy(['static_key' => 'PRO']);
            $subscription
                ->setSubscriptionPlan($plan)
                ->setIsActive(true)
                ->setUserInfo($user)
                ->setNextPaymentDate($subscription->getDateEnded())
                ->setDateCommenced(new \DateTime())
                ->setSource(MembershipSourceHelper::SUB_SOURCE_ADMIN)
            ;

            $user->setSubscriptionPlan($plan)->addUserSubscription($subscription);
            $em->persist($subscription);
            $em->flush();
        } else {
            if (!$user) {
                return new JsonResponse(['success' => false, 'error' => 'User not found'], 404);
            }

            try {
                $userModel->unsubscribe($user, true);
            } catch (UnsubscribeException $e) {
                $responseData['error'] = $e->getMessage();
            }
        }

        if (isset($responseData['error'])) {
            $responseData['success'] = false;
        } else {
            $responseData['success'] = true;
            $responseData['html'] = $this->renderView(
                'Admin/adminUsersResults.html.twig',
                ['results' => [$user]]
            );
        }

        return new JsonResponse($responseData);
    }

    /**
     * @Route("/admin/user/certify/{id}/{from}", defaults={"from" = "admin"}, name="admin_user_certify")
     * @param Request            $request
     * @param $id
     * @param $from
     * @param StripeManager      $stripeManager
     * @param ContainerInterface $container
     * @return Response
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function adminUserCertifyAction(Request $request, $id, $from, StripeManager $stripeManager, ContainerInterface $container)
    {
        // check the logged in user is an admin
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            $responseData = ['success' => false,
                'message'              => 'Invalid Access', ];
        }

        $em = $this->getDoctrine()->getManager();

        // load the user
        $userInfo = $em->getRepository('App:UserInfo')
                ->findOneBy(['id' => $id]);
        if (!$userInfo) {
            $responseData = ['success' => false,
                'message'              => 'Invalid user', ];
        }
        // create the admin audit object for recording this action
        $audit = new \App\Entity\AdminActionAudit();
        $audit->setUserInfo($userInfo);
        $audit->setActioner($this->getUser());

        if ($userInfo->getIsCertified()) {
            $userInfo->setIsCertified(false);
            $audit->setAction('uncertify');
            $userCertified = $em->getRepository('App:UserCertification')
                ->findOneBy(['userInfo' => $userInfo->getId()], ['id' => 'DESC']);
//            $stripeManager = $this->get('vocalizr_app.stripe_manager');
            if (isset($userCertified)) {
                $paymentSessionData = $em->getRepository('App:PaymentSessionData')
                    ->findOneBy(['userCertification' => $userCertified->getId()]);
                $stripeManager->getCancelSubscription($paymentSessionData->getSubscriptionId());
            }
        } else {
            $userInfo->setIsCertified(true);
            $audit->setAction('certify');

            // Send email to user
            $dispatcher = $container->get('hip_mandrill.dispatcher');
            $message    = new Message();
            $message->setSubject('Congratulations, you\'re Certified!');
            $message
                ->setTrackOpens(true)
                ->setTrackClicks(true);

            $message->addTo($userInfo->getEmail());
            $body = $this->container->get('templating')->render('Mail/certified.html.twig', [
                'userInfo' => $userInfo,
            ]);
            $message->addGlobalMergeVar('BODY', $body);
            $dispatcher->send($message, 'certification-successful-new-9-nov');
        }
        $em->persist($audit);
        $em->flush();

        $results   = [];
        $results[] = $userInfo;

        if ($from == 'admin') {
            $jsonResponse = [
                'success' => true,
                'userId'  => $userInfo->getId(),
                'html'    => $this->renderView(
                    'Admin/adminUsersResults.html.twig',
                    ['results' => $results]
                ),
            ];
        } else {
            $jsonResponse = [
                'success'   => true,
                'certified' => $userInfo->getIsCertified(),
            ];
        }
        return new Response(json_encode($jsonResponse));
    }

    /**
     * @Route("/admin/gigs", name="admin_projects")
     * @Template()
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function adminProjectsAction(Request $request)
    {
        // check the logged in user is an admin
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            return $this->redirect($this->generateUrl('dashboard'));
        }

        $em = $this->getDoctrine()->getManager();

        return $this->render('Admin/adminProjects.html.twig', []);
    }

    /**
     * @Route("/admin/projects/search", name="admin_projects_search")
     * @Template()
     */
    public function adminProjectSearchAction(Request $request)
    {

        // check the logged in user is an admin
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            $responseData = [
                    'success'  => false,
                    'message'  => 'Invalid Access',
                ];
            return new Response(json_encode($responseData));
        }

        $em = $this->getDoctrine()->getManager();

        $searchTerm = trim($request->get('s'));

        if ($searchTerm == '') {
            $responseData = ['success' => false,
                'message'              => 'No search string defined', ];
            return new Response(json_encode($responseData));
        }

        $results = $em->getRepository('App:Project')
                    ->findProject($searchTerm);
        $jsonResponse = [
            'success'    => true,
            'numResults' => count($results),
            'html'       => $this->renderView(
                'Admin/adminProjectsResults.html.twig',
                ['results' => $results]
            ),
        ];
        return new Response(json_encode($jsonResponse));
    }

    /**
     * @Route("/admin/project/activate/{id}", name="admin_project_activate")
     * @Route("/admin/project/deactivate/{id}", name="admin_project_deactivate")
     * @param Request $request
     * @param $id
     * @return JsonResponse|Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function adminProjectActivateAction(Request $request, $id)
    {
        // check the logged in user is an admin
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            $responseData = ['success' => false, 'message' => 'Invalid Access'];
            return new JsonResponse($responseData);
        }

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var Project $project */
        $project = $em->getRepository('App:Project')->findOneBy(['id' => $id]);
        if (!$project) {
            $responseData = ['success' => false, 'message' => 'Invalid project'];
            return new JsonResponse($responseData);
        }

        // create the admin audit object for recording this action
        $audit = new AdminActionAudit();
        $audit->setProject($project);
        $audit->setActioner($this->getUser());

        if ($project->getIsActive()) {
            $project->setIsActive(false);
            $audit->setAction('deactivate-project');

            // When an admin deactivates a Contest, the money for the contest should be refunded to the user's wallet.
            // Also we should remove corresponding project escrow.
            if (Project::PROJECT_TYPE_CONTEST === $project->getProjectType()) {
                $uwt = $this->createUserWalletTransaction($project);
                $em->persist($uwt);

                $projectEscrow = $project->getProjectEscrow();

                $project->setProjectEscrow(null);

                $em->remove($projectEscrow);
            }

            // delete from activity
            $qb = $em->createQueryBuilder();
            $qb->delete('App:VocalizrActivity', 'v');
            $qb->andWhere($qb->expr()->eq('v.project', ':project'));
            $qb->setParameter(':project', $project);
            $qb->getQuery()->execute();

            // Delete any messages related to the gig.
            $em->getRepository('App:MessageThread')->deleteThreadsForGig($project);
        } else {
            if (Project::PROJECT_TYPE_CONTEST === $project->getProjectType()) {
                $subscriptionPlan = $em->getRepository('App:SubscriptionPlan')->getActiveSubscription($project->getUserInfo()->getId());

                // Make sure user has enough funds in their wallet
                if (!$this->checkFundsForProject($project, $subscriptionPlan)) {
                    $responseData = ['success' => false, 'projectId' => $project->getId(), 'message' => 'Creator doesn\'t have enough funds in wallet to publish'];
                    return new JsonResponse($responseData);
                }

                // Add to project escrow
                $projectEscrow = new \App\Entity\ProjectEscrow();
                $projectEscrow->setFee(0);
                $projectEscrow->setAmount($project->getBudgetFrom() * 100);
                $projectEscrow->setUserInfo($project->getUserInfo());
                $em->persist($projectEscrow);

                $project->setProjectEscrow($projectEscrow);

                // Create transactions in user wallet
                $uwt = $this->createUserWalletTransaction($project, false);
                $em->persist($uwt);
            }

            $project->setIsActive(true);
            $audit->setAction('activate-project');
        }

        $em->persist($audit);
        $em->flush();

        $results   = [];
        $results[] = $project;

        $jsonResponse = [
            'success'   => true,
            'projectId' => $project->getId(),
            'html'      => $this->renderView(
                'Admin/adminProjectsResults.html.twig',
                ['results' => $results]
            ),
        ];

        return new Response(json_encode($jsonResponse));
    }

    /**
     * @param Project $project
     * @param bool $isDeactivate
     *
     * @param UserInfoModel $userInfoModel
     *
     * @return \App\Entity\UserWalletTransaction
     */
    private function createUserWalletTransaction($project, $isDeactivate = true, UserInfoModel $userInfoModel)
    {
        $amount      = $project->getBudgetFrom() * 100;
        $description = 'Refund for cancelled Contest {project}';
        if (!$isDeactivate) {
            $amount      = (-1) * $amount;
            $description = 'Escrow payment to contest {project}';
        }

        $uwt = $userInfoModel->createWalletTransaction($project->getUserInfo(), $amount)
        ;

        $uwt
            ->setDescription($description)
            ->setData(json_encode([
                'projectTitle' => $project->getTitle(),
                'projectUuid'  => $project->getUuid(),
                'projectType'  => Project::PROJECT_TYPE_CONTEST,
            ]));

        return $uwt;
    }

    /**
     * Check if logged in user has enough funds in wallet to publish project
     *
     * TODO: use calculator service instead
     *
     * @deprecated
     *
     * @param Project $project
     * @param array   $subscriptionPlan
     *
     * @return bool
     */
    private function checkFundsForProject(Project $project, array $subscriptionPlan)
    {
        // Workout total of project and if they have enough in their wallet
        $projectPrice = $project->getBudgetTo() * 100;
        // Add fees
        $projectPrice += $this->calculateProjectFee($project, $subscriptionPlan);

        return $project->getUserInfo()->getWallet() >= $projectPrice;
    }

    /**
     * Calculate project fees
     *
     * @deprecated
     *
     * @param Project $project
     * @param array   $subscriptionPlan
     *
     * @return int
     */
    private function calculateProjectFee(Project $project, array $subscriptionPlan)
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
        if ($project->getMessaging()) {
            $projectPrice += $subscriptionPlan['project_messaging_fee'];
        }
        if ($project->getRestrictToPreferences()) {
            $projectPrice += $subscriptionPlan['project_restrict_fee'];
        }
        if ($project->getToFavorites()) {
            $projectPrice += $subscriptionPlan['project_favorites_fee'];
        }
        if ($project->getProRequired()) {
            $projectPrice += $subscriptionPlan['project_lock_to_cert_fee'];
        }

        return $projectPrice;
    }

    /**
     * @Route("/admin/project/send-employer-receipt/{id}", name="admin_project_resend_employer_receipt")
     *
     * @param Request $request
     * @param int     $id
     *
     * @return JsonResponse
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function adminProjectResendEmployerReceipt(Request $request, int $id)
    {
        // check the logged in user is an admin
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            $responseData = ['success' => false, 'message' => 'Invalid Access'];
            return new JsonResponse($responseData);
        }

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var Project|null $project */
        $project = $em->getRepository('App:Project')
            ->findOneBy(['id' => $id]);
        if (!$project) {
            $responseData = ['success' => false, 'message' => 'Invalid project'];
            return new JsonResponse($responseData);
        }

        if (!$project->getIsComplete()) {
            return new JsonResponse([
                'success'   => true,
                'projectId' => $project->getId(),
                'html'      => $this->renderView('Admin/adminProjectsResults.html.twig', [
                    'results' => [$project],
                ]),
            ]);
        }

        $mandrillService = $this->get('vocalizr_app.service.mandrill');
        $mandrillService->sendProjectEmployerReceipt($project, $this->generateUrl('project_studio', [
            'uuid' => $project->getUuid(),
        ], true));

        return new JsonResponse([
            'success'   => true,
            'projectId' => $project->getId(),
            'html'      => $this->renderView('Admin/adminProjectsResults.html.twig', [
                'results'      => [$project],
                'sent_receipt' => true,
            ]),
        ]);
    }

    /**
     * @Route("/admin/project/public/{id}", name="admin_project_make_public")
     * @Route("/admin/project/private/{id}", name="admin_project_make_private")
     *
     * @param Request $request
     * @param int     $id
     *
     * @return JsonResponse
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function adminProjectPublishType(Request $request, int $id)
    {
        // check the logged in user is an admin
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            $responseData = ['success' => false, 'message' => 'Invalid Access'];
            return new JsonResponse($responseData);
        }

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        /** @var Project|null $project */
        $project = $em->getRepository('App:Project')
            ->findOneBy(['id' => $id]);
        if (!$project) {
            $responseData = ['success' => false, 'message' => 'Invalid project'];
            return new JsonResponse($responseData);
        }

        if ($project->getPublishType() === Project::PUBLISH_PUBLIC) {
            $project->setPublishType(Project::PUBLISH_PRIVATE);
            $em->getRepository('App:VocalizrActivity')->createQueryBuilder('va')
                ->delete()
                ->where('va.activity_type = :activity_type')
                ->andWhere('va.project = :project')
                ->setParameters([
                    'activity_type' => VocalizrActivity::ACTIVITY_TYPE_NEW_PROJECT,
                    'project'       => $project,
                ])
                ->getQuery()->execute();
        } else {
            $project->setPublishType(Project::PUBLISH_PUBLIC);
            $va = new VocalizrActivity();
            $va
                ->setActivityType(VocalizrActivity::ACTIVITY_TYPE_NEW_PROJECT)
                ->setActionedUserInfo($project->getUserInfo())
                ->setProject($project)
                ->setData(json_encode([]));
            $em->persist($va);
        }
        $em->persist($project);
        $em->flush();

        return new JsonResponse([
            'success'   => true,
            'projectId' => $project->getId(),
            'html'      => $this->renderView('Admin/adminProjectsResults.html.twig', [
                'results' => [$project],
            ]),
        ]);
    }

    /**
     * Gets the data pieces for the statistics
     *
     * @Route("/admin/stats", name="admin_stats")
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function adminStatsAction(Request $request)
    {
        $em   = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        // check the user is an admin
        if (!$user->getIsAdmin()) {
            $responseData = ['success' => false,
                'message'              => 'Invalid Access', ];
            return new Response(json_encode($responseData));
        }
        $stats = $em->getRepository('App:Statistics')
                    ->findStatsForCriteria([
                        'statistics_type' => $request->get('range'),
                        'start_date'      => $request->get('startDate'),
                        'end_date'        => $request->get('endDate'),
                    ]);
        $statsArray = [];
        foreach ($stats as $stat) {
            $statsArray[] = [
                'start_date'             => $stat->getStartDate(),
                'label'                  => $stat->getStartDate()->format('d M Y'),
                'users'                  => $stat->getUsers(),
                'vocalists'              => $stat->getVocalists(),
                'producers'              => $stat->getProducers(),
                'gigs'                   => $stat->getGigs(),
                'published_gigs'         => $stat->getPublishedGigs(),
                'public_published_gigs'  => $stat->getPublicPublishedGigs(),
                'private_published_gigs' => $stat->getPrivatePublishedGigs(),
                'awarded_gigs'           => $stat->getAwardedGigs(),
                'public_awarded_gigs'    => $stat->getPublicAwardedGigs(),
                'private_awarded_gigs'   => $stat->getPrivateAwardedGigs(),
                'completed_gigs'         => $stat->getCompletedGigs(),
                'public_completed_gigs'  => $stat->getPublicCompletedGigs(),
                'private_completed_gigs' => $stat->getPrivateCompletedGigs(),
                'revenue'                => $stat->getRevenue(),
                'bids'                   => $stat->getBids(),
                'messages'               => $stat->getMessages(),
            ];
        }

        $responseData = [
            'success' => true,
            'stats'   => $statsArray,
        ];

        return new Response(json_encode($responseData));
    }

    /**
     * Displays the filter for the accounting report
     *
     * @Route("/admin/accountingReport", name="admin_accounting_report")
     * @Template()
     *
     * @param Request $request
     *
     * @return Response
     */
    public function adminAccountingReportAction(Request $request)
    {
        // check the logged in user is an admin
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            return $this->redirect($this->generateUrl('dashboard'));
        }

        $error = null;
        $form  = $this->createFormBuilder()
                ->add('startDate', DateType::class, [
                    'widget'      => 'single_text',
                    'constraints' => [
                            new NotBlank(),
                        ],
                    'attr' => [
                        'class' => 'form-control ',
                    ],
                ])
                ->add('endDate', DateType::class, [
                    'widget'      => 'single_text',
                    'constraints' => [
                            new NotBlank(),
                        ],
                    'attr' => [
                        'class' => 'form-control ',
                    ],
                ])
                ->getForm();
        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            $startDate = $form->get('startDate')->getData();
            $endDate   = $form->get('endDate')->getData();
            if ($form->isValid()) {
                $startDate = $form->get('startDate')->getData();
                $endDate   = $form->get('endDate')->getData();

                // switch the dates around for the query if start is after end
                if ($startDate > $endDate) {
                    $tmpDate   = $startDate;
                    $startDate = $endDate;
                    $endDate   = $tmpDate;
                }

                // get the completed projects
                $em = $this->getDoctrine()->getManager();
                $q  = $em->getRepository('App:Project')
                        ->createQueryBuilder('p')
                        ->where('p.is_complete = true')
                        ->andWhere('p.completed_at >= :startDate')
                        ->setParameter('startDate', $startDate)
                        ->andWhere('p.completed_at <= :endDate')
                        ->setParameter('endDate', $endDate);
                $projects = $q->getQuery()->execute();

                // get upgrades
                $q = $em->getRepository('App:Project')
                      ->createQueryBuilder('p')
                      ->where('p.published_at >= :startDate')
                      ->setParameter('startDate', $startDate)
                      ->andWhere('p.published_at <= :endDate')
                      ->setParameter('endDate', $endDate)
                      ->andWhere('p.fees > 0');

                $projectUpgrades = $q->getQuery()->execute();

                if (count($projects) == 0 && count($projectUpgrades) == 0) {
                    $error = 'No gigs found for that date period';
                } else {
                    $response = $this->render('Admin/accountingReportCsv.html.twig', ['projects' => $projects, 'projectUpgrades' => $projectUpgrades]);

                    $response->headers->set('Content-Type', 'text/csv');
                    $response->headers->set('Content-Disposition', 'attachment; filename="accountReport.csv"');

                    return $response;
                }
            }
        }
        return $this->render('Admin/adminAccountingReport.html.twig', [
            'error' => $error,
            'form'  => $form->createView(),
            ]);
    }

    // VMAG

    /**
     * Display list of articles
     *
     * @Route("/admin/vmag", name="admin_vmag")
     * @Template()
     *
     * @param Request            $request
     * @param ContainerInterface $container
     *
     * @return Response
     */
    public function vmagAction(Request $request, ContainerInterface $container)
    {
        // check the logged in user is an admin
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            return $this->redirect($this->generateUrl('dashboard'));
        }

        $em = $this->getDoctrine()->getManager();
        $q  = $em->getRepository('App:Article')->createQueryBuilder('a');
        $q->select('a, u, ac');
        $q->leftJoin('a.author', 'u');
        $q->innerJoin('a.article_category', 'ac');
        $q->orderBy('a.created_at', 'DESC');

        $query = $q->getQuery();

        $paginator  = $container->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->get('page', 1)/*page number*/,
            20// limit per page
        );

        return $this->render('Admin/vmag.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    /**
     * View vmag article
     *
     * @Route("/admin/vmag/article/{id}", name="admin_vmag_article", defaults={"id" = ""})
     * @Template()
     *
     * @param Request $request
     *
     * @return Response
     */
    public function vmagArticleAction(Request $request, $id)
    {
        // check the logged in user is an admin
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            return $this->redirect($this->generateUrl('dashboard'));
        }

        $em      = $this->getDoctrine()->getManager();
        $article = new \App\Entity\Article();

        if ($id) {
            $article = $em->getRepository('App:Article')->find($id);
        }

        $form = $this->createForm(ArticleType::class, $article);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if (isset($_POST['publish'])) {
                if (!$article->getPath() && !$article->getFile()) {
                    $form->addError(new \Symfony\Component\Form\FormError('Please upload a header image'));
                }
            }

            if ($form->isValid()) {
                $file = $article->getFile();
                if ($file) {
                    $pathinfo = pathinfo($_FILES['article']['name']['file']);
                    $article->setPath($article->getSlug() . '.' . $pathinfo['extension']);

                    if (!copy($_FILES['article']['tmp_name']['file'], $article->getAbsolutePath())) {
                        echo 'cannot save image';
                    }


                    $image = new \claviska\SimpleImage();
                    // Magic! ✨
                    $image->fromFile($article->getAbsolutePath());
                    $image->resize(1600, 1600);             // resize to SIZE X SIZE pixels
                    $image->toFile($article->getAbsolutePath(), 'image/png');  // convert to PNG and save a copy to new-image.png

                    // create small one
                    $image->resize(600, 600);             // resize to SIZE X SIZE pixels
                    $image->toFile($article->getUploadRootDir() . '/small/' . $article->getPath(), 'image/png');

                    $article->setUpdatedAt(new \DateTime());
                }

                if (isset($_POST['publish'])) {
                    $article->setPublishedAt(new \DateTime());
                    $this->addFlash('notice', 'Article Published');
                } else {
                    $this->addFlash('notice', 'Article Saved');
                }
                $em->persist($article);
                $em->flush();

                return $this->redirect($this->generateUrl('admin_vmag_article', ['id' => $article->getId()]));
            }
        }

        return $this->render('Admin/vmagArticle.html.twig', [
            'form'    => $form->createView(),
            'article' => $article,
        ]);
    }

    /**
     * Delete vmag article
     *
     * @Route("/admin/vmag/article/{id}/delete", name="admin_vmag_article_delete")
     * @Template()
     *
     * @param Request $request
     *
     * @return Response
     */
    public function vmagArticleDeleteAction(Request $request, $id)
    {
        // check the logged in user is an admin
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            return $this->redirect($this->generateUrl('dashboard'));
        }

        $em = $this->getDoctrine()->getManager();

        $article = $em->getRepository('App:Article')->find($id);

        if (!$article) {
            return $this->createNotFoundException('Invalid article');
        }
        $title = $article->getTitle();

        $em->remove($article);
        $em->flush();

        $this->addFlash('notice', 'Article (' . $title . ') Deleted');

        return $this->redirect($this->generateUrl('admin_vmag'));
    }

    /**
     * Upload image for articles
     *
     * @Route("/admin/vmag/upload/image", name="admin_vmag_upload_image")
     * @Template()
     *
     * @param Request $request
     *
     * @return Response
     */
    public function vmagImageUploadAction(Request $request)
    {
        // check the logged in user is an admin
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            return $this->redirect($this->generateUrl('dashboard'));
        }

        if (!isset($_FILES['image'])) {
            die('<script>top.$("#loading").hide();alert("failed to upload image")</script>');
        }

        $em = $this->getDoctrine()->getManager();

        $ai = new \App\Entity\ArticleImage();

        // Create unique name
        $path        = pathinfo($_FILES['image']['name']);
        $newFileName = $path['filename'] . '-' . date('ymdhis') . '.' . $path['extension'];

        $ai->setPath($newFileName);

        // Check if upload directory exists, if not create it
        if (!is_dir($ai->getUploadRootDir())) {
            mkdir($ai->getUploadRootDir(), 0777, true);
        }

        // Move file to new directory from tmp dir
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $ai->getAbsolutePath())) {
            die('<Script>top.$("#loading").hide();alert("failed to upload image")</script>');
        }
        $em->persist($ai);
        $em->flush();

        return $this->render('Admin,vmagImageUpload.html.twig', [
            'ai' => $ai,
        ]);
    }

    /**
     * Delete image for article
     *
     * @Route("/admin/vmag/upload/image/delete/{id}", name="admin_vmag_delete_image")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function vmagImageDeleteAction(Request $request)
    {
        // check the logged in user is an admin
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            return $this->redirect($this->generateUrl('dashboard'));
        }

        $em = $this->getDoctrine()->getManager();
        $id = $request->get('id');

        $image = $em->getRepository('App:ArticleImage')->find($id);

        $em->remove($image);
        $em->flush();

        echo json_encode(['success' => true]);

        exit;
    }

    /**
     * Display images available for the mag
     *
     * @Route("/admin/vmag/images", name="admin_vmag_images")
     * @Template()
     *
     * @param Request $request
     *
     * @return Response
     */
    public function vmagImagesAction(Request $request)
    {
        // check the logged in user is an admin
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            return $this->redirect($this->generateUrl('dashboard'));
        }

        $em = $this->getDoctrine()->getManager();

        $images = $em->getRepository('App:ArticleImage')->findBy([], ['created_at' => 'DESC'], 12);

        return $this->render('Admin/vmagImages.html.twig', [
            'images' => $images,
        ]);
    }

    /**
     * Add/Edit author to vmag
     *
     * @Route("/admin/vmag/author/{id}", name="admin_vmag_author", defaults={"id" = ""})
     * @Template()
     *
     * @param Request $request
     *
     * @return Response
     */
    public function vmagAuthorAction(Request $request)
    {
        // check the logged in user is an admin
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            return $this->redirect($this->generateUrl('dashboard'));
        }
        $id = $request->get('id');

        $em     = $this->getDoctrine()->getManager();
        $author = new \App\Entity\Author();

        if ($id) {
            $author = $em->getRepository('App:Author')->find($id);
        }

        $form = $this->createForm(AuthorType::class, $author);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $this->addFlash('notice', 'Author Saved');

                $em->persist($author);
                $em->flush();

                return $this->redirect($this->generateUrl('admin_vmag_author', ['id' => $author->getId()]));
            }
        }

        return $this->render('Admin/vmagAuthor.html.twig', [
            'form'   => $form->createView(),
            'author' => $author,
        ]);
    }

    /**
     * List authors for vmag
     *
     * @Route("/admin/vmag/authors", name="admin_vmag_authors")
     * @Template()
     *
     * @param Request            $request
     * @param ContainerInterface $container
     *
     * @return Response
     */
    public function vmagAuthorListAction(Request $request, ContainerInterface $container)
    {
        // check the logged in user is an admin
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            return $this->redirect($this->generateUrl('dashboard'));
        }

        $em = $this->getDoctrine()->getManager();
        $q  = $em->getRepository('App:Author')->createQueryBuilder('a');
        $q->select('a');
        $q->addSelect('(select count(ar.id) FROM App:Article ar WHERE ar.author = a.id) article_count');
        $q->orderBy('a.created_at', 'DESC');

        $query = $q->getQuery();

        $paginator  = $container->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->get('page', 1)/*page number*/,
            20// limit per page
        );

        return $this->render('Admin/vmagAuthorList.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    /**
     * Get users json
     *
     * @Route("/admin/userJson", name="admin_user_json")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function userJsonAction(Request $request)
    {
        // check the logged in user is an admin
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            return $this->redirect($this->generateUrl('dashboard'));
        }

        $em = $this->getDoctrine()->getManager();

        $q = $em->getRepository('App:UserInfo')->createQueryBuilder('u');
        $q->where(
            $q->expr()->orX(
                    $q->expr()->like('u.email', ':searchTerm'),
                    $q->expr()->like('u.username', ':searchTerm'),
                    $q->expr()->like('u.display_name', ':searchTerm')
                )
        )
          ->setMaxResults(10)
          ->setParameter('searchTerm', '%' . $request->get('q') . '%');

        $query = $q->getQuery();

        $results = $query->execute();

        $users = [];
        foreach ($results as $user) {
            $users[] = [
                'id'   => $user->getId(),
                'name' => $user->getDisplayName(),
            ];
        }

        echo json_encode($users);
        exit;
    }

    /**
     * @Route("/admin/withdrawels", name="admin_withdrawels")
     * @Template()
     * @param Request $request
     * @return Response
     */
    public function adminWithdrawelsAction(Request $request)
    {

        // check the logged in user is an admin
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            $responseData = ['success' => false,
                'message'              => 'Invalid Access', ];
            return new Response(json_encode($responseData));
        }

        $em = $this->getDoctrine()->getManager();

        $withdrawels = $em->getRepository('App:UserWithdraw')
                    ->findBy([
                        'status' => 'PENDING',
                    ]);

        return $this->render('Admin/adminWithdrawels.html.twig', [
            'results' => $withdrawels
        ]);
    }

    /**
     * @Route("/admin/withdraws", name="admin_withdraws")
     * @Template()
     *
     * @param Request $request
     *
     * @return array|Response
     */
    public function adminWithdrawsAction(Request $request)
    {

        // check the logged in user is an admin
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            $responseData = ['success' => false,
                'message'              => 'Invalid Access', ];
            return new Response(json_encode($responseData));
        }

        $em = $this->getDoctrine()->getManager();

        $em = $this->getDoctrine()->getManager();
        $q  = $em->getRepository('App:UserWithdraw')->createQueryBuilder('w');
        $q->select('w, ui');
        $q->innerJoin('w.user_info', 'ui');
        $q->addOrderBy('w.created_at', 'DESC');

        $query = $q->getQuery();

        $paginator  = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $this->get('request')->query->get('page', 1)/*page number*/,
            20// limit per page
        );

        return $this->render('Admin/adminWithdraws.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    /**
     * @Route("/admin/cancelWithdrawel/{id}", name="admin_cancel_withdrawel")
     * @Template()
     * @param Request $request
     * @param $id
     * @return RedirectResponse|Response
     */
    public function adminCancelWithdrawelProcessAction(Request $request, $id)
    {

        // check the logged in user is an admin
        $user = $this->getUser();
        if (!$user->getIsAdmin()) {
            $responseData = ['success' => false,
                'message'              => 'Invalid Access', ];
            return new Response(json_encode($responseData));
        }

        $em = $this->getDoctrine()->getManager();

        $q = $em->getRepository('App:UserWithdraw')
                ->createQueryBuilder('uw')
                ->select('uw, ui')
                ->innerJoin('uw.user_info', 'ui')
                ->where('uw.id = :id')
                ->setParameter('id', $id);

        $withdrawel = $q->getQuery()->getSingleResult();

        $cancelForm = $this->createFormBuilder()
        ->add('message', TextareaType::class, [
            'label'    => 'Reason',
            'required' => false,
            'attr'     => [
                'class' => 'form-control',
                'rows'  => '2',
            ], ])
        ->getForm();
        if ($request->isMethod('POST')) {
            $cancelForm->handleRequest($request);

            $withdrawel->setStatus('DECLINED');
            $withdrawel->setStatusReason($cancelForm->get('message')->getData());

            $audit = new \App\Entity\AdminActionAudit();
            $audit->setUserInfo($withdrawel->getUserInfo());
            $audit->setActioner($this->getUser());
            $audit->setAction('cancel_withdraw_request');
            $audit->setNote($cancelForm->get('message')->getData());
            $em->persist($audit);
            $em->flush();
        }

        $this->addFlash('notice', 'Withdrawel Cancelled');
        return $this->redirect($this->generateUrl('admin_withdrawels'));
    }

    /**
     * @Route("/admin/withdrawel/{id}", name="admin_withdrawel")
     * @Template()
     *
     * @param Request $request
     * @param $id
     *
     * @return array|RedirectResponse|Response
     */
    public function adminWithdrawelProcessAction(Request $request, $id)
    {

        // check the logged in user is an admin
        $user = $this->getUser();
        if (!$user->getIsAdmin()) {
            $responseData = ['success' => false,
                'message'              => 'Invalid Access', ];
            return new Response(json_encode($responseData));
        }

        $em = $this->getDoctrine()->getManager();

        $q = $em->getRepository('App:UserWithdraw')
                ->createQueryBuilder('uw')
                ->select('uw, ui')
                ->innerJoin('uw.user_info', 'ui')
                ->where('uw.id = :id')
                ->setParameter('id', $id);

        $withdrawel     = $q->getQuery()->getSingleResult();
        $withdrawelUser = $withdrawel->getUserInfo();
        $errors         = [];

        $form = $this->createFormBuilder()
                ->add('message', TextareaType::class, [
                    'label'    => 'Message',
                    'required' => false,
                    'data'     => 'Vocalizr Wallet Withdraw',
                    'attr'     => [
                        'class' => 'form-control',
                        'rows'  => '2',
                    ], ])
                ->getForm();

        $cancelForm = $this->createFormBuilder()
                ->add('message', TextareaType::class, [
                    'label'    => 'Reason',
                    'required' => false,
                    'attr'     => [
                        'class' => 'form-control',
                        'rows'  => '2',
                    ], ])
                ->getForm();

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            // check the withdraw hasn't already been completed
            if ($withdrawel->getStatus() == 'COMPLETED') {
                $this->addFlash('notice', 'NOTE: Withdrawel Request was previously marked completed!');
                return $this->redirect($this->generateUrl('admin_withdrawels'));
            }

            // add entry to wallet transaction
            $walletTransaction = new \App\Entity\UserWalletTransaction();
            $walletTransaction->setUserInfo($withdrawelUser);
            $walletTransaction->setDescription('User Wallet Withdraw');
            $walletTransaction->setAmount(-$withdrawel->getAmount());
            $walletTransaction->setCurrency('USD');
            $em->persist($walletTransaction);

            // add admin audit entry
            $audit = new \App\Entity\AdminActionAudit();
            $audit->setUserInfo($withdrawelUser);
            $audit->setActioner($this->getUser());
            $audit->setAction('process_withdraw_request');
            $audit->setNote('Amount: ' . $withdrawel->getAmount());
            $em->persist($audit);

            // mark withdraw as COMPLETED
            $withdrawel->setCreatedAt(new \DateTime());
            $withdrawel->setStatus('COMPLETED');

            $em->flush();

            $this->addFlash('notice', 'Withdrawel Request has been completed successfully');

            return $this->redirect($this->generateUrl('admin_withdrawels'));
        }

        return ['errors' => $errors,
            'withdrawel' => $withdrawel,
            'form'       => $form->createView(),
            'cancelForm' => $cancelForm->createView(), ];
    }

    /**
     * @Route("/admin/marketplace", name="admin_marketplace")
     * @Template()
     *
     * @param Request $request
     *
     * @return array
     */
    public function marketplaceAction(Request $request)
    {
        // grab a list of marketplace items pending review
        $user = $this->getUser();
        if (!$user->getIsAdmin()) {
            throw $this->createNotFoundException('Page not found');
        }

        $em = $this->getDoctrine()->getManager();

        $q = $em->getRepository('App:MarketplaceItem')
                ->createQueryBuilder('mi')
                ->select('mi')
                ->where('mi.status = :review')
                ->setParameter('review', 'review')
                ->orderBy('mi.published_at', 'ASC')
                ->getQuery();

        $items = $q->execute();

        return [
            'items' => $items,
        ];
    }

    /**
     * @Route("/admin/marketplace/{uuid}", name="admin_marketplace_review")
     * @Template()
     *
     * @param Request $request
     * @param $uuid
     *
     * @return array
     */
    public function marketplaceReviewAction(Request $request, $uuid)
    {
        // grab a list of marketplace items pending review
        $user = $this->getUser();
        if (!$user->getIsAdmin()) {
            throw $this->createNotFoundException('Page not found');
        }
        $em = $this->getDoctrine()->getManager();

        $marketplaceItem = $em->getRepository('App:MarketplaceItem')->findOneBy(['uuid' => $uuid]);
        if (!$marketplaceItem) {
            throw $this->createNotFoundException('Item not found');
        }

        return [
            'item' => $marketplaceItem,
        ];
    }

    /**
     * @Route("/admin/marketplace/{uuid}/approve", name="admin_marketplace_approve")
     * @Template()
     *
     * @param Request $request
     * @param $uuid
     *
     * @return RedirectResponse
     */
    public function marketplaceApproveAction(Request $request, $uuid)
    {
        $user = $this->getUser();
        if (!$user->getIsAdmin()) {
            throw $this->createNotFoundException('Page not found');
        }
        $em = $this->getDoctrine()->getManager();

        $marketplaceItem = $em->getRepository('App:MarketplaceItem')->findOneBy(['uuid' => $uuid]);
        if (!$marketplaceItem) {
            throw $this->createNotFoundException('Page not found');
        }

        $marketplaceItem->setStatus('published');
        $em->flush();

        $dispatcher = $this->get('hip_mandrill.dispatcher');
        $message    = new Message();
        $message->setSubject('Vocalizr - Marketplace Item status updated');
        $message->setPreserveRecipients(false);
        $message->setTrackOpens(true)
                ->setTrackClicks(true);
        $message->addTo($marketplaceItem->getUserInfo()->getEmail());

        $body = $this->container->get('templating')->render('Mail/marketplaceItemApproved.html.twig', [
            'marketplaceItem' => $marketplaceItem,
        ]);
        $message->addGlobalMergeVar('BODY', $body);

        $dispatcher->send($message, 'default');

        $this->addFlash('notice', 'Marketplace Item has been approved');
        return $this->redirect($this->generateUrl('admin_marketplace'));
    }

    /**
     * @Route("/admin/marketplace/{uuid}/reject", name="admin_marketplace_reject")
     * @Template()
     */
    public function marketplaceRejectAction(Request $request, $uuid)
    {
        $user = $this->getUser();
        if (!$user->getIsAdmin()) {
            throw $this->createNotFoundException('Page not found');
        }
        $em = $this->getDoctrine()->getManager();

        $marketplaceItem = $em->getRepository('App:MarketplaceItem')->findOneBy(['uuid' => $uuid]);
        if (!$marketplaceItem) {
            throw $this->createNotFoundException('Page not found');
        }

        $form = $this->createFormBuilder($marketplaceItem)
                ->add('status_reason', TextareaType::class, [
                    'label'    => 'Reason',
                    'required' => false,
                    'attr'     => [
                        'class' => 'form-control',
                        'rows'  => '2',
                    ], ])
                ->getForm();

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            $marketplaceItem->setStatus('rejected');
            $marketplaceItem->setStatusReason($form->get('status_reason')->getData());
            $em->flush();

            $dispatcher = $this->get('hip_mandrill.dispatcher');
            $message    = new Message();
            $message->setPreserveRecipients(false);
            $message->setSubject('Vocalizr - Marketplace Item status updated');
            $message->setTrackOpens(true)
                    ->setTrackClicks(true);
            $message->addTo($marketplaceItem->getUserInfo()->getEmail());

            $body = $this->container->get('templating')->render('Mail/marketplaceItemRejected.html.twig', [
                'marketplaceItem' => $marketplaceItem,
            ]);
            $message->addGlobalMergeVar('BODY', $body);

            $dispatcher->send($message, 'default');

            $this->addFlash('notice', 'Marketplace Item has been rejected');
            return $this->redirect($this->generateUrl('admin_marketplace'));
        }

        return [
            'item' => $marketplaceItem,
            'form' => $form->createView(),
        ];
    }

    // ENGINE ROOM

    /**
     * Display list of orders
     *
     * @Route("/admin/engine", name="admin_engine")
     * @Template()
     *
     * @param Request            $request
     * @param ContainerInterface $container
     *
     * @return Response
     */
    public function engineAction(Request $request, ContainerInterface $container)
    {
        // check the logged in user is an admin
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            return $this->redirect($this->generateUrl('dashboard'));
        }

        $em = $this->getDoctrine()->getManager();
        $q  = $em->getRepository('App:EngineOrder')->createQueryBuilder('eo');
        $q->select('eo, ui, ep');
        $q->innerJoin('eo.user_info', 'ui');
        $q->innerJoin('eo.engine_product', 'ep');
        $q->addOrderBy('eo.status', 'DESC');
        $q->addOrderBy('eo.created_at', 'DESC');
        $q->where("eo.status != 'DRAFT'");

        $query = $q->getQuery();

        $paginator  = $container->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->get('page', 1)/*page number*/,
            20// limit per page
        );

        return $this->render('Admin/engine.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    /**
     * View order and assets
     *
     * @Route("/admin/engine/order/{uid}", name="admin_engine_order")
     * @Template()
     *
     * @param Request $request
     *
     * @return Response
     */
    public function engineOrderAction(Request $request, $uid)
    {
        // check the logged in user is an admin
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            return $this->redirect($this->generateUrl('dashboard'));
        }

        $em          = $this->getDoctrine()->getManager();
        $engineOrder = $em->getRepository('App:EngineOrder')->getOrderByUid($uid);

        if (!$engineOrder) {
            throw $this->createNotFoundException('Invalid order');
        }

        if ($request->getMethod() == 'POST') {
            $engineOrder->setStatus($request->get('status'));
            $em->flush();

            $request->query->set('notice', 'Changes saved');
        }

        return $this->render('Admin/engineOrder.html.twig', [
            'order' => $engineOrder,
        ]);
    }

    /**
     * Download engine order asset
     *
     * @Route("/admin/engine/order/{uid}/asset/{slug}", name="admin_engine_asset")
     */
    public function engineDownloadAssetAction(Request $request)
    {
        // check the logged in user is an admin
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            return $this->redirect($this->generateUrl('dashboard'));
        }

        $em = $this->getDoctrine()->getManager();

        $slug = $request->get('slug');

        // Get project asset by slug
        $asset = $em->getRepository('App:EngineOrderAsset')
                ->getBySlug($slug);

        if (!$asset) {
            throw $this->createNotFoundException('Invalid asset file');
        }

        // if dropbox link, redirect
        if ($asset->getDropboxLink()) {
            header('Location: ' . $asset->getDropboxLink());
            exit;
        }

        if (ini_get('zlib.output_compression')) {
            ini_set('zlib.output_compression', 'Off');
        }

        $file = $asset->getAbsolutePath();

        header('Content-Description: File Transfer');
        header('Content-Type: application/force-download');
        header('Content-Disposition: attachment; filename="' . $asset->getTitle() . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        ob_clean();
        flush();
        readfile($file);
        die;
    }

    /**
     * @param Request $request
     * @Route("/admin/subscriptions", name="admin_user_subscriptions")
     *
     * @return Response
     */
    public function adminUserSubscriptions(Request $request, UserInfoModel $userModel)
    {
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            return $this->redirect($this->generateUrl('dashboard'));
        }

        $object = new SubscriptionIdsCsvObject();
        $form   = $this->createForm(SubscriptionIdsCsvType::class, $object);
        /** @var EntityManager $em */
        $em            = $this->getDoctrine()->getManager();
        $messages      = [];
        $submitted     = false;
        $rowsCount     = 0;
        $deactivated   = 0;
        $subscriptions = [];

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                $submitted = true;
                $handle    = fopen($object->getFile()->getPathname(), 'r');
                $ids       = [];
                while (!feof($handle)) {
                    $data = fgetcsv($handle);
                    if (!$data || empty($data[0])) {
                        continue;
                    }

                    $ids[] = $data[0];
                    $rowsCount++;
                }
                if (!empty($ids)) {
                    $subscriptions = $em->getRepository('App:UserSubscription')->getSubscriptionsBySubIds($ids);
                }
            }
        }

        $messages['Rows found in CSV']   = $rowsCount;
        $messages['Found Subscriptions'] = count($subscriptions);
        foreach ($subscriptions as $subscription) {
            if ($subscription->getIsActive()) {
                $deactivated++;
            }
            $subscription->setIsActive(false);
            $user = $subscription->getUserInfo();
            $user->getUserSubscriptions();
        }

        $messages['Deactivated Subscriptions'] = $deactivated;


        if ($subscriptions) {
            $em->flush();

            $usersWithInvalidSubscription = $em->getRepository('App:UserInfo')->getUsersWithInvalidSubscription();

            $messages['PRO Statuses that has been revoked'] = count($usersWithInvalidSubscription);
            foreach ($usersWithInvalidSubscription as $user) {
                $user->setSubscriptionPlan(null);
            }

            $em->flush();
        }

        return $this->render('Admin/user_subscriptions.twig', [
            'form'      => $form->createView(),
            'submitted' => $submitted,
            'messages'  => $messages,
        ]);
    }

    /**
     * @Route("/admin/withdraw_email_lock", name="admin_withdraw_email_lock")
     *
     * @param Request $request
     * @return Response
     */
    public function adminWithdrawEmailLock(Request $request)
    {
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            return $this->redirect($this->generateUrl('dashboard'));
        }

        return $this->render('Admin/user_withdraw_email_lock.twig');
    }

    /**
     * @Route("/admin/withdraw_email_lock/list", name="admin_withdraw_email_lock_list")
     *
     * @param Request                $request
     * @param UserRestrictionService $restrictionService
     * @return JsonResponse
     */
    public function adminWithdrawEmailLockList(Request $request, UserRestrictionService $restrictionService)
    {
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            throw new AccessDeniedHttpException();
        }

//        $restrictionService = $this->get('vocalizr_app.user_restriction');
        $searchTerm = trim($request->get('search-term'));

        if ($searchTerm == '') {

            $responseData = [
                'success' => false,
                'message' => 'No search string defined'
            ];

            return new JsonResponse($responseData);
        }

        $users = $this->getDoctrine()->getManager()->getRepository('App:UserInfo')
            ->findUser($searchTerm);

        foreach ($users as $user) {
            if (!$user->getWithdrawEmail()) {
                $user->setWithdrawEmail(
                    $restrictionService->getWithdrawEmail($user)
                );
            }
        }

        return new JsonResponse([
            'success'    => true,
            'numResults' => count($users),
            'html'       => $this->renderView(
                'Admin/adminWithdrawEmailUserResults.html.twig',
                ['results' => $users]
            ),
        ]);
    }

    /**
     * @Route("/admin/withdraw_email_lock/change", name="admin_withdraw_email_change")
     * @param Request $request
     * @return JsonResponse
     */
    public function adminWithdrawEmailChange(Request $request)
    {
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            throw new AccessDeniedHttpException();
        }

        $userId = $request->get('id');
        if (!$userId) {
            throw new BadRequestHttpException('Field id is not specified');
        }

        $em = $this->getDoctrine()->getManager();

        $user = $em->getRepository('App:UserInfo')->find($userId);
        if (!$user) {
            throw new BadRequestHttpException('User not found');
        }

        $validator = $this->get('validator');
        $violations = $validator->validateValue($email = $request->get('email'), [
            new NotBlank(),
            new Email(),
        ]);

        if ($violations->count() > 0) {
            throw new BadRequestHttpException('Entered email is not valid.');
        }

        $user->setWithdrawEmail($email);

        $em->flush();

        return new JsonResponse([
            'success' => true,
        ]);
    }
}
