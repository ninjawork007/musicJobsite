<?php

namespace Vocalizr\AppBundle\Controller;

use Doctrine\ORM\EntityManager;
use Hip\MandrillBundle\Message;
use JMS\SecurityExtraBundle\Annotation\Secure;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Vocalizr\AppBundle\Entity\Project;
use Vocalizr\AppBundle\Entity\ProjectAsset;
use Vocalizr\AppBundle\Entity\ProjectAudio;
use Vocalizr\AppBundle\Entity\ProjectBid;
use Vocalizr\AppBundle\Entity\ProjectDispute;

use Vocalizr\AppBundle\Entity\UserInfo;
use Vocalizr\AppBundle\Entity\UserWalletTransaction;

use Vocalizr\AppBundle\Exception\CommonException;

// Forms
use Vocalizr\AppBundle\Form\Type\ProjectDisputeType;
use Vocalizr\AppBundle\Form\Type\ProjectLyricType;
use Vocalizr\AppBundle\Form\Type\UserReviewType;
use Vocalizr\AppBundle\Repository\SubscriptionPlanRepository;
use Vocalizr\AppBundle\Service\MandrillService;

/**
 * Class ProjectStudioController
 *
 * @property ProjectDispute|null disputeAccepted
 *
 * @package Vocalizr\AppBundle\Controller
 */
class ProjectStudioController extends Controller
{
    /**
     * @var Project|null - current project.
     */
    private $project;

    /**
     * @var ProjectBid|null - projectBid property of current project.
     */
    private $projectBid;

    /**
     * @var EntityManager|null
     */
    private $em;

    /**
     * @var UserInfo|null - currently logged in user.
     */
    private $user;

    /**
     * uuid of project.
     *
     * @var false|string|null - uuid of current project.
     */
    private $uuid;

    /**
     * @var Session|null
     */
    private $session;

    /**
     * @var Request|null
     */
    private $request;

    /**
     * @Route("/gig/{uuid}/studio", name="project_studio_old")
     */
    public function redirectAction(Request $request, $uuid)
    {
        return $this->redirect($this->generateUrl('project_studio', ['uuid' => $uuid]));
    }

    /**
     * Project studio
     * Only viewable once project has been awarded
     *
     * @Secure(roles="ROLE_USER")
     * @Route("/studio/{uuid}", name="project_studio")
     * @Route("/studio/{uuid}/asset/{assetSlug}", name="project_studio_asset")
     * @Route("/studio/{uuid}/asset/{assetDeleteSlug}/delete", name="project_studio_asset_delete")
     *
     * @Template()
     *
     * @param Request $request
     */
    public function indexAction(Request $request)
    {
        $this->request    = $request;
        $this->session    = $request->getSession();
        $uuid             = $this->uuid             = $request->get('uuid', false);
        $user             = $this->user             = $this->getUser();
        $em               = $this->em               = $this->getDoctrine()->getManager();
        $projectAudioRepo = $em->getRepository('VocalizrAppBundle:ProjectAudio');

        $userReviewForm = $this->createForm(new UserReviewType());

        // Make sure project is valid
        if (!$uuid) {
            throw $this->createNotFoundException('Invalid gig');
        }

        $project = $this->project = $em->getRepository('VocalizrAppBundle:Project')
                    ->getProjectByUuid($uuid);

        if (!$project) {
            throw $this->createNotFoundException('Invalid gig');
        }

        if (!$projectBid = $project->getProjectBid()) {
            throw $this->createNotFoundException('Cannot access studio until gig has been awarded and accepted');
        }
        $this->projectBid = $projectBid;

        // Only the owner of the gig can see this gig or
        // the person who won the project
        if ($project->getUserInfo()->getId() != $user->getId() &&
                $project->getProjectBid()->getUserInfo()->getId() != $user->getId()) {
            throw $this->createNotFoundException('Permission denied');
        }

        // Check to see if employee has signed
        if (!$project->getEmployeeName() && $user->getId() == $project->getEmployeeUserInfo()->getId()) {
            $form = $this->createFormBuilder($project, [
                'validation_groups' => ['sign_agreement', 'employee_sign'],
            ])->add('employee_name', null, [
                'label'       => 'Sign Agreement',
                'attr'        => ['class' => 'form-control'],
                'constraints' => [
                    new \Symfony\Component\Validator\Constraints\NotBlank([
                        'message' => 'Required',
                    ]),
                ],
            ])->getForm();

            if ($request->get('sign_agreement')) {
                $form->bind($request);
                if ($form->isValid()) {
                    $em->flush();

                    $this->generateAgreement($project);

                    $agreementOwnerName = 'Vocalizr Agreement for ' . $project->getTitle() . ' with ' . $projectBid->getUserInfo()->getUsername();
                    $agreementUserName  = 'Vocalizr Agreement for ' . $project->getTitle() . ' with ' . $project->getUserInfo()->getUsername();

                    // Send emails to both parties
                    $dispatcher = $this->get('hip_mandrill.dispatcher');
                    $message    = new Message();
                    $message
                        ->addGlobalMergeVar('PROJECTTITLE', $project->getTitle())
                        ->addGlobalMergeVar('PROJECTURL', $this->generateUrl('project_studio', [
                            'uuid' => $project->getUuid(),
                        ], true))
                        ->setTrackOpens(true)
                        ->setTrackClicks(true)
                    ;

                    $messageToOwner = clone $message;

                    $messageToOwner
                        ->addTo($project->getUserInfo()->getEmail())
                        ->addMergeVar($project->getUserInfo()->getEmail(), 'USER', $project->getUserInfo()->getUsernameOrFirstName())
                        ->addAttachmentFromPath($project->getAbsolutePdfPath(), 'application/pdf', $agreementOwnerName . '.pdf')
                    ;

                    $message
                        ->addTo($projectBid->getUserInfo()->getEmail())
                        ->addMergeVar($projectBid->getUserInfo()->getEmail(), 'USER', $projectBid->getUserInfo()->getUsernameOrFirstName())
                        ->addAttachmentFromPath($project->getAbsolutePdfPath(), 'application/pdf', $agreementUserName . '.pdf')
                    ;


                    $dispatcher->send($message, 'gig-agreement');
                    $dispatcher->send($messageToOwner, 'gig-agreement');


                    return $this->redirect($this->generateUrl('project_studio', ['uuid' => $project->getUuid()]));
                }
            }

            return $this->render('VocalizrAppBundle:ProjectStudio:signAgreement.html.twig', [
                'project'    => $project,
                'projectBid' => $projectBid,
                'form'       => $form->createView(),
            ]);
        }

        $projectDisputeForm = $this->projectDisputeForm = $this->createForm(new ProjectDisputeType($user, $projectBid));

        // Work out other parties user info
        if ($project->isOwner($user)) {
            $otherUserInfo = $project->getBidderUser();
        } else {
            $otherUserInfo = $project->getUserInfo();
        }
        $this->otherUserInfo = $this->employeeUserInfo = $otherUserInfo;

        // Get project featured audio
        $defaultProjectAudio = $projectAudioRepo->findBy([
            'project' => $project->getId(),
            'flag'    => ProjectAudio::FLAG_MASTER,
        ], ['created_at' => 'DESC']);

        if ($defaultProjectAudio) {
            $defaultProjectAudio = $defaultProjectAudio[0];
        } else {
            $defaultProjectAudio = $projectAudioRepo->findOneBy([
                'project' => $project->getId(),
                'flag'    => ProjectAudio::FLAG_FEATURED,
            ]);
        }

        // Get audio latest employee upload
        $employeeAudio = $projectAudioRepo->findOneBy([
            'project' => $project->getId(),
            'flag'    => ProjectAudio::FLAG_WORKING,
        ]);

        // Make all unread feed items as read if action is done by other party
        $q = $em->getRepository('VocalizrAppBundle:ProjectFeed')
                ->updateFeedItemsAsRead($project->getId(), $this->otherUserInfo->getId());

        // Edit lyrics form
        $lyricForm = $this->createForm(new ProjectLyricType(), $project);

        // If user is bidder, remove ontime field from form
        if ($project->getBidderUser()->getId() == $user->getId()) {
            $userReviewForm->remove('on_time');
        }

        // If project is compeleted, see if other party has had a review
        $userReview = false;
        if ($project->getIsComplete()) {
            $userReview = $em->getRepository('VocalizrAppBundle:UserReview')->findOneBy([
                'project'   => $project->getId(),
                'user_info' => $otherUserInfo->getId(),
            ]);
        }

        /**
         * @var ProjectDispute[] $projectDisputes - Get project disputes
         */
        $projectDisputes = $em->getRepository('VocalizrAppBundle:ProjectDispute')
            ->getDisputesByProject($project->getId());

        $disputeActive   = false;
        $disputeAccepted = null;
        // Get project disputes for user that hasn't been responded to.
        foreach ($projectDisputes as $dispute) {
            if (is_null($dispute->getAccepted())) {
                $disputeActive = $dispute;
                break;
            }
        }

        foreach ($projectDisputes as $dispute) {
            if ($dispute->getAccepted()) {
                $disputeAccepted = $dispute;
            }
        }
        $this->disputeAccepted = $disputeAccepted;

        $canReview = !$userReview && $project->getIsComplete() && (!$this->disputeAccepted);

        if ($request->getMethod() == 'POST') {
            // If they have posted a comment
            if ($request->get('comment') || $request->get('audio_file')) {
                // If file exists, this means they have uploaded audio
                if ($request->get('audio_file')) {
                    // Attempt to save file
                    $commentAudio = $projectAudioRepo
                            ->saveUploadedFile($project->getId(), $user->getId(), $request->get('audio_title'), $request->get('audio_file'), ProjectAudio::FLAG_COMMENT);

                    if (!$commentAudio) {
                        $audioError = true;
                    }
                }

                if (!isset($audioError)) {
                    $projectComment = new \Vocalizr\AppBundle\Entity\ProjectComment();
                    $projectComment->setProject($project);
                    if (isset($commentAudio)) {
                        $projectComment->setProjectAudio($commentAudio);
                    }
                    $projectComment->setFrom($user);
                    $projectComment->setContent($request->get('comment'));

                    $em->persist($projectComment);

                    $em->flush();

                    return $this->forward('VocalizrAppBundle:ProjectStudio:feed', [
                        'uuid' => $project->getUuid(),
                    ], [
                        'lastFeedId' => $request->get('lastFeedId'),
                    ]);
                } else {
                    $request->query->set('error', 'There was a problem while uploading your audio file. Please try again.');
                }
            }

            // If saving lyrics
            if ($request->get('save_lyrics')) {
                $lyricForm->bind($request);

                if ($lyricForm->isValid()) {
                    $data = $lyricForm->getData();
                    $em->persist($data);
                    $em->flush();

                    // Add to lyric history
                    $projectLyrics = new \Vocalizr\AppBundle\Entity\ProjectLyrics();
                    $projectLyrics->setUserInfo($user);
                    $projectLyrics->setProject($project);
                    $projectLyrics->setLyrics($data->getLyrics());

                    $em->persist($projectLyrics);
                    $em->flush();

                    $this->get('session')->getFlashBag()->add('notice', 'Lyrics saved');
                } else {
                    $this->get('session')->getFlashBag()->add('error', 'Could not save lyrics');
                }

                return $this->redirect($this->generateUrl('project_studio', ['uuid' => $project->getUuid()]));
            }

            // Prompting bidder to upload assets
            if ($request->get('prompt_assets') && !$project->getPromptAssets()) {
                $project->setPromptAssets(true);
                $em->persist($project);
                $em->flush();

                // Send mail to prompt user
                $dispatcher = $this->get('hip_mandrill.dispatcher');
                $message    = new Message();
                $message
                    ->addTo($projectBid->getUserInfo()->getEmail())
                    ->addGlobalMergeVar('USER', $projectBid->getUserInfo()->getUsernameOrFirstName())
                    ->addGlobalMergeVar('PROJECTOWNER', $user->getUsername())
                    ->addGlobalMergeVar('PROJECTTITLE', $project->getTitle())
                    ->addGlobalMergeVar('PROJECTURL', $this->generateUrl('project_studio', [
                        'uuid' => $project->getUuid(),
                    ], true))
                    ->setTrackOpens(true)
                    ->setTrackClicks(true);

                $dispatcher->send($message, 'project-prompt-assets');

                $request->query->set('notice', $projectBid->getUserInfo()->getUsername() . ' has been prompted to upload their assets');
            }

            // SUBMIT DISPUTE
            if ($request->get('dispute')) {
                $this->_studioActionSubmitDispute();
            }

            // DISPUTE RESPONSE
            // Accept or decline dispute amount
            if ($request->get('dispute_response') && $request->get('dispute_id')) {
                $this->_studioActionDisputeResponse();
            }

            // UPLOAD ASSETS
            if ($request->get('submit_assets')) {
                $assetsUploaded = $this->_studioActionSubmitAssets();
                $this->session->getFlashBag()->set('assets_uploaded', $assetsUploaded);
                return $this->redirect(
                    $this->generateUrl($request->get('_route'), ['uuid' => $uuid])
                );
            }

            // RELEASE ESCROW
            if ($request->get('release_escrow')) {
                $this->_studioActionReleaseEscrow();
            }

            // USER REVIEW SAVE
            // Only submit if they haven't had a review yet
            if ($request->get('review') && $canReview) {
                $userReviewForm->bindRequest($request);
                if ($userReviewForm->isValid()) {
                    if ($project->isOwner($user)) {
                        $userInfo = $project->getBidderUser();
                    } else {
                        $userInfo = $project->getUserInfo();
                    }

                    $data = $userReviewForm->getData();
                    $data->setUserInfo($userInfo);
                    $data->setProject($project);
                    $data->setReviewedBy($user); // Logged in user
                    $em->persist($data);
                    $em->flush();

                    $userReview = $data;

                    // Check to see if the reviewer has got one back yet
                    $otherUserReview = $em->getRepository('VocalizrAppBundle:UserReview')->findOneBy([
                        'project'   => $project->getId(),
                        'user_info' => $this->getUser(),
                    ]);

                    // Email user of review
                    $body = $this->renderView('VocalizrAppBundle:Mail:reviewNotifyUser.html.twig', [
                        'userInfo'        => $userInfo,
                        'project'         => $project,
                        'userReview'      => $data,
                        'otherUserReview' => $otherUserReview,
                    ]);
                    $dispatcher = $this->get('hip_mandrill.dispatcher');
                    $message    = new Message();
                    $message
                        ->addTo($userInfo->getEmail())
                        ->setSubject('You got a review!')
                        ->addGlobalMergeVar('BODY', $body)
                        ->setTrackOpens(true)
                        ->setTrackClicks(true);

                    $dispatcher->send($message, 'default');

                    $request->query->set('notice', 'Thank you for reviewing ' . $userInfo->getDisplayName());
                }
            }
        }

        // DOWNLOAD ASSET
        if ($request->get('assetSlug')) {
            $this->_studioActionDownloadAsset();
        }

        // DELETE ASSET
        if ($request->get('assetDeleteSlug')) {
            $this->_studioActionDeleteAsset();
        }

        // Get project feed
        $projectFeed = $em->getRepository('VocalizrAppBundle:ProjectFeed')
                ->getFeed($project->getId());

        $feedAudios = $em->getRepository('VocalizrAppBundle:ProjectAudio')->getFeedAudio(
            $projectFeed,
            $project->getId()
        );

        // Get lyrics history
        $projectLyrics = $em->getRepository('VocalizrAppBundle:ProjectLyrics')
                ->getLyrics($project->getId());

        // Get project assets
        $projectAssets = $em->getRepository('VocalizrAppBundle:ProjectAsset')
                ->getByProjectId($project->getId());

        // If project is complete, get other users voice tags
        $userTags = [];
        if ($project->getIsComplete() && $otherUserInfo->getIsVocalist()) {
            $userTags['voiceTag'] = $em->getRepository('VocalizrAppBundle:UserVoiceTag')
                    ->getByUserJoinVotedUser($otherUserInfo->getId(), $user->getId());
            $userTags['vocalStyle'] = $em->getRepository('VocalizrAppBundle:UserVocalStyle')
                    ->getByUserJoinVotedUser($otherUserInfo->getId(), $user->getId());
            $userTags['vocalCharacteristic'] = $em->getRepository('VocalizrAppBundle:UserVocalCharacteristic')
                    ->getByUserJoinVotedUser($otherUserInfo->getId(), $user->getId());
        }

        $this->markProjectRead($project);

        return $this->render('@VocalizrApp/ProjectStudio/index.html.twig', [
            'project'             => $project,
            'defaultProjectAudio' => $defaultProjectAudio,
            'employeeAudio'       => $employeeAudio,
            'projectFeed'         => $projectFeed,
            'projectLyrics'       => $projectLyrics,
            'projectAssets'       => $projectAssets,
            'projectDisputes'     => $projectDisputes,
            'userReview'          => $userReview,
            'otherUserInfo'       => $otherUserInfo,
            'userReviewForm'      => $userReviewForm->createView(),
            'projectDisputeForm'  => $projectDisputeForm->createView(),
            'lyricForm'           => $lyricForm->createView(),
            'userTags'            => $userTags,
            'disputeActive'       => $disputeActive,
            'disputeAccepted'     => $this->disputeAccepted,
            'canReview'           => $canReview,
            'feedAudios'          => $feedAudios,
        ]);
    }

    /**
     * Studio Action
     * Submit dispute
     */
    public function _studioActionSubmitDispute()
    {
        // Get current dispute if one exists
        $disputeActive = $this->em->getRepository('VocalizrAppBundle:ProjectDispute')->findOneBy(['project' => $this->project, 'accepted' => null]);

        // Make sure there are no other disputes
        if ($disputeActive) {
            $this->request->query->set('error', 'There is already a dispute active that needs to be responded to');
            return false;
        }

        $projectDisputeForm = $this->projectDisputeForm;

        $post = $this->request->get($projectDisputeForm->getName());

        $helper         = $this->get('service.helper');
        $post['amount'] = $helper->getMoneyAsInt($post['amount']);

        $projectDisputeForm->bind($post);

        if ($projectDisputeForm->isValid()) {
            $data = $projectDisputeForm->getData();

            // Convert amount into cents
            $data->setAmount($data->getAmount() * 100);

            $data->setUserInfo($this->otherUserInfo);
            $data->setFromUserInfo($this->user);
            $data->setProject($this->project);
            $this->em->persist($data);
            $this->em->flush();

            $dispute = $data;

            // Notify other party
            $dispatcher = $this->get('hip_mandrill.dispatcher');
            $message    = new Message();
            $message
                ->addTo($dispute->getUserInfo()->getEmail())
                ->addGlobalMergeVar('USER', $dispute->getUserInfo()->getUsernameOrFirstName())
                ->addGlobalMergeVar('DISPUTEUSER', $dispute->getFromUserInfo()->getUsername())
                ->addGlobalMergeVar('AMOUNT', number_format(($dispute->getAmount() / 100), 2))
                ->addGlobalMergeVar('REASON', nl2br($dispute->getReason()))
                ->addGlobalMergeVar('PROJECTTITLE', $this->project->getTitle())
                ->addGlobalMergeVar('PROJECTURL', $this->generateUrl('project_studio', [
                    'uuid' => $this->project->getUuid(),
                ], true))
                ->setTrackOpens(true)
                ->setTrackClicks(true);

            $dispatcher->send($message, 'project-dispute-sent');

            $this->request->query->set('notice', 'Your negotiation has been submitted');

            $this->disputeAccepted = $dispute;
        } else {
            $this->request->query->set('error', 'Please fix the error(s) below');
        }
    }

    /**
     * Studio Action
     * Dispute Response
     */
    public function _studioActionDisputeResponse()
    {
        $request = $this->request;
        $helper  = $this->get('service.helper');
        $mailer  = $this->get('service.mail');
        $em      = $this->em;

        if (!in_array($request->get('dispute_response'), ['accept', 'decline'])) {
            return false;
        }

        // Get dispute
        // Make sure it's for this project, and meant for this user to respond to
        $dispute = $this->em->getRepository('VocalizrAppBundle:ProjectDispute')
                ->findOneBy([
                    'id'        => $request->get('dispute_id'),
                    'project'   => $this->project->getId(),
                    'user_info' => $this->user->getId(),
                ]);

        // If they decline
        if ($request->get('dispute_response') == 'decline') {
            $dispute->setAccepted(false);
            $em->persist($dispute);
            $em->flush();

            // Notify the person who sent the dispute
            $dispatcher = $this->get('hip_mandrill.dispatcher');
            $message    = new Message();
            $message
                ->addTo($dispute->getFromUserInfo()->getEmail())
                ->addGlobalMergeVar('USER', $dispute->getFromUserInfo()->getUsernameOrFirstName())
                ->addGlobalMergeVar('DISPUTEUSER', $dispute->getUserInfo()->getUsername())
                ->addGlobalMergeVar('AMOUNT', number_format(($dispute->getAmount() / 100), 2))
                ->addGlobalMergeVar('PROJECTTITLE', $this->project->getTitle())
                ->addGlobalMergeVar('PROJECTURL', $this->generateUrl('project_studio', [
                    'uuid' => $this->project->getUuid(),
                ], true))
                ->setTrackOpens(true)
                ->setTrackClicks(true);

            $dispatcher->send($message, 'project-dispute-amount-declined');

            $this->request->query->set('notice', 'The selected dispute amount was declined');
            return true;
        }

        if ($request->get('dispute_response') == 'accept') {
            $dispute->setAccepted(true);

            if ($dispute->getAmount() == 0) {
                $this->project->setFullyRefunded(true);
            }

            // Get project escrow
            $escrow = $this->project->getProjectEscrow();

            // Work out percent used for fee
            $feePercent = ($escrow->getFee() / $escrow->getAmount()) * 100;

            // Work out new project fee in cents
            $newProjectFee = $helper->getPricePercent($dispute->getAmount(), $feePercent, false);

            // Work out refund amount
            $refundProjectFee    = $escrow->getFee() - $newProjectFee;
            $refundProjectAmount = $escrow->getAmount() - $dispute->getAmount();

            // Update project escrow with new amount
            $escrow->setFee($newProjectFee);
            $escrow->setAmount($dispute->getAmount());
            $em->persist($escrow);

            $this->disputeAccepted = $dispute;

            // Create wallet transactions for refund to project owner
            $uwt = new UserWalletTransaction();
            $uwt->setUserInfo($this->project->getUserInfo());
            $uwt->setAmount($refundProjectAmount);
            $uwt->setCurrency($this->container->getParameter('default_currency'));
            $description = 'Refund payment for {project} escrow';
            $uwt->setDescription($description);
            $data = [
                'projectTitle' => $this->project->getTitle(),
                'projectUuid'  => $this->project->getUuid(),
            ];
            $uwt->setData(json_encode($data));
            $em->persist($uwt);

            // Refund project fee
            $uwt = new UserWalletTransaction();
            $uwt->setUserInfo($this->project->getUserInfo());
            $uwt->setAmount($refundProjectFee);
            $uwt->setCurrency($this->container->getParameter('default_currency'));
            $description = 'Refund gig fee for {project} escrow';
            $uwt->setDescription($description);
            $data = [
                'projectTitle' => $this->project->getTitle(),
                'projectUuid'  => $this->project->getUuid(),
            ];
            $uwt->setData(json_encode($data));
            $em->persist($uwt);

            // Notify the person who sent the dispute
            $this->get('vocalizr_app.service.mandrill')->sendProjectDisputeAmountAccepted(
                $dispute,
                $this->project,
                $this->generateUrl('project_studio', [
                    'uuid' => $this->project->getUuid(),
                ], true)
            );


            // Release money to project bidder
            $this->_studioActionReleaseEscrow();
        }
    }

    /**
     * Studio Action
     * Submit uploaded assets to project
     */
    private function _studioActionSubmitAssets()
    {
        $mediaInfoService = $this->get('vocalizr_app.media_info');
        $files            = $this->request->get('asset_file');
        $fileTitles       = $this->request->get('asset_file_title');

        if (count($files) > 0) {
            $violationArrays = [];
            $fileError       = [];
            $projectAssets   = [];

            if (count($files) > 0) {
                foreach ($files as $i => $file) {
                    $title = isset($fileTitles[$i]) ? $fileTitles[$i] : $file;

                    $violations = $mediaInfoService->validateProjectAssetAudio($file);
                    if ($violations) {
                        $violationArrays[$title] = $violations;
                    }

                    if (!empty($violations)) {
                        continue;
                    }

                    if (
                        !$projectAsset = $this->em->getRepository('VocalizrAppBundle:ProjectAsset')
                            ->saveUploadedFile($this->user->getId(), $this->project->getId(), $title, $file)
                    ) {
                        $fileError[] = $file;
                    } else {
                        $projectAssets[] = $projectAsset;
                    }
                }
            }

            $totalSuccess = count($projectAssets);
            // If any files failed to be saved, display messages

            if (!empty($violationArrays)) {
                foreach ($violationArrays as $title => $violationArray) {
                    foreach ($violationArray as $violation) {
                        $this->session->getFlashBag()->add('error', "Failed to submit file {$title}: $violation");
                    }
                }
                return false;
            } elseif ($fileError) {
                if (count($fileError) == count($files)) {
                    $this->session->getFlashBag()->add('error', 'Failed to submit files, please upload again');
                    return false;
                } else {
                    $message = 'Successfully submitted ' . $totalSuccess . ' uploaded asset' . ($totalSuccess > 1 ? 's' : '') .
                                    '.<br>Failed to submit ' . count($fileError) . ' file' . (count($fileError) > 1 ? 's' : '');
                    $this->session->getFlashBag()->add('notice', $message);
                }
            } else {
                $this->session->getFlashBag()->add('notice', 'Successfully submitted your uploaded assets');
            }

            // Remove prompt user for files, as they have uploaded some files
            if ($this->projectBid->getUserInfo()->getId() == $this->user->getId()
                    && $this->project->getPromptAssets()) {
                $this->project->setPromptAssets(false);
                $this->em->persist($this->project);
            }

            $this->em->flush();

            return true;
        }

        return false;
    }

    /**
     * Upload assets modal
     *
     * @Secure(roles="ROLE_USER")
     * @Route("/studio/{uuid}/assets", name="project_studio_assets_upload")
     * @Template()
     *
     * @param Request $request
     */
    public function uploadAssetsAction(Request $request, $uuid)
    {
        $user = $this->getUser();
        $em   = $this->getDoctrine();

        // Make sure project is valid
        if (!$uuid) {
            throw $this->createNotFoundException('Invalid gig');
        }

        $project = $this->project = $em->getRepository('VocalizrAppBundle:Project')
                    ->getProjectByUuid($uuid);

        if (!$project) {
            throw $this->createNotFoundException('Invalid gig');
        }

        if (!$projectBid = $project->getProjectBid()) {
            throw $this->createNotFoundException('Cannot access studio until gig has been awarded and accepted');
        }

        // Only the owner of the gig can see this gig or
        // the person who won the project
        if ($project->getUserInfo()->getId() != $user->getId() &&
                $project->getProjectBid()->getUserInfo()->getId() != $user->getId()) {
            throw $this->createNotFoundException('Permission denied');
        }

        return [
            'project' => $project,
        ];
    }

    /**
     * Studio Action
     * Download asset
     */
    private function _studioActionDownloadAsset()
    {
        $slug = $this->request->get('assetSlug');

        // Get project asset by slug
        $projectAsset = $this->em->getRepository('VocalizrAppBundle:ProjectAsset')
                ->getBySlug($slug);

        if (!$projectAsset) {
            throw $this->createNotFoundException('Invalid asset file');
        }

        // Make sure they have permission to download file
        if ($this->project->getProjectType() == 'paid' && $projectAsset->getUserInfo()->getId() != $this->user->getId() && !$this->project->getIsComplete()) {
            throw $this->createNotFoundException('Please release payment before you can download this asset');
        }

        if ($projectAsset->getUserInfo()->getId() != $this->user->getId() && !$projectAsset->getDownloaded()) {
            $projectAsset->setDownloaded(true);
            $this->em->persist($projectAsset);
            $this->em->flush();
        }

        if (ini_get('zlib.output_compression')) {
            ini_set('zlib.output_compression', 'Off');
        }

        $file = $projectAsset->getAbsolutePath();

        header('Content-Description: File Transfer');
        header('Content-Type: application/force-download');
        header('Content-Disposition: attachment; filename="' . $projectAsset->getTitle() . '"');
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
     * Studio Action
     * Delete asset
     */
    private function _studioActionDeleteAsset()
    {
        $slug = $this->request->get('assetDeleteSlug');

        // Get project asset by slug
        $projectAsset = $this->em->getRepository('VocalizrAppBundle:ProjectAsset')
                ->getBySlug($slug);

        if (!$projectAsset) {
            throw $this->createNotFoundException('Invalid asset file');
        }

        // Make sure logged in user owns asset
        if ($projectAsset->getUserInfo()->getId() != $this->user->getId()) {
            throw $this->createNotFoundException('You do not have permission to delete this asset');
        }

        $this->request->query->set('notice', 'Asset ' . $projectAsset->getTitle() . ' has been deleted');

        $this->em->remove($projectAsset); // This will also delete the file
        $this->em->flush();
    }

    /**
     * Studio Action
     * Release escrow payment
     */
    private function _studioActionReleaseEscrow()
    {
        // Get escrow payment
        if (!$projectEscrow = $this->project->getProjectEscrow()) {
            throw $this->createNotFoundException('Invalid payment release');
        }

        // Make sure payment hasn't already been released
        if ($projectEscrow->getReleasedDate()) {
            throw $this->createNotFoundException('Payment has already been released to user');
        }

        // Get PRO subscription plan (the only possible bidder's subscription plan while bidding)
        $bidderSubscriptionPlan = $this->em->getRepository('VocalizrAppBundle:SubscriptionPlan')
            ->getByStaticKey(SubscriptionPlanRepository::PLAN_PRO);

        // Create user wallet transaction
        // Add money to project bidders wallet
        $uwt = new UserWalletTransaction();
        $uwt->setUserInfo($this->projectBid->getUserInfo());
        $uwt->setAmount($projectEscrow->getAmount()); // In cents
        $uwt->setCurrency($this->container->getParameter('default_currency'));
        $description = 'Payment for gig {project} from {username}';
        $uwt->setDescription($description);
        $data = [
            'username'     => $this->user->getUsername(),
            'projectTitle' => $this->project->getTitle(),
            'projectUuid'  => $this->project->getUuid(),
        ];
        $uwt->setData(json_encode($data));
        $this->em->persist($uwt);

        // Admin fee
        $helper = $this->get('service.helper');
        $format = false;

        $fee = $helper->getPricePercent($projectEscrow->getAmount(), $bidderSubscriptionPlan['payment_percent_taken'], $format);

        $projectBid = $projectEscrow->getProjectBid();

        // Check if there is an override
        if ($projectBid->getPaymentPercentTaken()) {
            $fee = $helper->getPricePercent($projectEscrow->getAmount(), $projectBid->getPaymentPercentTaken(), $format);
        }

        // Create user wallet transaction
        // Deduct admin fee from wallet
        $uwt = new UserWalletTransaction();
        $uwt->setUserInfo($this->projectBid->getUserInfo());
        $uwt->setAmount('-' . $fee); // In cents
        $uwt->setCurrency($this->container->getParameter('default_currency'));
        $description = 'Gig fee taken for {project}';
        $uwt->setDescription($description);
        $data = [
            'projectTitle' => $this->project->getTitle(),
            'projectUuid'  => $this->project->getUuid(),
        ];
        $uwt->setData(json_encode($data));
        $this->em->persist($uwt);

        // Set release date and save
        $projectEscrow->setContractorFee($fee);
        $projectEscrow->setReleasedDate(new \DateTime());
        $this->em->persist($projectEscrow);

        // Set project as completed
        $this->project->setIsComplete(true);
        $this->em->persist($this->project);

        $audit = $this->get('vocalizr_app.model.user_audit')->createProjectReleaseEscrowAudit($this->project);

        $this->em->persist($audit);

        $this->generatePdfInvoice($this->project);

        /** @var MandrillService $mandrillService */
        $mandrillService = $this->get('vocalizr_app.service.mandrill');

        // Send email to bidder notifiying them that payment has been released
        // Also send email to project owner.
//        $mandrillService->sendProjectPayedMessages($this->project, $this->generateUrl('project_studio', [
//            'uuid' => $this->project->getUuid(),
//        ], true), $this->project->getInvoicePdfPath());

        $url = $this->generateUrl('project_studio', [
            'uuid' => $this->project->getUuid(),
        ]);

        // Display notice only if payment has not been negotiated.
        if (!$this->disputeAccepted || $this->disputeAccepted->getAmount()) {
            $message = 'Payment has been released to ' . $this->projectBid->getUserInfo()->getUsername() . '. ';

            if (!$this->disputeAccepted) {
                $url .= '#rating';
                $message .= '<a href="#rating" style="color:#fff">Review them now</a>';
            }

            $this->get('session')->getFlashBag()->add('notice', $message);
        }

        $this->em->flush();

        return new RedirectResponse($url);
    }

    /**
     * Project delete dispute
     * Only viewable once project has been awarded
     *
     * @Secure(roles="ROLE_USER")
     * @Route("/studio/{uuid}/dispute/del/{id}", name="project_dispute_del")
     */
    public function disputeDeleteAction(Request $request)
    {
        $id   = $request->get('id');
        $user = $this->getUser();
        $em   = $this->getDoctrine()->getManager();

        // Get project dispute
        $dispute = $em->getRepository('VocalizrAppBundle:ProjectDispute')
                ->findOneBy([
                    'id'             => $id,
                    'from_user_info' => $user,
                ]);
        if (!$dispute) {
            throw $this->createNotFoundException('Invalid dispute');
        }

        $em->remove($dispute);
        $em->flush();

        $this->get('session')->getFlashBag()->add('notice', 'Neigiation removed');

        return $this->redirect($this->generateUrl('project_studio', [
            'uuid' => $request->get('uuid'),
        ]) . '#payment');
    }

    /**
     * Check feed for any new items for project
     *
     * @Secure(roles="ROLE_USER")
     * @Route("/studio/{uuid}/feed", name="project_studio_feed")
     */
    public function feedAction(Request $request)
    {
        $uuid       = $request->get('uuid', false);
        $lastFeedId = $request->get('lastFeedId') ? $request->get('lastFeedId') : $request->query->get('lastFeedId');
        $user       = $this->getUser();
        $em         = $this->getDoctrine()->getManager();

        // Make sure project is valid
        if (!$uuid) {
            throw $this->createNotFoundException('Invalid gig');
        }

        $project = $this->project = $em->getRepository('VocalizrAppBundle:Project')
                    ->getProjectByUuid($uuid);

        if (!$project) {
            throw $this->createNotFoundException('Invalid gig');
        }

        if (!$projectBid = $project->getProjectBid()) {
            throw $this->createNotFoundException('Cannot access studio until gig has been awarded and accepted');
        }

        // Only the owner of the gig can see this gig or
        // the person who won the project
        if ($project->getUserInfo()->getId() != $user->getId() &&
                $project->getProjectBid()->getUserInfo()->getId() != $user->getId()) {
            throw $this->createNotFoundException('Permission denied');
        }

        // Work out other parties user info
        if ($project->isOwner($user)) {
            $otherUserInfo = $project->getBidderUser();
        } else {
            $otherUserInfo = $project->getUserInfo();
        }

        // If items array exist, then look to mark items as read
        $items = $request->get('feed_items');
        if ($items && count($items) > 0) {
            $q = $em->getRepository('VocalizrAppBundle:ProjectFeed')->createQueryBuilder('pf');
            $q->update()
                    ->set('pf.feed_read', 1)
                    ->where('pf.from_user_info = :fromUserInfoId AND pf.project = :projectId')
                    ->andWhere($q->expr()->in('pf.id', ':feedItems'));
            $params = [
                ':fromUserInfoId' => $otherUserInfo->getId(),
                ':projectId'      => $project->getId(),
                ':feedItems'      => $items,
            ];
            $q->setParameters($params);
            $q->getQuery()->execute();
            exit;
        }

        // If no feed id, then ignore request
        if ($lastFeedId === false) {
            exit;
        }

        // Get project feed
        $projectFeed = $em->getRepository('VocalizrAppBundle:ProjectFeed')
                ->getFeed($project->getId(), $request->get('lastFeedId'));

        $feedAudios = $em->getRepository('VocalizrAppBundle:ProjectAudio')->getFeedAudio(
            $projectFeed,
            $project->getId()
        );

        if (!$projectFeed) {
            exit;
        }

        // Make all unread feed items as read if action is done by other party
        //$em->getRepository('VocalizrAppBundle:ProjectFeed')
        //        ->updateFeedItemsAsRead($project->getId(), $otherUserInfo->getId());

        // Get display names
        $displayNames[$otherUserInfo->getId()] = $otherUserInfo->getDisplayName();
        $displayNames[$user->getId()]          = $user->getDisplayName();

        return $this->render('VocalizrAppBundle:ProjectStudio:feedItems.html.twig', [
            'projectFeed'  => $projectFeed,
            'project'      => $project,
            'displayNames' => $displayNames,
            'feedAudios'   => $feedAudios,
        ]);
    }

    /**
     * Upload master or vocal audio
     *
     * @Secure(roles="ROLE_USER")
     * @Route("/studio/{uuid}/upload/{type}", name="project_studio_upload")
     * @Template()
     *
     * @param Request $request
     */
    public function uploadAudioAction(Request $request)
    {
        $uuid = $request->get('uuid');
        $type = $request->get('type');

        if (!in_array($type, [ProjectAudio::FLAG_WORKING, ProjectAudio::FLAG_MASTER])) {
            return $this->forward('VocalizrAppBundle:Default:error', [
                'error' => 'Invalid audio type',
            ]);
        }

        $user             = $this->getUser();
        $em               = $this->getDoctrine()->getManager();
        $projectAudioRepo = $em->getRepository('VocalizrAppBundle:ProjectAudio');

        // Make sure project is valid
        if (!$uuid) {
            return $this->forward('VocalizrAppBundle:Default:error', [
                'error' => 'Invalid gig',
            ]);
        }

        $project = $this->project = $em->getRepository('VocalizrAppBundle:Project')
                    ->getProjectByUuid($uuid);

        if (!$project) {
            return $this->forward('VocalizrAppBundle:Default:error', [
                'error' => 'Invalid gig',
            ]);
        }

        if (!$projectBid = $project->getProjectBid()) {
            return $this->forward('VocalizrAppBundle:Default:error', [
                'error' => 'Cannot upload studio audio until gig has been awarded and accepted',
            ]);
        }

        // Check permissions
        if ($project->getUserInfo()->getId() != $user->getId() &&
                $project->getProjectBid()->getUserInfo()->getId() != $user->getId()) {
            return $this->forward('VocalizrAppBundle:Default:error', [
                'error' => 'Permission denied',
            ]);
        }

        // Check file type and who is uploading
        if (($type == ProjectAudio::FLAG_MASTER && $project->getEmployeeUserInfo() == $user) ||
                $type == ProjectAudio::FLAG_WORKING && $project->getUserInfo() == $user) {
            return $this->forward('VocalizrAppBundle:Default:error', [
                'error' => 'Permission denied to upload that audio type',
            ]);
        }

        // If file exists, this means they have uploaded audio
        if ($request->get('audio_file')) {
            // Get current audio for that type so we can remove later
            /*
            $currentAudio = $projectAudioRepo->findOneBy(array(
                'project' => $project->getId(),
                'flag' => $type,
            ));
             *
             */

            // Attempt to save file
            $projectAudio = $projectAudioRepo
                    ->saveUploadedFile(
                        $project->getId(),
                        $user->getId(),
                        $request->get('audio_title'),
                        $request->get('audio_file'),
                        $type
                    );

            if (!$projectAudio) {
                $this->get('session')->getFlashBag()->add('error', 'There was a issue with your uploaded audio. Please try again');
                return $this->redirect($this->generateUrl('project_studio', ['uuid' => $project->getUuid()]));
            }

            // Remove other audio to replace
            /*
            if ($currentAudio) {
                $em->remove($currentAudio);
                $em->flush();
            }
             *
             */

            $this->get('session')->getFlashBag()->add('notice', 'Successfully saved audio');
            return $this->redirect($this->generateUrl('project_studio', ['uuid' => $project->getUuid()]));
        }

        return [
            'project' => $project,
        ];
    }

    // HELPER FUNCTIONS
    private function markProjectRead($project)
    {
        $em = $this->getDoctrine()->getManager();

        if ($project->getUserInfo() == $this->getUser()) {
            $project->setEmployerReadAt(new \DateTime());
        } else {
            $project->setEmployeeReadAt(new \DateTime());
        }
        $em->flush($project);

        // udpate all activity for this project to read for this user
        $q = $em->getRepository('VocalizrAppBundle:ProjectActivity')->createQueryBuilder('pa');
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

        // check to see if the user has any unread projects or invitations
        $q = $em->getRepository('VocalizrAppBundle:Project')->createQueryBuilder('p');
        $q->select('count(p)')
                ->where('p.user_info = :user_info')
                ->andWhere('p.last_activity != :empty_activity')
                ->andWhere('p.employer_read_at is null')
                ->setParameter(':user_info', $this->getUser())
                ->setParameter(':empty_activity', '{}');
        $numEmployerUnread = $q->getQuery()->getSingleScalarResult();

        $q = $em->getRepository('VocalizrAppBundle:Project')->createQueryBuilder('p');
        $q->select('count(p)')
                ->where('p.employee_user_info = :user_info')
                ->andWhere('p.last_activity != :empty_activity')
                ->andWhere('p.employee_read_at is null')
                ->setParameter(':user_info', $this->getUser())
                ->setParameter(':empty_activity', '{}');
        $numEmployeeUnread = $q->getQuery()->getSingleScalarResult();

        if ($numEmployerUnread == 0 && $numEmployeeUnread == 0) {
            $this->getUser()->setUnreadProjectActivity(false);
        }

        $q = $em->getRepository('VocalizrAppBundle:ProjectInvite')->createQueryBuilder('pi');
        $q->select('count(pi)')
                ->where('pi.user_info = :user_info')
                ->andWhere('pi.read_at is null')
                ->setParameter(':user_info', $this->getUser());
        $numInvitesUnread = $q->getQuery()->getSingleScalarResult();
        if ($numInvitesUnread == 0) {
            $this->getUser()->setUnseenProjectInvitation(false);
        }

        $em->flush($this->getUser());
    }

    /**
     * @Secure(roles="ROLE_USER")
     * @Route("/studio/{uuid}/agreement", name="project_studio_agreement")
     *
     * @param Request $request
     */
    public function downloadAgreementAction(Request $request, $uuid)
    {
        $em   = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        // Make sure project is valid
        if (!$uuid) {
            throw $this->createNotFoundException('Invalid gig');
        }

        $project = $em->getRepository('VocalizrAppBundle:Project')
                    ->getProjectByUuid($uuid);

        if (!$project) {
            throw $this->createNotFoundException('Invalid gig');
        }

        if (!$projectBid = $project->getProjectBid()) {
            throw $this->createNotFoundException('Cannot access studio until gig has been awarded and accepted');
        }

        // Only the owner of the gig can see this gig or
        // the person who won the project
        if ($project->getUserInfo()->getId() != $user->getId() &&
                $project->getProjectBid()->getUserInfo()->getId() != $user->getId()) {
            throw $this->createNotFoundException('Permission denied');
        }

        $pdfPath = $project->getAbsolutePdfPath();

        if (!file_exists($pdfPath)) {
            $mpdf = $this->generateAgreement($project);

            $mpdf->Output('agreement.pdf', 'D');
        }

        $otherPartyUser = $user === $project->getUserInfo() ?
            $project->getBidderUser() :
            $project->getUserInfo()
        ;

        $otherPartyName = $otherPartyUser->getFirstName() ?
            $otherPartyUser->getFullName() :
            $otherPartyUser->getUsernameOrDisplayName()
        ;

        $escapedOtherUsername
            = preg_replace("/[^a-zA-Z0-9\-_]+/u", "-", $otherPartyName);

        $escapedProjectTitle
            = preg_replace("/[^a-zA-Z0-9\-_]+/u", "-", $project->getTitle());

        $filename
            = sprintf('Vocalizr-Gig-Agreement-for-job-%s-with-%s.pdf', $escapedProjectTitle, $escapedOtherUsername);

        header('Content-disposition: attachment; filename=' . $filename);
        header('Content-type: application/pdf');
        readfile($pdfPath);
        exit;
    }

    /**
     * Download master track
     *
     * @Secure(roles="ROLE_USER")
     * @Route("/studio/{uuid}/master/{slug}", name="project_studio_download_master")
     *
     * @param Request $request
     */
    public function downloadMasterAction(Request $request, $uuid, $slug)
    {
        $em   = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        // Make sure project is valid
        if (!$slug) {
            throw $this->createNotFoundException('Invalid audio 1');
        }

        $projectAudio = $em->getRepository('VocalizrAppBundle:ProjectAudio')
                    ->findOneBy([
                        'slug' => $slug,
                    ]);

        if (!$projectAudio) {
            throw $this->createNotFoundException('Invalid audio 2');
        }

        $project = $projectAudio->getProject();

        if (!$projectBid = $project->getProjectBid()) {
            throw $this->createNotFoundException('Cannot access studio until gig has been awarded and accepted');
        }

        // Only the owner of the gig can see this gig or
        // the person who won the project
        if ($project->getUserInfo()->getId() != $user->getId() &&
                $project->getProjectBid()->getUserInfo()->getId() != $user->getId()) {
            throw $this->createNotFoundException('Permission denied');
        }

        if (ini_get('zlib.output_compression')) {
            ini_set('zlib.output_compression', 'Off');
        }

        $file = $projectAudio->getAbsolutePath();

        header('Content-Description: File Transfer');
        header('Content-Type: application/force-download');
        header('Content-Disposition: attachment; filename="' . $projectAudio->getTitle() . '"');
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
     * Download all assets (not from Dropbox). Returns zip file.
     *
     * @Secure(roles="ROLE_USER")
     *
     * @Route("/studio/{uuid}/assets/all", name="project_studio_download_all_assets")
     *
     * @param Request $request
     * @param string  $uuid
     *
     * @return Response
     *
     * @throws CommonException
     */
    public function downloadAssetsAction(Request $request, $uuid)
    {
        $em   = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        /** @var Project $project */
        $project = $em->getRepository('VocalizrAppBundle:Project')->getProjectByUuid($uuid);

        if (!$project) {
            throw $this->createNotFoundException('Project not found.');
        }

        if (!$projectBid = $project->getProjectBid()) {
            throw $this->createNotFoundException('Cannot access studio until gig has been awarded and accepted');
        }

        // Only the person who won the project can download assets.
        // The owner of the gig can download assent only after payment.
        if (
            $project->getProjectBid()->getUserInfo()->getId() != $user->getId() &&
            (
                $project->getUserInfo()->getId() != $user->getId() ||
                !$project->getIsComplete() ||
                $project->isFullyRefunded()
            )
        ) {
            throw new AccessDeniedHttpException('Access denied. 
            Only the person who won the project can download assets. 
            The owner of the gig can download assent only after payment, 
            and only if project was not closed with negotiation for $0.');
        }

        /** @var ProjectAsset[] $assets */
        $assets = $project->getProjectAssets();

        // Create new Zip Archive.
        $zip = new \ZipArchive();

        $responseFileName = str_replace(
            array_merge(
                array_map('chr', range(0,31)),
                ['<', '>', ':', '"', '/', "\\", '|', '?', '*', ' ']
            ),
            '-',
            sprintf('Vocalizr-Assets-for-%s-with-%s.zip',
                $project->getTitle(),
                $project->getBidderUser()->getUsername()
            )
        );

        $tempFilePath = $this->get('service.helper')->getUploadTmpDir()
            . DIRECTORY_SEPARATOR . uniqid('voc-asset') . '.zip';

        if ($zip->open($tempFilePath, \ZipArchive::CREATE) !== true) {
            throw new CommonException('Could not create archive');
        }

        $fileNamesInResponseArchive = [];
        foreach ($assets as $asset) {
            // If we have two files with same names, we need give them unique names
            // Because we should add them in one zip file
            $fileName = $asset->getAbsolutePath();
            if (file_exists($fileName)) {
                $baseName = str_replace(' ', '-', $asset->getTitle());
                if (array_key_exists($baseName, $fileNamesInResponseArchive)) {
                    ++$fileNamesInResponseArchive[$baseName];
                    $uniqueFileName = sprintf(
                        '%d_%s',
                        $fileNamesInResponseArchive[$baseName],
                        $baseName
                    );
                } else {
                    $fileNamesInResponseArchive[$baseName] = 0;
                    $uniqueFileName                        = $baseName;
                }
                $zip->addFile($fileName, $uniqueFileName);
            }
        }

        $zip->close();

        $response = new BinaryFileResponse($tempFilePath);
        $response->setContentDisposition('attachment', $responseFileName);
        $response->prepare($request);
        $response->send();

        @$success = unlink($tempFilePath);

        if (!$success) {
            error_log('Could not delete temp file for project asset studio. File path: ' . $tempFilePath);
        }

        exit();
    }

    /**
     * @Secure(roles="ROLE_USER")
     * @Route("/studio/{uuid}/regenAgreement", name="project_studio_regen_agreement")
     *
     * @param Request $request
     */
    public function regenAgreementAction(Request $request)
    {
        $user    = $this->getUser();
        $rootDir = $this->container->get('kernel')->getRootDir();
        $uuid    = $request->get('uuid');
        $em      = $this->getDoctrine()->getManager();

        // Make sure project is valid
        if (!$uuid) {
            throw $this->createNotFoundException('Invalid gig');
        }

        $project = $em->getRepository('VocalizrAppBundle:Project')
                    ->getProjectByUuid($uuid);

        if (!$project) {
            throw $this->createNotFoundException('Invalid gig');
        }

        if (!$projectBid = $project->getProjectBid()) {
            throw $this->createNotFoundException('Cannot access studio until gig has been awarded and accepted');
        }

        $subscriptionPlan = $em->getRepository('VocalizrAppBundle:SubscriptionPlan')
                ->getActiveSubscription($projectBid->getUserInfo()->getId());
        $employeeSubscriptionPlan = $em->getRepository('VocalizrAppBundle:SubscriptionPlan')
        ->getActiveSubscription($projectBid->getUserInfo()->getId());

        $container = $this->container;

        $mpdf = $this->generateAgreement($project);

        $mpdf->Output('agreement.pdf', 'D');
    }

    /**
     * @Secure(roles="ROLE_USER")
     * @Route("/studio/{uuid}/agreement/image/{index}.jpg", name="project_studio_agreement_image")
     *
     * @param Request $request
     * @param string $uuid
     * @param int $index
     */
    public function getAgreementImageAction(Request $request, $uuid, $index)
    {
        $renderQuality = 400;
        $resizeQuality = 200;
        if (!extension_loaded('imagick')) {
            return new Response('ImageMagick not installed');
        }

        if ($index < 1 || $index > 4) {
            throw new NotFoundHttpException('Image not found');
        }

        $index--;

        $em = $this->getDoctrine()->getManager();
        $user = $this->getUser();
        $project = $em->getRepository('VocalizrAppBundle:Project')
            ->getProjectByUuid($uuid);

        $this->checkStudioAccess($project, $user);

        $pdfPath = $project->getAbsolutePdfPath();

        if (!file_exists($pdfPath)) {
            $this->generateAgreement($project);
        }

        $filename = sprintf('%s_image_%d.jpg', $pdfPath, $index);

        if (file_exists($filename)) {
            return new BinaryFileResponse($filename);
        }

        $imagick = new \Imagick();
        $imagick->setResolution($renderQuality, $renderQuality);
        try {
            $imagick->readImage(sprintf('%s[%d]', $pdfPath, $index));
        } catch (\ImagickException $exception) {
            throw new NotFoundHttpException($exception->getMessage());
        }
        $imagick->setImageFormat('jpg');
        $imagick->thumbnailImage(1080, 1528, true, true);

        $imageData = $imagick->__toString();

        file_put_contents($filename, $imageData);
        chmod($filename, 0777);

        return new Response($imageData, 200, [
            'Content-Type' => 'image/jpeg',
        ]);
    }

    /**
     * Project studio complete
     * Marks the current gig as completed
     *
     * @Secure(roles="ROLE_USER")
     * @Route("/studio/{uuid}/complete", name="project_studio_complete")
     *
     * @Template()
     *
     * @param Request $request
     */
    public function completeProjectAction(Request $request)
    {
        $securityContext  = $this->container->get('security.context');
        $this->request    = $request;
        $uuid             = $this->uuid             = $request->get('uuid', false);
        $user             = $this->user             = $this->getUser();
        $em               = $this->em               = $this->getDoctrine()->getManager();
        $projectAudioRepo = $em->getRepository('VocalizrAppBundle:ProjectAudio');
        $userAudioRepo    = $em->getRepository('VocalizrAppBundle:UserAudio');

        $userReviewForm = $this->createForm(new UserReviewType());

        // Make sure project is valid
        if (!$uuid) {
            throw $this->createNotFoundException('Invalid gig');
        }

        $project = $this->project = $em->getRepository('VocalizrAppBundle:Project')
                    ->getProjectByUuid($uuid);

        if (!$project) {
            throw $this->createNotFoundException('Invalid gig');
        }

        // Set project as completed
        $this->project->setIsComplete(true);
        $this->em->flush();

        $this->get('session')->getFlashBag()->add('notice', 'Congratulations on successfully completing your gig! <a href="#rating">Give member a review!</a>');

        return $this->redirect($this->generateUrl('project_studio', [
            'uuid' => $this->project->getUuid(),
        ]) . '#rating');
    }

    /**
     * @param Project $project
     * @param UserInfo $user
     */
    private function checkStudioAccess(Project $project, UserInfo $user)
    {
        if (!$project) {
            throw $this->createNotFoundException('Invalid gig');
        }

        if (!$projectBid = $project->getProjectBid()) {
            throw $this->createNotFoundException('Cannot access studio until gig has been awarded and accepted');
        }

        // Only the owner of the gig can see this gig or
        // the person who won the project
        if ($project->getUserInfo()->getId() != $user->getId() &&
            $project->getProjectBid()->getUserInfo()->getId() != $user->getId()) {
            throw $this->createNotFoundException('Permission denied');
        }
    }

    /**
     * @param Project $project
     * @return \mPDF
     * @throws \Twig_Error
     */
    private function generateAgreement(Project $project)
    {
        $em         = $this->getDoctrine()->getManager();
        $projectBid = $project->getProjectBid();


        // get employer's membership
        $subscriptionPlan = $em->getRepository('VocalizrAppBundle:SubscriptionPlan')
            ->getActiveSubscription($project->getUserInfo()->getId());
        $employeeSubscriptionPlan = $em->getRepository('VocalizrAppBundle:SubscriptionPlan')
            ->getActiveSubscription($projectBid->getUserInfo()->getId());

        $contentData = [
            'project'                  => $project,
            'projectBid'               => $projectBid,
            'subscriptionPlan'         => $subscriptionPlan,
            'employeeSubscriptionPlan' => $employeeSubscriptionPlan,
        ];

        if ($project->getProjectType() == Project::PROJECT_TYPE_CONTEST) {
            $title   = 'CONTEST AGREEMENT';
            $content = $this->get('templating')->render('VocalizrAppBundle:Contest:agreement.html.twig', $contentData);
        } else {
            $title   = 'GIG AGREEMENT';
            $content = $this->get('templating')->render('VocalizrAppBundle:Project:agreement.html.twig', $contentData);
        }

        $pdfPath = $project->getAbsolutePdfPath();

        return $this->generatePdf($title, $content, $pdfPath);
    }

    /**
     * @param Project $project
     * @return \mPDF
     * @throws \Twig_Error
     */
    private function generatePdfInvoice(Project $project)
    {
        $em         = $this->getDoctrine()->getManager();
        $projectBid = $project->getProjectBid();

        // get employer's membership
        $subscriptionPlan = $em->getRepository('VocalizrAppBundle:SubscriptionPlan')
            ->getActiveSubscription($project->getUserInfo()->getId());
        $employeeSubscriptionPlan = $em->getRepository('VocalizrAppBundle:SubscriptionPlan')
            ->getActiveSubscription($projectBid->getUserInfo()->getId());

        $contentData = [
            'project'                  => $project,
            'projectBid'               => $projectBid,
            'subscriptionPlan'         => $subscriptionPlan,
            'employeeSubscriptionPlan' => $employeeSubscriptionPlan,
        ];

        if ($project->getProjectType() == Project::PROJECT_TYPE_CONTEST) {
            $title   = 'CONTEST INVOICE';
        } else {
            $title   = 'GIG INVOICE';
        }

        $content = $this->get('templating')->render('VocalizrAppBundle:Project:invoice.html.twig', $contentData);

        $pdfPath = $project->getInvoicePdfPath();

        return $this->generatePdf($title, $content, $pdfPath);
    }

    /**
     * @param $title
     * @param $content
     * @param $pdfPath
     * @return \mPDF
     * @throws \Twig_Error
     */
    private function generatePdf($title, $content, $pdfPath)
    {
        $rootDir    = $this->get('kernel')->getRootDir();
        $pdfTempDir = $rootDir . '/../tmp/mpdf';

        $css    = realpath($rootDir . '/../src/Vocalizr/AppBundle/Resources/public/css/pdf.css');
        $header = $this->get('templating')->render('VocalizrAppBundle:Pdf:header.html.twig', [
            'title' => $title,
        ]);
        $footer = $this->get('templating')->render('VocalizrAppBundle:Pdf:footer.html.twig');

        if (!file_exists($pdfTempDir)) {
            @$dirExists = mkdir($pdfTempDir);
        } else {
            $dirExists = true;
        }

        if ($dirExists && !defined('_MPDF_TEMP_PATH')) {
            define("_MPDF_TEMP_PATH", $rootDir . '/../tmp/mpdf');
        }
        $mpdf = new \mPDF('', 'A4', '', '', 0, 0, 30, 35, 0, 10);
        $mpdf->setHTMLHeader($header);
        $mpdf->setHTMLFooter($footer);
        $mpdf->WriteHTML(file_get_contents($css), 1);
        $mpdf->WriteHTML($content, 2);
        $mpdf->Output($pdfPath, 'F');

        chmod($pdfPath, 0777);

        @array_map('unlink', glob($pdfPath . '_image_*.jpg'));

        return $mpdf;
    }
}
