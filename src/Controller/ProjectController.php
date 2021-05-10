<?php

namespace App\Controller;

use App\Model\ProjectModel;
use App\Service\HelperService;
use App\Service\PayPalService;
use App\Service\ProjectPriceCalculator;
use Mpdf\Mpdf;
use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Slot\MandrillBundle\Message;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Exception\AlreadyBoundException;
use Symfony\Component\Form\Exception\FormException;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Process\ProcessBuilder;
use App\Entity\Project;
use App\Entity\ProjectAudio;
use App\Entity\ProjectBid;
use App\Entity\ProjectEscrow;
use App\Entity\SubscriptionPlan;
use App\Entity\UserAudio;
use App\Entity\UserInfo;
use App\Event\JustCreatedEvent;
use App\Form\Type\EditProjectType;
use App\Form\Type\LocationType;
use App\Form\Type\NewProjectGigType;
use App\Form\Type\ProjectBidType;
use App\Form\Type\PublishType;
use App\Model\HintModel;
use App\Repository\ProjectBidRepository;
use App\Repository\SubscriptionPlanRepository;
use Twig\Environment;

class ProjectController extends AbstractController
{
    /**
     * @Route("/start", name="project_start")
     * @Template()
     *
     * @param Request $request
     *
     * @return array|RedirectResponse
     */
    public function startAction(Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            return $this->redirect($this->generateUrl('project_new'));
        }

        return $this->render('Project/start.html.twig', []);
    }

    /**
     * @Route("/new/gig/{uuid}", name="project_new", defaults={"uuid" = ""})
     * @Template()
     *
     * @param Request $request
     * @param $uuid
     *
     * @return array|RedirectResponse
     *
     * @throws AlreadyBoundException
     * @throws FormException
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
                throw $this->createNotFoundException('Invalid gig');
            }

            if ($project->getProjectType() == Project::PROJECT_TYPE_CONTEST) {
                return $this->redirect($this->generateUrl('contest_new', ['uuid' => $project->getUuid()]));
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

        // new project form
        $english = $em->getRepository('App:Language')
                ->findOneByTitle('English');
        // Get budget options
        $ymlParser  = new \Symfony\Component\Yaml\Parser();
        $file       = $this->getParameter('kernel.project_dir') . '/config/packages/project.yml';
        $projectYml = $ymlParser->parse(file_get_contents($file));

        $form         = $this->createForm(NewProjectGigType::class, $project, [
                                                        'english' => $english,
                                                        'budget'  => $projectYml['budget']
                                                    ]);
        $form->handleRequest($request);
        $locationForm = $this->createForm(LocationType::class);
        $locationForm->handleRequest($request);

        /**
         * Handle owner saving functions
         * Before saving, check permission
         */
        if ($request->isMethod('POST')) {
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
                $budget          = $form->get('budget')->getData();
                list($min, $max) = explode('-', $budget);
                $project->setBudgetFrom($min);
                $project->setBudgetTo($max);

                $project->setUserInfo($user);
                $project->setProjectType(Project::PROJECT_TYPE_PAID);

                // Remove contact details
                $project->cleanDescription();

                // If new project and no audio, throw error
                if (!$project->getId() && !$request->get('audio_file')) {
                    $request->query->set('error', 'Audio file is required');
                    return $this->render(
                        'Project:new.html.twig',
                        [
                            'form'                => $form->createView(),
                            'locationForm'        => $locationForm->createView(),
                            'project'             => $project,
                            'defaultProjectAudio' => $defaultProjectAudio,
                        ]
                    );
                }

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
                        return $this->redirect($this->generateUrl('project_new', ['uuid' => $project->getUuid()]));
                    }

                    if ($defaultProjectAudio) {
                        $em->remove($defaultProjectAudio);
                    }
                    $em->flush();

                    $defaultProjectAudio = $projectAudio;

                    // Convert uploaded file to 112
                    $helper = $this->container->get('service.helper');
                    $cmd    = '--abr 112 ' . $projectAudio->getAbsolutePath() . ' ' . $projectAudio->getAbsolutePath();
                    $helper->execLame($cmd);
                }

                if ($request->get('save')) {
                    $this->get('session')->getFlashBag()->add('notice', 'Gig has been saved');
                    return $this->redirect($this->generateUrl('project_new', ['uuid' => $project->getUuid()]));
                }

                if ($request->get('next')) {
                    return $this->redirect($this->generateUrl('project_new_publish', ['uuid' => $project->getUuid()]));
                }
            } else {
                $request->query->set('error', 'Please fix the error(s) below');
            }
        } else {
            if ($project->getId()) {
                $budget = $project->getBudgetFrom() . '-' . $project->getBudgetTo();
                $form->get('budget')->setData($budget);
            }
        }

        return $this->render('Project/new.html.twig', [
            'project'             => $project,
            'defaultProjectAudio' => $defaultProjectAudio,
            'form'                => $form->createView(),
            'locationForm'        => $locationForm->createView(),
        ]);
    }

    /**
     * @Route("/hire/{username}", name="project_hire")
     * @Template()
     */
    public function newHireAction(Request $request, $username)
    {
        $user = $this->getUser();
        $em   = $this->getDoctrine()->getManager();

        // Get user
        $hireUser = $em->getRepository('App:UserInfo')->findOneBy([
            'username'  => $username,
            'is_active' => true,
        ]);
        if (!$hireUser) {
            throw $this->createNotFoundException('Invalid user');
        }

        if ($hireUser->getId() == $user->getId()) {
            throw $this->createNotFoundException('You cannot hire yourself :)');
        }

        // Make sure user isn't blocked
        $userBlock = $em->getRepository('App:UserBlock')
                ->findOneBy([
                    'block_user' => $user,
                    'user_info'  => $hireUser,
                ]);
        if ($userBlock) {
            throw $this->createNotFoundException('You are unable to hire this member');
        }

        $canHire = $this->get('vocalizr_app.user_restriction')->canHireNow();
        if (!$canHire) {
            if ($request->isXmlHttpRequest()) {
                return $this->render('include/panel/hire_now_panel.html.twig');
            } else {
                /** @var Session $session */
                $session = $request->getSession();
                $session->getFlashBag()->set('hire_now_modal', true);
                $redirectUrl = $request->headers->get('referrer', $this->generateUrl('vocalists'));
                return $this->redirect($redirectUrl);
            }
        }

        $projectRepo      = $em->getRepository('App:Project');
        $projectAudioRepo = $em->getRepository('App:ProjectAudio');

        $defaultProjectAudio = false;
        $project             = new Project();

        // new project form
        $english = $em->getRepository('App:Language')
                ->findOneByTitle('English');
        // Get budget options
        $ymlParser  = new \Symfony\Component\Yaml\Parser();
        $file       = $this->getParameter('kernel.project_dir') . '/config/packages/project.yml';
        $projectYml = $ymlParser->parse(file_get_contents($file));

        $form = $this->createForm(new NewProjectGigType($english, $projectYml['budget']), $project);

        /**
         * Handle owner saving functions
         * Before saving, check permission
         */
        if ($request->isMethod('POST')) {
            if ($hireUser->getIsProducer() && !$hireUser->getIsVocalist()) {
                $form->get('looking_for')->setData('producer');
            }
            if (!$hireUser->getIsProducer() && $hireUser->getIsVocalist()) {
                $form->get('looking_for')->setData('vocalist');
            }
            $form->bind($request);

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

            if ($form->isValid()) {
                $budget          = $form->get('budget')->getData();
                list($min, $max) = explode('-', $budget);
                $project->setBudgetFrom($min);
                $project->setBudgetTo($max);

                $project->setUserInfo($user);
                $project->setProjectType(Project::PROJECT_TYPE_PAID);
                $project->setPublishType(Project::PUBLISH_PRIVATE);
                $project->setHireUser($hireUser);

                // Set bids due 29 days from now
                $dt = new \DateTime();
                $dt->modify('+28 days');
                $dt->modify('+23 hours');
                $dt->modify('+59 minutes');
                $project->setBidsDue($dt);

                $project->setIsActive(true);
                $project->setPublishedAt(new \DateTime());

                // Remove contact details
                $project->cleanDescription();

                // If new project and no audio, throw error
                if (!$request->get('audio_file')) {
                    $request->query->set('error', 'Audio file is required');
                    return $this->render(
                        'Project:newHire.html.twig',
                        [
                            'form'                => $form->createView(),
                            'project'             => $project,
                            'defaultProjectAudio' => $defaultProjectAudio,
                            'hireUser'            => $hireUser,
                        ]
                    );
                }

                $em->persist($project);
                $em->flush();

                // Attempt to save file
                if ($request->get('audio_file')) {
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
                        return $this->redirect($this->generateUrl('project_hire', ['uuid' => $project->getUuid()]));
                    }

                    if ($defaultProjectAudio) {
                        $em->remove($defaultProjectAudio);
                    }
                    $em->flush();

                    $defaultProjectAudio = $projectAudio;

                    // Convert uploaded file to 112
                    $helper = $this->container->get('service.helper');
                    $cmd    = '--abr 112 ' . $projectAudio->getAbsolutePath() . ' ' . $projectAudio->getAbsolutePath();
                    $helper->execLame($cmd);
                }

                // Add project invite
                $projectInvite = new \App\Entity\ProjectInvite();
                $projectInvite->setUserInfo($hireUser);
                $projectInvite->setProject($project);
                $projectInvite->setHireNow(true);
                $em->persist($projectInvite);

                $em->flush();

                // Send hire now email
                $dispatcher = $this->get('hip_mandrill.dispatcher');
                $message    = new Message();
                $message
                        ->setTrackOpens(true)
                        ->setTrackClicks(true);
                $message->addTo($hireUser->getEmail());

                $templateName = 'projectHire';
                $subject      = 'A Vocalist wants to hire you!';
                if ($user->getIsProducer()) {
                    $subject = 'A Producer wants to hire you!';
                }

                $body = $this->container->get('templating')->render(
                    'Mail:projectHire.html.twig',
                    [
                        'hireUser' => $hireUser,
                        'project'  => $project,
                    ]
                );

                $message->setSubject($subject);
                $message->addGlobalMergeVar('BODY', $body);
                $dispatcher->send($message, 'default');

                $this->get('session')->getFlashBag()->add('notice', 'Your hire now request has been sent');
                return $this->redirect($this->generateUrl('project_view', ['uuid' => $project->getUuid()]));
            } else {
                $request->query->set('error', 'Please fix the error(s) below');
            }
        }

        return [
            'form'     => $form->createView(),
            'hireUser' => $hireUser,
        ];
    }

//     * @Secure(roles="ROLE_USER")
    /**
     * Publish newly created project
     * @IsGranted("ROLE_USER")
     * @Route("/new/gig/{uuid}/publish", name="project_new_publish")
     * @Template
     *
     * @param Request $request
     * @param string  $uuid
     */
    public function publishAction(Request $request, $uuid, ProjectPriceCalculator $calculator, PayPalService $payPalService)
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

        if ($project->getProjectType() == Project::PROJECT_TYPE_CONTEST) {
            return $this->redirect($this->generateUrl('contest_new', ['uuid' => $project->getUuid()]));
        }

        // If project is published already
        if ($project->getPublishedAt()) {
            return $this->redirect($this->generateUrl('project_view', ['uuid' => $project->getUuid()]));
        } elseif ($project->getPaymentStatus() !== Project::PAYMENT_STATUS_PENDING) {
            return $this->redirect($this->generateUrl('project_publish_confirm', ['uuid' => $project->getUuid()]));
        }

        // Check if logged in user created gig
        if ($project->getUserInfo()->getId() != $user->getId()) {
            throw $this->createNotFoundException('Permission denied');
        }

        // If gig has been awarded, redirect to studio
        if ($project->getProjectBid()) {
            return $this->redirect($this->generateUrl('project_studio', ['uuid' => $project->getUuid()]));
        }

        $form = $this->createForm(PublishType::class, $project);
        $form->handleRequest($request);
        // If post method, we are saving options form
        if ($request->getMethod() == 'POST') {
            if ($form->isSubmitted() && $form->isValid()) {
                if (!$form->get('publish_type')->getData()) {
                    $project->setPublishType(Project::PUBLISH_PUBLIC);
                }

//                $calculator = $this->get('vocalizr_app.project_price_calculator');

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

        return $this->render('Project/publish.html.twig', [
            'paypal'           => $payPalService,
            'project'          => $project,
            'form'             => $form->createView(),
            'subscriptionPlan' => $subscriptionPlan,
            'prices'           => $prices,
        ]);
    }

//     * @Secure(roles="ROLE_USER")
    /**
     * Confirm the publishing of a project
     *
     * @IsGranted("ROLE_USER")
     * @Route("/new/gig/{uuid}/publish/confirm", name="project_publish_confirm")
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

        if ($project->getProjectType() == Project::PROJECT_TYPE_CONTEST) {
            return $this->redirect($this->generateUrl('contest_publish_confirm', ['uuid' => $project->getUuid()]));
        }

        // Check if logged in user created gig
        if ($project->getUserInfo()->getId() != $user->getId()) {
            throw $this->createNotFoundException('Permission denied');
        }

        if ($project->getPublishedAt()) {
            return $this->redirect($this->generateUrl('project_view', ['uuid' => $project->getUuid()]));
        }

        // If gig has been awarded, redirect to studio
        if ($project->getProjectBid()) {
            return $this->redirect($this->generateUrl('project_studio', ['uuid' => $project->getUuid()]));
        }

        $subscriptionPlan = $em->getRepository('App:SubscriptionPlan')->getActiveSubscription($user->getId());

        if ($project->getPaymentStatus() === Project::PAYMENT_STATUS_PENDING) {
            $this->get('session')->getFlashBag()->add('error', 'Please wait a minute while we receive payment for a project.');
            return $this->redirect($this->generateUrl('contest_new_publish', ['uuid' => $uuid]));
        }

        if ($project->getPublishType() == Project::PUBLISH_PRIVATE) {
            $project->setShowInNews(false);
        }

        // Set bids due 29 days from now
        $dt = new \DateTime();
        $dt->modify('+28 days');
        $dt->modify('+23 hours');
        $dt->modify('+59 minutes');
        $project->setBidsDue($dt);

        // If featured
        if ($project->getFeatured()) {
            $project->setFeaturedAt(new \DateTime());
        }

        if ($project->getToFavorites() === true) {
            $this->notifyFavorites($project);
        }

        $project->setIsActive(true);
        $project->setPublishedAt(new \DateTime());

        $em->flush();

        $this->get('session')->getFlashBag()->add('notice', 'Your Gig has been successfully published');

        $dispatcher = new EventDispatcher();
        $dispatcher->dispatch(
            'contest_or_gig.just_created',
            new JustCreatedEvent(JustCreatedEvent::TYPE_GIG, $user, $project)
        );

        $this->get('session')->getFlashBag()->add('just_published', true);

        return $this->redirect($this->generateUrl('contest_view', ['uuid' => $uuid]));
    }

//     * @Secure(roles="ROLE_USER")
    /**
     * Cancel a project
     *
     * @IsGranted("ROLE_USER")
     * @Route("/new/cancel/{uuid}", name="project_new_cancel")
     *
     * @param Request $request
     * @param string  $uuid
     */
    public function newCancelAction(Request $request, $uuid)
    {
        $user = $this->getUser();
        $em   = $this->getDoctrine()->getManager();

        $projectRepo = $em->getRepository('App:Project');

        $project = $projectRepo->getProjectByUuid($uuid);

        if (!$project) {
            throw $this->createNotFoundException('Invalid project');
        }

        // Check if logged in user created gig
        if ($project->getUserInfo()->getId() != $user->getId()) {
            throw $this->createNotFoundException('Permission denied');
        }

        if ($project->getPublishedAt()) {
            throw $this->createNotFoundException('You cannot cancel a project that is already published');
        }

        $projectType = $project->getProjectType();

        // Remove project audio
        $audio = $em->getRepository('App:ProjectAudio')->findOneBy(['project' => $project]);
        if ($audio) {
            $em->remove($audio);
        }
        $em->remove($project);
        $em->flush();

        if ($projectType == Project::PROJECT_TYPE_CONTEST) {
            $this->get('session')->getFlashBag()->add('notice', 'Contest has been cancelled');
            return $this->redirect($this->generateUrl('contest_new'));
        } else {
            $this->get('session')->getFlashBag()->add('notice', 'Gig has been cancelled');
            return $this->redirect($this->generateUrl('project_new'));
        }
    }

//     * @Secure(roles="ROLE_USER")
    /**
     * Edit project
     *
     * @IsGranted("ROLE_USER")
     * @Route("/gig/{uuid}/edit", name="project_edit")
     * @Route("/gig/{uuid}/contract/delete/{contractSlug}", name="project_contract_delete")
     * @Template()
     */
    public function editAction(Request $request)
    {
        $this->request     = $request;
        $uuid              = $request->get('uuid', false);
        $user              = $this->user              = $this->getUser();
        $em                = $this->em                = $this->getDoctrine()->getManager();
        $projectAudioRepo  = $em->getRepository('App:ProjectAudio');
        $displayIntroModal = false;

        // Make sure project is valid
        if (!$uuid) {
            throw $this->createNotFoundException('Invalid gig');
        }

        $project = $this->project = $em->getRepository('App:Project')
                ->getProjectByUuid($uuid);

        if (!$project) {
            throw $this->createNotFoundException('Invalid gig');
        }

        if ($project->getProjectType() == Project::PROJECT_TYPE_CONTEST) {
            $this->redirect($this->generateUrl('contest_edit', ['uuid' => $project->getUuid()]));
        }

        // Check if logged in user created gig
        if ($project->getUserInfo()->getId() != $user->getId()) {
            throw $this->createNotFoundException('Permission denied');
        }

        // If gig has been awarded, redirect to studio
        if ($project->getProjectBid()) {
            return $this->redirect($this->generateUrl('project_studio', ['uuid' => $project->getUuid()]));
        }

        if (is_null($project->getUpdatedAt())) {
            $displayIntroModal = true;
            $project->setUpdatedAt(new \DateTime());
            $em->persist($project);
            $em->flush();
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
        $ymlParser  = new \Symfony\Component\Yaml\Parser();
        $file       = $this->getParameter('kernel.project_dir') . '/config/packages/project.yml';
        $projectYml = $ymlParser->parse(file_get_contents($file));

        $form         = $this->createForm(EditProjectType::class, $project, [
                                            'english' => $english,
                                            'budget'  => $projectYml['budget']
                                        ]);
        $locationForm = $this->createForm(LocationType::class);
        $form->handleRequest($request);
        $locationForm->handleRequest($request);

        /**
         * Handle owner saving functions
         * Before saving, check permission
         */
        if ($request->isMethod('POST') && $request->get('save')) {
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

                // Get budget and set correct fields
                $budget = $form->get('budget')->getData();
                if ($budget) {
                    list($min, $max) = explode('-', $budget);
                    $project->setBudgetFrom($min);
                    $project->setBudgetTo($max);
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

                //$patterns = array('<[\w.]+@[\w.]+>', '<\w{3,6}:(?:(?://)|(?:\\\\))[^\s]+>');
                //$matches = array('[email removed]', '[link removed]');
                //$newDesc = preg_replace($patterns, $matches, $project->getDescription());
                //$project->setDescription($newDesc);

                $em->persist($project);
                $em->flush();

                // Attempt to save file
                if ($request->get('audio_file')) {
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
                        return $this->redirect($this->generateUrl('contest_new', ['uuid' => $project->getUuid()]));
                    }

                    if ($defaultProjectAudio) {
                        $em->remove($defaultProjectAudio);
                    }
                    $em->flush();

                    $defaultProjectAudio = $projectAudio;
                }

                $request->query->set('notice', 'Changes Saved');
            } else {
                $request->query->set('error', 'Please fix the error(s) below');
            }
        } else {
            $form->get('budget')->setData($project->getBudgetFrom() . '-' . $project->getBudgetTo());
        }

        $filters = [];
        if ($request->get('filter')) {
            $filters['orderBy'] = [$request->get('filter'), $request->get('dir', 'desc')];
        }

        // Get bids & bid stats (total bids, avg bid amount)
        $bids                     = $em->getRepository('App:ProjectBid')->getProjectBids($project->getId(), $filters);
        $bidStats                 = $em->getRepository('App:ProjectBid')->getBidStats($project->getId());
        $bidStats['avgBidAmount'] = $bidStats['avgBidAmount'] / 100;

        $projectAwarded = $em->getRepository('App:ProjectBid')->isProjectAwarded($project->getId());

        $projectContracts = $em->getRepository('App:ProjectContract')->findBy([
            'project' => $project->getId(),
        ], [
            'created_at' => 'DESC',
        ]);

        $userBid = null;
        if ($user) {
            $userBid = $em->getRepository('App:ProjectBid')->findOneBy([
                'user_info' => $user->getId(),
                'project'   => $project->getId(),
            ]);
        }

        return $this->render('Project/edit.html.twig', [
            'form'                => $form->createView(),
            'locationForm'        => $locationForm->createView(),
            'project'             => $project,
            'projectAwarded'      => $projectAwarded,
            'projectContracts'    => $projectContracts,
            'displayIntroModal'   => $displayIntroModal,
            'defaultProjectAudio' => $defaultProjectAudio,
            'bidStats'            => $bidStats,
            'favoriteCount'       => $favoriteCount,
            'bids'                => $bids,
        ]);
    }

    /**
     * Show the widget that has information about the projects current status
     *
     * @Route("/gig/statusWidget/{uuid}", name="project_status_widget")
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param type                                      $uuid
     * @Template()
     */
    public function projectStatusWidgetAction(Request $request, $uuid, ProjectModel $projectModel)
    {
        $em   = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        /** @var ProjectBidRepository $bidRepo */
        $bidRepo = $em->getRepository('App:ProjectBid');

        // Make sure project is valid
        if (!$uuid) {
            throw $this->createNotFoundException('Invalid gig');
        }

        $project = $this->project = $em->getRepository('App:Project')
                ->getProjectByUuid($uuid);

        if (!$project) {
            throw $this->createNotFoundException('Invalid gig');
        }

        $projectAwarded = $bidRepo->isProjectAwarded($project->getId());

        // Get bids & bid stats (total bids, avg bid amount)
        $bids                     = $bidRepo->getProjectBids($project->getId());
        $bidStats                 = $bidRepo->getBidStats($project->getId());
        $bidStats['avgBidAmount'] = $bidStats['avgBidAmount'] / 100;

        $publishForm = $this->createFormBuilder($project)
            ->add('publish_type', ChoiceType::class, [
                'label'   => 'PUBLISHING OPTIONS',
                'choices' => [Project::PUBLISH_PUBLIC => ucwords(Project::PUBLISH_PUBLIC),
                    Project::PUBLISH_PRIVATE          => ucwords(Project::PUBLISH_PRIVATE), ],
                'expanded' => true,
                'multiple' => false,
                'data'     => Project::PUBLISH_PUBLIC,
            ])
            ->add('show_in_news', null, [
                'label'    => 'ANNOUNCE IN VOCALIZR FEED',
                'required' => false,
                'data'     => true,
            ])
            ->add('to_favorites', null, [
                'label'    => 'NOTIFY YOUR FAVORITES',
                'required' => false,
            ])
            ->add('restrict_to_preferences', null, [
                'label'    => 'RESTRICT TO GIG PREFERENCES',
                'required' => false,
            ])
            ->getForm()
        ;

        $userBid                = false;
        $userMatchesPreferences = [];
        $restrictBid            = false;
        // Check bid hasn't past, if not, do additional checks
        if ($project->getIsActive() && time() < $project->getBidsDue()->getTimestamp()) {
            if ($user) {
                // Check if project has restrictions
                $userMatchesPreferences = $this->userMeetProjectPreferences($project, $projectModel);
                if (count($userMatchesPreferences) > 0) {
                    $restrictBid = true;
                }

                // If bid isn't restricted and If user doesn't own gig, find out if they had bidded already
                if (!$restrictBid && $project->getUserInfo()->getId() != $user->getId()) {
                    $userBid = $bidRepo->findOneBy([
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
        $bidForm    = $this->createForm(ProjectBidType::class, $projectBid);

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
        ];

        // if ajax request return json response
        if ($request->isXmlHttpRequest()) {
            $jsonResponse = [
                'success' => true,
                'html'    => $this->renderView(
                    'Project/projectStatusWidget.html.twig',
                    $templateData
                ),
            ];
            return new Response(json_encode($jsonResponse));
        }

        return $this->render('Project/projectStatusWidget.html.twig', $templateData);
    }

    private function notifyFavorites(Project $project)
    {
        $processBuilder = new ProcessBuilder();
        $processBuilder
                ->setArguments([
                    'php app/console vocalizr:gig:invite:favorites',
                    '--projectId=' . $project->getId(),
                    '--userId=' . $this->getUser()->getId(),
                ])
                ->setWorkingDirectory(getcwd() . '../')
                ->setTimeout(1800) // 30 mins
        ;
        $process = $processBuilder->getProcess();
        $process->start();
    }

    /**
     * Download contract
     *
     * @Route("/gig/{uuid}/contract/{slug}", name="project_contract_download")
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function downloadContractAction(Request $request)
    {
        $em   = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        // Find project contract
        $projectContract = $em->getRepository('App:ProjectContract')
                ->findOneBy([
                    'slug' => $request->get('slug'),
                ]);
        if (!$projectContract) {
            throw $this->createNotFoundException('Invalid contract file');
        }

        if (ini_get('zlib.output_compression')) {
            ini_set('zlib.output_compression', 'Off');
        }

        $file = $projectContract->getAbsolutePath();

        header('Content-Description: File Transfer');
        header('Content-Type: application/force-download');
        header('Content-Disposition: attachment; filename="' . $projectContract->getTitle() . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        flush();
        readfile($file);
        die;
    }

    /**
     * View project
     *
     * @Route("/gig/{uuid}", name="project_view")
     * @Template()
     */
    public function viewAction(Request $request, HelperService $helper)
    {
        $uuid              = $request->get('uuid', false);
        $user              = $this->getUser();
        $em                = $this->getDoctrine()->getManager();
        $userAudioRepo     = $em->getRepository('App:UserAudio');
        $projectAudioRepo  = $em->getRepository('App:ProjectAudio');
        $displayIntroModal = false;
//        $helper            = $this->get('service.helper');

        // Make sure project is valid
        if (!$uuid) {
            throw $this->createNotFoundException('Invalid gig');
        }

        $project = $em->getRepository('App:Project')
            ->getProjectByUuid($uuid);

        if (!$project) {
            throw $this->createNotFoundException('Invalid gig');
        }

        if (!$project->getIsActive()) {
            throw $this->createNotFoundException('Gig no longer exists');
        }

        if ($project->getProjectType() == Project::PROJECT_TYPE_CONTEST) {
            return $this->redirect($this->generateUrl('contest_view', ['uuid' => $project->getUuid()]), 301);
        }

        // Make sure contest is published
        if (!$project->getPublishedAt()) {
            if ($project->getUserInfo()->getId() == $user->getId()) {
                return $this->redirect($this->generateUrl('project_new', ['uuid' => $project->getUuid()]));
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
        $bidForm    = $this->createForm(ProjectBidType::class, $projectBid);

        // Get bid stats (total bids, avg bid amount)
        $bidStats                 = $em->getRepository('App:ProjectBid')->getBidStats($project->getId());
        $bidStats['avgBidAmount'] = $bidStats['avgBidAmount'] / 100;

        if (!$request->get('filter')) {
            $request->query->set('filter', 'default');
            $request->query->set('dir', 'desc');
        }

        $filters       = [];
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
        $bids = $em->getRepository('App:ProjectBid')->getProjectBids($project->getId(), $filters);

        $projectAwarded = $em->getRepository('App:ProjectBid')->isProjectAwarded($project->getId());

        if ($this->getUser()) {
            $this->markProjectRead($project);
        }

        if ($project->getHireUser()) {
            if (!$this->getUser()) {
                throw $this->createNotFoundException('You are unable to view this gig');
            }
            if ($project->getUserInfo()->getId() != $user->getId() && $project->getHireUser()->getId() != $user->getId()) {
                throw $this->createNotFoundException('You are unable to view this gig');
            }
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
        }

        $subscriptionPlan = $em->getRepository('App:SubscriptionPlan')->getFeaturePrices();

        return $this->render('Project/view.html.twig', [
            'project'             => $project,
            'projectAwarded'      => $projectAwarded,
            'defaultProjectAudio' => $defaultProjectAudio,
            'bidStats'            => $bidStats,
            'bidForm'             => $bidForm->createView(),
            'bids'                => $bids,
            'activeThreads'       => $activeThreads,
            'hiddenBids'          => $hiddenBids,
            'shortlistBids'       => $shortlistBids,
            'userBid'             => $userBid,
            'subscriptionPlan'    => $subscriptionPlan,
        ]);
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
    private function userMeetProjectPreferences($project, $projectModel)
    {
        return $projectModel->getUserMeetProjectPreferencesArray($this->getUser(), $project);
    }

    /**
     * Bid action
     * Display bid form in modal
     *
     * @Route("/gig/{uuid}/bid", name="project_bid")
     * @Template()
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param ProjectModel $projectModel
     * @return RedirectResponse|Response
     */
    public function bidAction(Request $request, ProjectModel $projectModel)
    {
        $uuid = $request->get('uuid', false);
        /** @var UserInfo $user */
        $user          = $this->getUser();
        $em            = $this->getDoctrine()->getManager();
        $userAudioRepo = $em->getRepository('App:UserAudio');
        $helper        = $this->get('service.helper');

        // Make sure user is logged in
        $securityContext = $this->container->get('security.context');
        if (!$securityContext->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->forward('App:Default:error', [
                'error' => 'You need to be logged in to place a bid',
            ]);
        }

        // See if user has audio tracks
        $userAudioTracks = $this->get('vocalizr_app.model.user_audio')->getUserAudios($user, true);

        $project = $em->getRepository('App:Project')
                ->getProjectByUuid($uuid);

        if (!$project) {
            return $this->forward('App:Default:error', [
                'error' => 'Invalid gig',
            ]);
        }
        if ($project->getProRequired() && !$user->getIsCertified()) {
            return $this->forward('App:Default:error', [
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

        $resubmit               = $request->get('resubmit', 0);
        $userBid                = false;
        $userMatchesPreferences = [];
        $restrictBid            = false;
        // Check bid hasn't past, if not, do additional checks
        if ($resubmit || time() < $project->getBidsDue()->getTimestamp()) {
            // Check if project has restrictions
            $userMatchesPreferences = $this->userMeetProjectPreferences($project, $projectModel);
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
            $userBid = true;
        }

        // If user has already bidded or time has past
        if ($userBid && !$resubmit) {
            // If time has past
            if (time() > $project->getBidsDue()->getTimestamp()) {
                return $this->forward('App:Default:error', [
                    'error' => 'Sorry. Bidding on this gig is now closed',
                ]);
            }

            return $this->forward('App:Default:error', [
                'error' => 'You have already submitted a bid for this gig',
            ]);
        }

        // If bid is restricted and user wasn't invited
        $projectInvite = $em->getRepository('App:ProjectInvite')
                ->findOneBy([
                    'project'   => $project->getId(),
                    'user_info' => $user->getId(),
                ]);
        if ($restrictBid && !$projectInvite) {
            return $this->forward('App:Default:error', [
                'error' => 'Sorry. Bidding on this gig is restricted to users who meet the gig requirements.',
            ]);
        }

        // Project bid form
        $projectBid = new ProjectBid();
        $bidForm    = $this->createForm(ProjectBidType::class, $projectBid);
        $bidForm->handleRequest($request);
        // If they are placing a bid, and havent already done so
        if ($request->getMethod() == 'POST') {

            if ($project->getProjectType() == 'paid') {
                $amount      = $request->get($bidForm->getName());
                $amountValue = $amount['amount'];

                // Strip any comma's
                $amountValue = $helper->getMoneyAsInt($amountValue);
            } else {
                $amountValue = 0;
            }

            // Amount needs to be 20 or more
            if ($amountValue >= 20 || $project->getProjectType() == 'collaboration') {
                $projectBid->setUserInfo($user);
                $projectBid->setProject($project);

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
                }

                // If they choose profile track.
                if ($preferredTrackSlug = $request->get('selected_user_track')) {
                    $track = $em->getRepository(UserAudio::class)->findOneBySlugAndUser($preferredTrackSlug, $user);
                    if ($track) {
                        $projectBid->setTitleAudio($track);
                    } else {
                        $uploadError = true;
                    }
                }

                // If there was an upload error, display error
                if (isset($uploadError)) {
                    $this->get('session')->getFlashBag()->add('error', 'There was a problem while processing your bid. Please try again.');
                    return $this->redirect($this->generateUrl('project_view', ['uuid' => $project->getUuid()]));
                }

                // Save Bid
                if ($project->getProjectType() == 'paid') {
                    $amount      = $request->get($bidForm->getName());
                    $amountValue = $amount['amount'];
                    $projectBid->setAmount($helper->getMoneyAsInt($amountValue) * 100); // Set amount to cents
                } else {
                    $projectBid->setAmount(0);
                }

                // if resubmitting a bid... remove the old one
                if ($resubmit) {
                    $em->remove($userBid);
                    $em->flush();
                }
                $em->persist($projectBid);
                $em->flush();

                if ($resubmit) {
                    return new Response(json_encode([
                        'success' => true,
                        'amount'  => (number_format($projectBid->getAmount() / 100, 2)),
                    ]));
                } else {
                    $this->get('session')->getFlashBag()->add('notice', 'Successfully placed your bid.');
                    if ($request->isXmlHttpRequest()) {
                        return new Response(json_encode([
                            'success'  => true,
                            'redirect' => $this->generateUrl('project_view', ['uuid' => $project->getUuid()]),
                        ]));
                    }
                    return $this->redirect($this->generateUrl('project_view', ['uuid' => $project->getUuid()]));
                }
            } else {
                if ($request->isXmlHttpRequest()) {
                    return new Response(json_encode([
                        'success'  => true,
                        'redirect' => $this->generateUrl('project_view', ['uuid' => $project->getUuid()]),
                    ]));
                }
                $this->get('session')->getFlashBag()->add('error', 'Your bid amount needs to be more than $20');
                return $this->redirect($this->generateUrl('project_view', ['uuid' => $project->getUuid()]));
            }
        }

        // Get bid stats (total bids, avg bid amount)
        $bidStats                 = $em->getRepository('App:ProjectBid')->getBidStats($project->getId());
        $bidStats['avgBidAmount'] = $bidStats['avgBidAmount'] / 100;

        return $this->render('Project/bid.html.twig', [
            'resubmit'  => $resubmit,
            'project'   => $project,
            'bidForm'   => $bidForm->createView(),
            'bidStats'  => $bidStats,
            'userAudio' => $userAudioTracks,
        ]);
    }

    /**
     * Award Project Action
     *
     * @Template()
     * @Route("/project/{uuid}/award/{bidUuid}", name="project_award")
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function awardAction(Request $request)
    {
        $uuid    = $request->get('uuid', false);
        $bidUuid = $request->get('bidUuid', false);
        /** @var UserInfo $user */
        $user   = $this->getUser();
        $em     = $this->getDoctrine()->getManager();
        $paypal = $this->get('service.paypal');

        // Make sure project is valid
        if (!$uuid) {
            throw $this->createNotFoundException('Invalid gig');
        }

        $project = $em->getRepository('App:Project')
                ->getProjectByUuid($uuid);

        if (!$project) {
            throw $this->createNotFoundException('Invalid gig');
        }

        // Make sure they are the owner of the project
        if ($project->getUserInfo()->getId() != $user->getId()) {
            throw $this->createNotFoundException('Permission Denied');
        }

        // Make sure project hasn't already been awarded
        if ($project->getProjectBid()) {
            throw $this->createNotFoundException('Gig has already been awarded');
        }
        if ($projectAwarded = $em->getRepository('App:ProjectBid')->isProjectAwarded($project->getId())) {
            throw $this->createNotFoundException('Gig has already been awarded');
        }

        if (!$user->isVerified()) {
            /** @var Session $session */
            $session = $request->getSession();
            $session->getFlashBag()->add('error', 'You must verify your identity before awarding gig');

            return $this->redirect($this->generateUrl('project_view', ['uuid' => $project->getUuid()]));
        }

        /**
         * @var ProjectBid $projectBid - Get project bid they are wanting to award.
         */
        $projectBid = $em->getRepository('App:ProjectBid')->getProjectBidByUuid($bidUuid);
        if (!$projectBid) {
            throw $this->createNotFoundException('Cannot award gig to invalid bidder');
        }

        // Get bid stats (total bids, avg bid amount)
        $bidStats                 = $em->getRepository('App:ProjectBid')->getBidStats($project->getId());
        $bidStats['avgBidAmount'] = $bidStats['avgBidAmount'] / 100;

        // Get subscription plan user is on
        $helper           = $this->container->get('service.helper');
        $subscriptionPlan = $em->getRepository('App:SubscriptionPlan')->getActiveSubscription($user->getId());

        $form = $this->createFormBuilder($project, [
            'validation_groups' => ['sign_agreement', 'employer_sign'],
        ])->add('employer_name', null, [
                'label'       => 'Sign Agreement',
                'attr'        => ['class' => 'form-control'],
        ])->getForm();

        if ($request->getMethod() == 'POST' && $request->get('award')) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $projectBid = $projectBid[0];
                // Make sure user has enough in wallet for bid
                if ($user->getWallet() < $projectBid->getAmount()) {
                    $this->get('session')->getFlashBag()->add('error', 'You do not have enough in your wallet to award this gig');
                    return $this->redirect($this->generateUrl('project_award', [
                        'uuid'    => $project->getUuid(),
                        'bidUuid' => $projectBid->getUuid(),
                    ]));
                }

                $projectBid->setFlag('A'); // Awarded flag
                $project->setProjectBid($projectBid);
                $project->setEmployeeUserInfo($projectBid->getUserInfo());
                $project->setAwardedAt(new \DateTime());
                $em->flush();

                $helper           = $this->container->get('service.helper');
                $subscriptionPlan = $em->getRepository('App:SubscriptionPlan')->getActiveSubscription($user->getId());

                $fee = 0;
                if ($subscriptionPlan['project_percent_added']) {
                    $fee = $helper->getPricePercent($projectBid->getAmount(), $subscriptionPlan['project_percent_added'], false);
                }

                // Create project escrow row
                $escrow = new ProjectEscrow();
                $escrow->setUserInfo($user);
                $escrow->setProject($projectBid->getProject());
                $escrow->setProjectBid($projectBid);
                $escrow->setAmount($projectBid->getAmount());
                $escrow->setFee($fee);
                $em->persist($escrow);
                $project->setProjectEscrow($escrow);
                $em->persist($project);

                // Create user wallet transaction
                // For only the amount of the project bid
                $uwt = new \App\Entity\UserWalletTransaction();
                $uwt->setUserInfo($user);
                $uwt->setAmount('-' . $projectBid->getAmount()); // Minus amount
                $uwt->setCurrency($this->container->getParameter('default_currency'));
                $description = 'Escrow payment to {username} for gig {project}';
                $uwt->setDescription($description);
                $data = [
                    'username'     => $projectBid->getUserInfo()->getUsername(),
                    'projectTitle' => $project->getTitle(),
                    'projectUuid'  => $project->getUuid(),
                ];
                $uwt->setData(json_encode($data));
                $em->persist($uwt);

                if ($fee) {
                    // Create user wallet transaction
                    // Take admin fee for project
                    $uwt = new \App\Entity\UserWalletTransaction();
                    $uwt->setUserInfo($user);
                    $uwt->setAmount('-' . $fee); // Minus amount
                    $uwt->setCurrency($this->container->getParameter('default_currency'));
                    $description = 'Gig fee taken for {project}';
                    $uwt->setDescription($description);
                    $data = [
                        'projectTitle' => $project->getTitle(),
                        'projectUuid'  => $project->getUuid(),
                    ];
                    $uwt->setData(json_encode($data));
                    $em->persist($uwt);
                }

                $em->flush();

                // Send email to bidder saying payment has been made and awarded
                $dispatcher = $this->get('hip_mandrill.dispatcher');
                $message    = new Message();
                $message
                        ->addTo($projectBid->getUserInfo()->getEmail())
                        ->addGlobalMergeVar('USER', $projectBid->getUserInfo()->getUsernameOrFirstName())
                        ->addGlobalMergeVar('BIDAMOUNT', number_format(($projectBid->getAmount() / 100)), 2)
                        ->addGlobalMergeVar('PROJECTTITLE', $project->getTitle())
                        ->addGlobalMergeVar('PROJECTURL', $this->generateUrl('project_studio', [
                            'uuid' => $project->getUuid(),
                        ], true))
                        ->setTrackOpens(true)
                        ->setTrackClicks(true);

                if ($project->getProjectType() == Project::PROJECT_TYPE_PAID) {
                    $dispatcher->send($message, 'project-bid-awarded');
                } else {
                    $dispatcher->send($message, 'collaboration-bid-awarded');
                }

                // close all chats for this project
                $q = $em->getRepository('App:MessageThread')->createQueryBuilder('mt');
                $q->update()
                        ->set('mt.is_open', ':false')
                        ->where('mt.project = :project');
                $params = [
                    ':false'   => '0',
                    ':project' => $project,
                ];
                $q->setParameters($params);
                $q->getQuery()->execute();

                $this->get('session')->getFlashBag()->add('notice', 'Successfully awarded project to <strong>' . $projectBid->getUserInfo()->getDisplayName() . '</strong>');

                if (!$referer = $this->get('session')->get('sc_referer', false)) {
                    $referer = $this->generateUrl('project_studio', ['uuid' => $project->getUuid()]);
                }

                return $this->redirect($referer);
            } else {
                $request->query->set('error', 'Please fix error below');
            }
        } else {
            if (!$user->isSubscribed()) {
                $fee = $helper->getPricePercent($projectBid[0]->getAmount(), $subscriptionPlan['project_percent_added'], false);
                $this->get('vocalizr_app.model.hint')->setSession(HintModel::HINT_GIG, $fee);
            }
        }

        return $this->render('Project/award.html.twig', [
            'project'          => $project,
            'bid'              => $projectBid,
            'bidStats'         => $bidStats,
            'subscriptionPlan' => $subscriptionPlan,
            'paypal'           => $paypal,
            'form'             => $form->createView(),
        ]);
    }

    /**
     * Award Confirm Project
     *
     * @Route("/project/{uuid}/award/confirm", name="project_award_confirm")
     * @Template()
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function awardConfirmAction(Request $request)
    {
        $uuid    = $request->get('uuid', false);
        $bidUuid = $request->get('bid', false);
        $user    = $this->getUser();
        $em      = $this->getDoctrine()->getManager();

        // Make sure project is valid
        if (!$uuid || !$bidUuid) {
            throw $this->createNotFoundException('Invalid gig');
        }

        $project = $em->getRepository('App:Project')
                ->getProjectByUuid($uuid);

        if (!$project) {
            throw $this->createNotFoundException('Invalid gig');
        }

        if ($project->getUserInfo()->getId() != $user->getId()) {
            throw $this->createNotFoundException('Permission Denied');
        }

        // Make sure project hasn't already been awarded
        if ($project->getProjectBid()) {
            throw $this->createNotFoundException('Gig has already been awarded');
        }
        if ($projectAwarded = $em->getRepository('App:ProjectBid')->isProjectAwarded($project->getId())) {
            throw $this->createNotFoundException('Gig has already been awarded');
        }

        // Get project bid they are wanting to award
        $projectBid = $em->getRepository('App:ProjectBid')->getProjectBidByProjectId($bidUuid, $project->getId());
        if (!$projectBid) {
            throw $this->createNotFoundException('Cannot award gig to invalid bid');
        }

        $subscriptionPlan = $em->getRepository('App:SubscriptionPlan')->getActiveSubscription($user->getId());
        $paypal           = $this->get('service.paypal');

        return $this->render('Project/awardConfirm.html.twig', [
            'project'          => $project,
            'bid'              => $projectBid,
            'subscriptionPlan' => $subscriptionPlan,
            'paypal'           => $paypal,
        ]);
    }

    /**
     * Load JSON data about project
     *
     * @Route("/gig/{uuid}/json/{action}", name="project_json")
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function jsonAction(Request $request)
    {
        $uuid = $request->get('uuid', false);
        $user = $this->getUser();
        $em   = $this->getDoctrine()->getManager();

        $response = new \Symfony\Component\HttpFoundation\JsonResponse();

        // Make sure project is valid
        if (!$uuid) {
            $response->setData([
                'error' => 'Invalid gig',
            ]);
            return $response;
        }

        $project = $em->getRepository('App:Project')
                ->getProjectByUuid($uuid);

        if (!$project) {
            $response->setData([
                'error' => 'Invalid gig',
            ]);
            return $response;
        }

        if ($project->getUserInfo()->getId() != $user->getId() &&
                $project->getProjectBid()->getUserInfo()->getId() != $user->getId()) {
            $response->setData([
                'error' => 'Permission Denied',
            ]);
            return $response;
        }

        if ($request->get('action') == 'loadLyrics') {
            $id            = $request->get('id');
            $projectLyrics = $em->getRepository('App:ProjectLyrics')->find($id);
            $response->setData([
                'lyrics' => $projectLyrics->getLyrics(),
            ]);
            return $response;
        }
    }

    /**
     * Delete user bid request
     *
     * @Route("/gig/{uuid}/bid/delete/{bidUuid}", name="gig_bid_delete")
     */
    public function deleteBidAction(Request $request)
    {
        $em   = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        // Get project bid by uuid and by current logged in user
        // This makes sure that someone else cannot delete other bids
        $projectBid = $em->getRepository('App:ProjectBid')
                ->findOneBy(['uuid' => $request->get('bidUuid'), 'user_info' => $user]);

        if (!$projectBid) {
            throw $this->createNotFoundException('Invalid bid');
        }

        // Make sure bids flag has not been awarded or decline
        if (!is_null($projectBid->getFlag())) {
            throw $this->createNotFoundException('You cannot remove a bid that has been awarded or declined');
        }

        $em->remove($projectBid);
        $em->flush();

        $this->get('session')->getFlashBag()->add('notice', 'Your bid for gig <strong>' . $projectBid->getProject()->getTitle() . '</strong> has been deleted');

        if (!$referer = $request->headers->get('referer')) {
            $referer = $this->generateUrl('projects');
        }
        return $this->redirect($referer);
    }

    /**
     * Invite user to gig
     *
     * @Route("/gig/{uuid}/invite/{username}", name="project_invite")
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function inviteUserAction(Request $request)
    {
        $em   = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        // See if project exists and is valid
        // - project hasn't been awarded
        // - project isn't completed
        // - bids due hasn't past
        $q = $em->getRepository('App:Project')
                ->createQueryBuilder('p')
                ->where('p.uuid = :uuid AND p.project_bid IS NULL')
                ->andWhere('p.is_complete = 0 AND p.bids_due >= :now');
        $params = [
            ':uuid' => $request->get('uuid'),
            ':now'  => date('Y-m-d H:i:s'),
        ];
        $q->setParameters($params);
        $project = $q->getQuery()->getOneOrNullResult();

        if (!$project) {
            return new JsonResponse(['error' => 'Invalid gig']);
        }

        // Make sure user exists and is active
        $userInfo = $em->getRepository('App:UserInfo')
                ->findOneBy([
                    'username'  => $request->get('username'),
                    'is_active' => true,
                ]);

        if (!$userInfo) {
            return new JsonResponse(['error' => 'You cannot invite an invalid user']);
        }

        // Make sure user hasn't been blocked
        $userBlock = $em->getRepository('App:UserBlock')
                ->findOneBy([
                    'block_user' => $user,
                    'user_info'  => $userInfo,
                ]);
        if ($userBlock) {
            return new JsonResponse(['error' => 'You are unable to invite this member to your gig']);
        }

        // Make sure they haven't been invited already
        $projectInvite = $em->getRepository('App:ProjectInvite')
                ->findOneBy([
                    'project'   => $project->getId(),
                    'user_info' => $userInfo->getId(),
                ]);

        if ($projectInvite) {
            return new JsonResponse(['error' => 'already-invited']);
        }

        $projectInvite = new \App\Entity\ProjectInvite();
        $projectInvite->setProject($project);
        $projectInvite->setUserInfo($userInfo);
        $em->persist($projectInvite);
        $em->flush();

        // Get user preference for the person who was just invited
        if ($userPref = $userInfo->getUserPref()) {
            $sendEmail = $userPref->getEmailProjectInvites();
        } else {
            $sendEmail = \App\Entity\UserPref::DEFAULT_EMAIL_PROJECT_INVITES;
        }

        if ($sendEmail) {
            $dispatcher = $this->get('hip_mandrill.dispatcher');

            $message = new Message();
            $message->setSubject('Gig Invitation to "' . $project->getTitle() . '"');
            if ($project->getProjectType() == Project::PROJECT_TYPE_CONTEST) {
                $message->setSubject('Contest Invitation to "' . $project->getTitle() . '"');
            }
            $message->setFromEmail('noreply@vocalizr.com');
            $message->setFromName('Vocalizr');
            $message
                    ->setTrackOpens(true)
                    ->setTrackClicks(true);

            $message->addTo($userInfo->getEmail());
            if ($project->getProjectType() == 'contest') {
                $body = $this->container->get('templating')->render('App:Mail:contestInvite.html.twig', [
                    'userInfo' => $userInfo,
                    'project'  => $project,
                ]);
            } else {
                $body = $this->container->get('templating')->render('App:Mail:projectInvite.html.twig', [
                    'userInfo' => $userInfo,
                    'project'  => $project,
                ]);
            }
            $message->addGlobalMergeVar('BODY', $body);
            $dispatcher->send($message, 'default');
        }

        return new JsonResponse(['success' => true]);
    }

    /**
     * Generate Button for Inviting a member to a gig
     *
     * @Template()
     */
    public function inviteToGigButtonAction($userInfo, $projects = null, $hasProjects = true)
    {
        if (!$hasProjects) {
            return [
                'projects' => false,
            ];
        }

        $em   = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        // Get projects that are not bidded on yet
        if (!$projects) {
            $projects = $em->getRepository('App:Project')
                    ->getProjectsToInvite($user->getId(), $userInfo->getId());
        }

        return [
            'userInfo' => $userInfo,
            'projects' => $projects,
        ];
    }

//     * @Secure(roles="ROLE_USER")
    /**
     * Stream audio for asset
     * @IsGranted("ROLE_USER")
     *
     * @Route("/gig/{uuid}/asset/{slug}", name="gig_asset_stream")
     */
    public function audioProjectAssetAction(Request $request)
    {
        $user = $this->getUser();
        if (!$user) {
            throw $this->createNotFoundException();
        }

        $em     = $this->getDoctrine()->getManager();
        $helper = $this->get('service.helper');

        // Get audio based on slug
        if (!$projectAsset = $em->getRepository('App:ProjectAsset')->findOneBy(['slug' => $request->get('slug')])) {
            throw $this->createNotFoundException();
        }

        $project = $projectAsset->getProject();

        if ($project->getUserInfo()->getId() != $user->getId() &&
                $project->getProjectBid()->getUserInfo()->getId() != $user->getId()) {
            throw $this->createNotFoundException('Permission denied');
        }

        $file = $projectAsset->getAbsolutePreviewPath();

        // Does the file exist?
        if (!file_exists($file)) {
            throw $this->createNotFoundException();
        }

        $helper->streamAudio($file, $request);
    }

    /**
     * Play bid audio from directory
     *
     * @Route("/bid/audio/{filename}", name="bid_audio_stream")
     */
    public function audioProjectBidAction(Request $request)
    {
        $em     = $this->getDoctrine()->getManager();
        $helper = $this->get('service.helper');

        // Get audio based on path
        if (!$projectBid = $em->getRepository('App:ProjectBid')->findOneBy(['path' => $request->get('filename')])) {
            throw $this->createNotFoundException();
        }
        $audioFile = $projectBid->getAbsolutePath();

        // Does the file exist?
        if (!file_exists($audioFile)) {
            throw $this->createNotFoundException();
        }

        // redirect to actual file
        header('Location: /a/project/' . $projectBid->getProject()->getId() . '/bids/' . $projectBid->getPath());
        exit;

        $helper->streamAudio($audioFile);
    }

    /**
     * Stream project audio for audio player
     *
     * @Route("/gig/{uuid}/audio/{slug}", name="project_audio")
     */
    public function audioAction(Request $request)
    {
        $uuid    = $request->get('uuid');
        $em      = $this->getDoctrine()->getManager();
        $user    = $this->getUser();;
        $helper  = $this->get('service.helper');

        $project = $em->getRepository('App:Project')
                ->findOneByUuid($uuid);

        if (!$project) {
            throw $this->createNotFoundException('Invalid gig');
        }

        // Get user audio by project id and audio slug, and make sure it's not a project audio file
        $projectAudio = $em->getRepository('App:ProjectAudio')->findOneBy(['slug' => $request->get('slug')]);

        if (!$projectAudio) {
            throw $this->createNotFoundException('Audio file not found 1');
        }

        $file = $projectAudio->getAbsolutePath();
        if (!file_exists($file)) {
            throw $this->createNotFoundException('Audio file not found 2');
        }

        // redirect to actual file
        header('Location: /a/project/' . $projectAudio->getId() . '/' . $projectAudio->getPath());
        exit;
    }

    /**
     * @Template()
     */
    public function ownerBidsAction($bids, $project, $hiddenBids, $shortlistBids, $projectAwarded, $freePlan)
    {
        return $this->render('Project/ownerBids.html.twig', [
            'bids'           => $bids,
            'project'        => $project,
            'shortlistBids'  => $shortlistBids,
            'hiddenBids'     => $hiddenBids,
            'projectAwarded' => $projectAwarded,
            'freePlan'       => $freePlan,
        ]);
    }

    // HELPER FUNCTIONS

    private function markProjectRead($project)
    {
        $em = $this->getDoctrine()->getManager();

        if ($project->getUserInfo() == $this->getUser()) {
            $project->setEmployerReadAt(new \DateTime());
            $lastActivity = $project->getLastActivity();
            if (count($lastActivity) > 0 && $this->getUser() == $project->getUserInfo() && $lastActivity['name'] == 'new bid') {
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

        // Check for notifications for project
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

    /**
     * @Route("/gig/{uuid}/agreement/{type}", defaults={"type" = "gig"}, name="project_agreement")
     * @Template()
     */
    public function downloadAgreementTemplateAction(Request $request, Environment $twig)
    {
        $container = $this->container;
        $user      = $this->getUser();
        $rootDir   = $this->getParameter('kernel.project_dir');

        if ($request->get('type') == 'gig') {
            $type  = 'gig';
            $title = 'GIG AGREEMENT';
        } else {
            $title = 'CONTEST AGREEMENT';
            $type  = 'contest';
        }

        $header = $this->render('Pdf/header.html.twig', [
            'title' => $title,
        ]);
        $footer = $this->render('Pdf/footer.html.twig', []);

        $content = null;
        if ($request->get('type') == 'gig') {
            $content = $this->render('Project/agreement.html.twig', []);
        } elseif ($request->get('type') == 'contest') {
            $content = $this->render('Contest/agreement.html.twig', []);
        }
        $css = realpath($rootDir . '/public/css/pdf.css');

        $mpdf = new Mpdf(['', 'A4', '', '', 0, 0, 30, 35, 0, 10]);
        $mpdf->setHTMLHeader($header);
        $mpdf->setHTMLFooter($footer);
        $mpdf->WriteHTML(file_get_contents($css), 1);
        $mpdf->WriteHTML($content, 2);
        $mpdf->Output('agreement-' . $type . '-template.pdf', 'D');
        exit;
    }

    /**
     * @Template()
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

        return $this->render('Project/agreement.html.twig', [
            'project'                  => $project,
            'projectBid'               => $projectBid,
            'subscriptionPlan'         => $subscriptionPlan,
            'employeeSubscriptionPlan' => $employeeSubscriptionPlan,
        ]);
    }

    /**
     * @Template()
     */
    public function collabAgreementAction($project)
    {
        $user = $this->getUser();
        $em   = $this->getDoctrine()->getManager();

        return $this->render('Project/collabAgreement.html.twig', [
            'project' => $project,
        ]);
    }

    /**
     * @Route("/gig/{uuid}/delete", name="project_delete")
     */
    public function deleteGigAction(Request $request, $uuid)
    {
        $user = $this->getUser();

        /** @var EntityManager $em */
        $em = $this->getDoctrine()->getManager();

        $projectRepo = $em->getRepository('App:Project');

        if (!$user || !$user->getIsAdmin()) {
            return $this->redirect($this->generateUrl('dashboard'));
        }

        $project = $projectRepo->getProjectByUuid($uuid);
        if (!$project) {
            die("Gig doesn't exist");
        }

        $project->setIsActive(false);
        $em->persist($project);

        // Find vocalizr activity
        $projectActivities = $em->getRepository('App:VocalizrActivity')->findBy([
            'project' => $project,
        ]);

        // Delete any messages related to the gig.
        $em->getRepository('App:MessageThread')->deleteThreadsForGig($project);

        foreach ($projectActivities as $projectActivity) {
            $em->remove($projectActivity);
        }
        $em->flush();

        return $this->redirect($this->generateUrl('project_view', ['uuid' => $uuid]));
    }

    /**
     * @Route("/gig/{uuid}/audiobrief", name="project_audiobrief")
     */
    public function audioBriefAction(Request $request)
    {
        $em          = $this->getDoctrine()->getManager();
        $uuid        = $request->get('uuid');
        $projectRepo = $em->getRepository('App:Project');

        $project = $projectRepo->getProjectByUuid($uuid);
        if (!$project) {
            return $this->createNotFoundException('Project does not exist');
        }

        $project->setAudioBriefClick($project->getAudioBriefClick() + 1);
        $em->persist($project);

        $em->flush();

        return $this->redirect($project->getAudioBrief());
    }

//     * @Secure(roles="ROLE_USER")
    /**
     * @Route("/project/{uuid}/shortlist/{bidUuid}", name="project_shortlist_bid")
     * @IsGranted("ROLE_USER")
     */
    public function shortlistBidAction($uuid, $bidUuid)
    {
        $user = $this->getUser();
        $em   = $this->getDoctrine()->getManager();

        // Get project bid
        $projectBid = $em->getRepository('App:ProjectBid')
                ->findOneBy(['uuid' => $bidUuid]);

        if (!$projectBid) {
            return new JsonResponse(['error' => 'Invalid entry']);
        }

        $project = $projectBid->getProject();

        // Make sure they are the owner
        if ($project->getUserInfo()->getId() != $user->getId()) {
            return new JsonResponse(['error' => 'You are not the owner of this project']);
        }

        $shortlist = $projectBid->getShortlist();

        // If it is, set false
        if ($shortlist) {
            $projectBid->setShortlist(false);
            $result = ['success' => 'Removed from shortlist', 'result' => false];
        } else {
            $projectBid->setShortlist(true);

            $result = ['sucess' => 'Added to shortlist', 'result' => true];
        }

        $em->flush();

        return new JsonResponse($result);
    }

//     * @Secure(roles="ROLE_USER")
    /**
     * @Route("/project/{uuid}/hide/{bidUuid}", name="project_hide_bid")
     * @IsGranted("ROLE_USER")
     */
    public function hideBidAction($uuid, $bidUuid)
    {
        $user = $this->getUser();
        $em   = $this->getDoctrine()->getManager();

        // Get project bid
        $projectBid = $em->getRepository('App:ProjectBid')
                ->findOneBy(['uuid' => $bidUuid]);

        if (!$projectBid) {
            return new JsonResponse(['error' => 'Invalid entry']);
        }

        $project = $projectBid->getProject();

        // Make sure they are the owner
        if ($project->getUserInfo()->getId() != $user->getId()) {
            return new JsonResponse(['error' => 'You are not the owner of this project']);
        }

        // If it exists, delete entry
        if (!$projectBid->getHidden()) {
            // If project shortlisted, remove from list
            $projectBid->setShortlist(false);

            $projectBid->setHidden(true);
            $result = ['success' => 'Bid has been hidden', 'result' => false];
        } else {
            $projectBid->setHidden(false);
            $result = ['sucess' => 'Bid is unhidden', 'result' => true];
        }

        $em->flush();

        return new JsonResponse($result);
    }
}
