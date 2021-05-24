<?php

namespace App\Controller;

use Slot\MandrillBundle\Message;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Entity\Counter;
use App\Entity\Project;

class MessageController extends AbstractController
{
    /**
     * Main message center action
     *
     * @Route("/messages", name="message_home")
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @Template()
     */
    public function indexAction(Request $request)
    {
        $session = $request->getSession();
        $user    = $this->getUser();
        $em      = $this->getDoctrine()->getManager();

        // load the threads for this user
        $countThreads = $em->getRepository('App:MessageThread')
                ->findThreadsForUserCount($user);

        $threads = [];
        if ($countThreads) {
            $threads = $em->getRepository('App:MessageThread')
                        ->findThreadsForUser($user);
        }

        $activeThread    = null;
        $activeThreadBid = null;

        if (count($threads) > 0) {
            $threadUuid = $request->get('tid');
            if (!$threadUuid) {
                $activeThread = $threads[0];
            } else {
                $activeThread = $em->getRepository('App:MessageThread')
                        ->findOneBy(['uuid' => $threadUuid]);
            }

            // ensure this user can access this thread
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

            $message = new \App\Entity\Message();

            $form = $this->createFormBuilder($message)
                    ->add('content', null, [
                        'attr' => [
                            'class'       => 'form-control',
                            'placeholder' => 'Enter message...',
                            'rows'        => 6,
                        ],
                    ])
                    ->getForm();

            $response = $this->render(
                'Message/index.html.twig',
                ['threads'            => $threads,
                    'activeThread'    => $activeThread,
                    'countThreads'    => $countThreads,
                    'activeThreadBid' => $activeThreadBid,
                    'form'            => $form->createView(), ]
            );
        } else {
            $response = $this->render('Message/empty.html.twig');
        }
        return $response;
    }

    /**
     * Get the messages for a thread and display them
     *
     * @Route("/thread/{uuid}", name="thread_messages")
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param type                                      $threadUuid
     */
    public function viewMessagesAction(Request $request, $uuid)
    {
        $session = $request->getSession();
        $user    = $this->getUser();
        $em      = $this->getDoctrine()->getManager();

        $thread = $em->getRepository('App:MessageThread')
                ->findOneBy(['uuid' => $uuid]);

        // ensure this user can access this thread
        if ($thread->getEmployer() != $user &&
            $thread->getBidder() != $user) {
            return new Response(json_encode([
                'success' => false,
                'message' => 'Invalid message thread',
            ]));
        }

        // update the thread
        if ($thread->getEmployer() == $user) {
            $user->setNumUnreadMessages($user->getNumUnreadMessages() - $thread->getNumEmployerUnread());
            $em->flush($user);
            $thread->setNumEmployerUnread(0);
            $thread->setEmployerLastRead(new \DateTime());
        } else {
            $user->setNumUnreadMessages($user->getNumUnreadMessages() - $thread->getNumBidderUnread());
            $em->flush($user);
            $thread->setNumBidderUnread(0);
            $thread->setBidderLastRead(new \DateTime());
        }
        $em->flush($thread);

        // mark all messages to this user in this thread as read
        $q = $em->getRepository('App:Message')->createQueryBuilder('m');
        $q->update()
                ->set('m.read_at', ':now')
                ->where('m.to_user_info = :user_info')
                ->andWhere('m.message_thread = :thread')
                ->andWhere('m.read_at is null');
        $params = [
            ':now'       => new \DateTime(),
            ':user_info' => $user,
            ':thread'    => $thread,
        ];
        $q->setParameters($params);
        $q->getQuery()->execute();

        // get the bid for the active thread
        $threadBid = $em->getRepository('App:ProjectBid')
                              ->findOneBy(['project' => $thread->getProject(),
                                  'user_info'        => $thread->getBidder(), ]);

        $message = new \App\Entity\Message();

        $form = $this->createFormBuilder($message)
                ->add('content', null, [
                    'attr' => [
                        'class'       => 'form-control',
                        'placeholder' => 'Add your message here...',
                        'rows'        => 6,
                    ],
                ])
                ->getForm();

        $templateData = ['thread' => $thread,
            'threadBid'           => $threadBid,
            'form'                => $form->createView(), ];
        return new Response(json_encode([
            'success' => true,
            'html'    => $this->renderView(
                'Message/threadMessages.html.twig',
                $templateData
            ),
        ]));
    }

    /**
     * Compose a new message from the gig page.
     *
     * @Route("/message/reply/{uuid}", name="message_reply")
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string                                    $uuid
     * @Template()
     */
    public function replyAction(Request $request, $uuid)
    {
        $session = $request->getSession();
        $user    = $this->getUser();
        $em      = $this->getDoctrine()->getManager();

        // load the project this message relates to
        $messageThread = $em->getRepository('App:MessageThread')
                    ->findOneBy(['uuid' => $uuid]);

        // make sure this user is the employer or bidder
        if ($messageThread->getEmployer() != $user && $messageThread->getBidder() != $user) {
            return new Response(json_encode([
                'success' => false,
                'message' => 'Invalid message thread',
            ]));
        }
        $project = $messageThread->getProject();

        $message = new \App\Entity\Message();

        $form = $this->createFormBuilder($message)
                ->add('content', null, [
                    'attr' => [
                        'class'       => 'form-control',
                        'placeholder' => 'Add your message here...',
                        'rows'        => 6,
                    ],
                ])
                ->getForm();

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);
            $data = $form->getData();

            if ($messageThread->getEmployer() == $user) {
                $toUser = $messageThread->getBidder();
                $messageThread->setNumBidderUnread($messageThread->getNumBidderUnread() + 1);
                $messageThread->setEmployerLastRead(new \DateTime());
            } else {
                $toUser = $messageThread->getEmployer();
                $messageThread->setNumEmployerUnread($messageThread->getNumEmployerUnread() + 1);
                $messageThread->setBidderLastRead(new \DateTime());
            }
            $messageThread->setLastMessageAt(new \DateTime());

            $toUser->setNumUnreadMessages($toUser->getNumUnreadMessages() + 1);

            $message->setMessageThread($messageThread);
            $message->setUserInfo($user);
            $message->setToUserInfo($toUser);
            $message->setContent(strip_tags($data->getContent()));

            $messageFileRepo = $em->getRepository('App:MessageFile');

            // Check for any files uploaded
            if ($request->get('asset_file')) {
                $assetFiles  = $request->get('asset_file');
                $assetTitles = $request->get('asset_file_title');
                foreach ($assetFiles as $k => $assetFile) {
                    $assetTitle  = $assetTitles[$k];
                    $messageFile = $messageFileRepo->saveUploadedFile($user, $project, $message, $assetTitle, $assetFile);
                    $message->addMessageFile($messageFile);
                }
            }

            $em->persist($message);

            $em->flush();
        }

        return new Response(json_encode([
            'success' => true,
            'html'    => $this->renderView(
                'Message/message.html.twig',
                ['thread'      => $messageThread,
                    'messages' => [$message], ]
            ),
        ]));
    }

    /**
     * Compose a new message from the gig page.
     *
     * @Route("/message/compose/{projectUuid}/{userId}", name="message_compose")
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string                                    $projectUuid
     * @param string                                    $userId
     * @Template()
     */
    public function composeAction(Request $request, $projectUuid, $userId)
    {
        $session = $request->getSession();
        $user    = $this->getUser();
        $em      = $this->getDoctrine()->getManager();

        // load the project this message relates to
        $project = $em->getRepository('App:Project')
                    ->getProjectByUuid($projectUuid);

        if (!$project) {
            return $this->forward('App:Default:error', [
                'error' => 'Invalid gig',
            ]);
        }

        // load the user this message is being sent to
        $toUser = $em->getRepository('App:UserInfo')
                     ->findOneBy(['id' => $userId]);
        if (!$toUser) {
            return $this->forward('App:Default:error', [
                'error' => 'Invalid user',
            ]);
        }

        // ensure the user actually placed a bid
        $bid = $em->getRepository('App:ProjectBid')
                    ->findOneBy(['project' => $project,
                        'user_info'        => $toUser, ]);
        if (!$bid) {
            return $this->forward('App:Default:error', [
                'error' => 'Invalid user',
            ]);
        }

        $message = new \App\Entity\Message();

        $form = $this->createFormBuilder($message)
                ->add('content', null, [
                    'attr' => [
                        'class'       => 'form-control',
                        'placeholder' => 'Add your message here...',
                        'rows'        => 6,
                    ],
                ])
                ->getForm();

        if ($request->isMethod('POST')) {
            // see if there is a message thread for this project and users
            $messageThread = $em->getRepository('App:MessageThread')
                        ->findOneBy(['project' => $project,
                            'employer'         => $user,
                            'bidder'           => $toUser, ]);
            if (!$messageThread) {
                // if no message thread we will create one
                $messageThread = new \App\Entity\MessageThread();
                $messageThread->setProject($project);
                $messageThread->setEmployer($user);
                $messageThread->setNumEmployerUnread(0);
                $messageThread->setEmployerLastRead(new \DateTime());
                $messageThread->setBidder($toUser);
                $messageThread->setLastMessageAt(new \DateTime());
                $em->persist($messageThread);
            }
            $messageThread->setNumBidderUnread($messageThread->getNumBidderUnread() + 1);
            $toUser->setNumUnreadMessages($toUser->getNumUnreadMessages() + 1);

            $form->handleRequest($request);
            $data = $form->getData();

            $message->setContent(strip_tags($data->getContent()));
            $message->setMessageThread($messageThread);
            $message->setUserInfo($user);
            $message->setToUserInfo($toUser);

            $em->persist($message);
            $em->flush();

            // If user is accept email notifications for new messages
            $toUserPref = $toUser->getUserPref();
            if (!$toUserPref) {
                $toUserPref = new \App\Entity\UserPref();
            }

            if ($toUserPref->getEmailMessages()) {
                // Send email notifying user
                $dispatcher = $this->container->get('hip_mandrill.dispatcher');

                $subject = $user->getDisplayName() . ' sent you a message about your bid on "' . $project->getTitle() . '"';
                if ($project->getProjectType() == 'contest') {
                    $subject = $user->getDisplayName() . ' sent you a message about your entry on "' . $project->getTitle() . '"';
                }

                $body = $this->container->get('templating')->render('Mail/newMessage.html.twig', [
                    'toUserInfo'    => $toUser,
                    'fromUserInfo'  => $user,
                    'project'       => $project,
                    'messageThread' => $messageThread,
                    'message'       => $message,
                ]);

                $message = new Message();
                $message->setSubject($subject);
                $message->setFromEmail('noreply@vocalizr.com');
                $message->setFromName('Vocalizr');
                $message
                    ->setTrackOpens(true)
                    ->setTrackClicks(true);

                $message->addTo($toUser->getEmail());
                $message->addGlobalMergeVar('BODY', $body);
                $dispatcher->send($message, 'default');
            }

            $session->set('messageWarningSeen', true);

            return new Response(json_encode([
                'success' => true,
                'url'     => $this->generateUrl('message_home', ['tid' => $messageThread->getUuid()]),
            ]));
        }

        $templateData = [
            'project' => $project,
            'toUser'  => $toUser,
            'form'    => $form->createView(),
        ];
        return $templateData;
    }

    /**
     * Compose a new message for private convo
     *
     * @Route("/message/private/compose/{userId}", name="message_compose_private")
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string                                    $userId
     * @Template()
     */
    public function composePrivateAction(Request $request, $userId)
    {
        $session = $request->getSession();
        $user    = $this->getUser();
        $em      = $this->getDoctrine()->getManager();

        // load the user this message is being sent to
        $toUser = $em->getRepository('App:UserInfo')
                     ->findOneBy(['id' => $userId]);
        if (!$toUser) {
            return $this->forward('App:Default:error', [
                'error' => 'Invalid user',
            ]);
        }

        // Make sure user is connected
        $userConnect = $em->getRepository('App:UserConnect')
                ->findOneBy([
                    'to'   => $toUser,
                    'from' => $user,
                ]);
        if (!$userConnect) {
            return $this->forward('App:Default:error', [
                'error' => 'You need to be connected with member to message them',
            ]);
        }

        // See if they have been blocked by other user
        $userBlocked = $em->getRepository('App:UserBlock')
                ->findOneBy([
                    'user_info'  => $toUser,
                    'block_user' => $user,
                ]);
        if ($userBlocked) {
            return $this->forward('App:Default:error', [
                'error' => 'Sorry, you are unable to send messages to this member',
            ]);
        }

        // Make sure they haven't blocked the user either
        $userBlocked = $em->getRepository('App:UserBlock')
                ->findOneBy([
                    'user_info'  => $user,
                    'block_user' => $toUser,
                ]);
        if ($userBlocked) {
            return $this->forward('App:Default:error', [
                'error' => 'Unblock member before try to message them',
            ]);
        }

        // Check how many messages they started this month
        $messageCount     = $em->getRepository('App:Counter')->getCount($user, Counter::TYPE_MESSAGE);
        $subscriptionPlan = $em->getRepository('App:SubscriptionPlan')->getActiveSubscription($user->getId());

        if ($subscriptionPlan['message_month_limit'] && $messageCount >= $subscriptionPlan['message_month_limit']) {
            return $this->forward('App:Default:error', [
                'error' => 'You have reached your monthly message limit',
            ]);
        }

        $message = new \App\Entity\Message();

        $form = $this->createFormBuilder($message)
                ->add('content', null, [
                    'attr' => [
                        'class'       => 'form-control',
                        'placeholder' => 'Add your message here...',
                        'rows'        => 6,
                    ],
                ])
                ->getForm();

        if ($request->isMethod('POST')) {
            // see if there is a message thread for this project and users
            $messageThread = $em->getRepository('App:MessageThread')
                        ->findOneBy(['project' => null,
                            'employer'         => $user,
                            'bidder'           => $toUser,
                            'is_open'          => true,
                        ]);
            if (!$messageThread) {
                // if no message thread we will create one
                $messageThread = new \App\Entity\MessageThread();
                $messageThread->setProject(null);
                $messageThread->setEmployer($user);
                $messageThread->setNumEmployerUnread(0);
                $messageThread->setEmployerLastRead(new \DateTime());
                $messageThread->setBidder($toUser);
                $messageThread->setLastMessageAt(new \DateTime());
                $em->persist($messageThread);
            }
            $messageThread->setNumBidderUnread($messageThread->getNumBidderUnread() + 1);
            $toUser->setNumUnreadMessages($toUser->getNumUnreadMessages() + 1);

            $form->handleRequest($request);
            $data = $form->getData();

            $message->setContent(strip_tags($data->getContent()));
            $message->setMessageThread($messageThread);
            $message->setUserInfo($user);
            $message->setToUserInfo($toUser);

            $em->persist($message);
            $em->flush();

            // If user is accept email notifications for new messages
            $toUserPref = $toUser->getUserPref();
            if (!$toUserPref) {
                $toUserPref = new \App\Entity\UserPref();
            }

            if ($toUserPref->getEmailMessages()) {
                // Send email notifying user
                $dispatcher = $this->container->get('hip_mandrill.dispatcher');

                $subject = $user->getDisplayName() . ' sent you a private message';

                $body = $this->container->get('templating')->render('Mail/newMessagePrivate.html.twig', [
                    'toUserInfo'    => $toUser,
                    'fromUserInfo'  => $user,
                    'messageThread' => $messageThread,
                    'message'       => $message,
                ]);

                $message = new Message();
                $message->setSubject($subject);
                $message->setFromEmail('noreply@vocalizr.com');
                $message->setFromName('Vocalizr');
                $message
                    ->setTrackOpens(true)
                    ->setTrackClicks(true);

                $message->addTo($toUser->getEmail());
                $message->addGlobalMergeVar('BODY', $body);
                $dispatcher->send($message, 'default');
            }

            // Update counter for user
            $em->getRepository('App:Counter')->addCount($user, Counter::TYPE_MESSAGE);

            $session->set('messageWarningSeen', true);

            return new Response(json_encode([
                'success' => true,
                'url'     => $this->generateUrl('message_home', ['tid' => $messageThread->getUuid()]),
            ]));
        }

        $templateData = [
            'toUser' => $toUser,
            'form'   => $form->createView(),
        ];
        return $templateData;
    }

    /**
     * Marks all messages for this user as read
     *
     * @Route("/message/markAllRead", name="message_mark_all_read")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function markAllAsReadAction(Request $request)
    {
        $session = $request->getSession();
        $user    = $this->getUser();
        $em      = $this->getDoctrine()->getManager();

        $now = new \DateTime();

        // mark all the messages to the user as read
        $q = $em->getRepository('App:Message')->createQueryBuilder('m');
        $q->update()
                ->set('m.read_at', ':now')
                ->where('m.to_user_info = :user_info')
                ->andWhere('m.read_at is null');
        $params = [
            ':now'       => $now,
            ':user_info' => $user,
        ];
        $q->setParameters($params);
        $q->getQuery()->execute();

        // update message thread info for this user
        $q = $em->getRepository('App:MessageThread')->createQueryBuilder('mt');
        $q->update()
                ->set('mt.num_employer_unread', 0)
                ->set('mt.employer_last_read', ':now')
                ->where('mt.employer = :user_info');
        $params = [
            ':now'       => $now,
            ':user_info' => $user,
        ];
        $q->setParameters($params);
        $q->getQuery()->execute();

        $q = $em->getRepository('App:MessageThread')->createQueryBuilder('mt');
        $q->update()
                ->set('mt.num_bidder_unread', 0)
                ->set('mt.bidder_last_read', ':now')
                ->where('mt.bidder = :user_info');
        $params = [
            ':now'       => $now,
            ':user_info' => $user,
        ];
        $q->setParameters($params);
        $q->getQuery()->execute();

        // set the total messages unread for this user to 0
        $user->setNumUnreadMessages(0);

        $em->flush();

        return new Response(json_encode([
            'success' => true,
        ]));
    }

    /**
     * @Route("/message/file/delete/{id}", name="message_delete")
     */
    public function deleteFileAction(Request $request)
    {
        $user = $this->getUser();
        $em   = $this->getDoctrine()->getManager();

        // Get slug
        $messageFile = $em->getRepository('App:MessageFile')->
                findOneBy(['id' => $request->get('id'), 'user_info' => $user]);

        if (!$messageFile) {
            return new Response(json_encode([
                'success' => false,
                'message' => "File doesn't exist",
            ]));
        }

        $em->remove($messageFile);
        $em->flush();

        return new Response(json_encode([
            'success' => true,
        ]));
    }

    /**
     * @Route("/message/file/{slug}/download", name="message_download")
     *
     * @param Request $request
     */
    public function downloadFileAction(Request $request)
    {
        $user = $this->getUser();
        $em   = $this->getDoctrine()->getManager();

        // Get slug
        $messageFile = $em->getRepository('App:MessageFile')->
                findOneBy(['slug' => $request->get('slug')]);

        if (!$messageFile) {
            throw $this->createNotFoundException('File does not exist');
        }

        $project = $messageFile->getProject();
        $message = $messageFile->getMessage();

        if (!$messageFile->getDownloaded()) {
            $messageFile->setDownloaded(true);
            $em->persist($messageFile);
            $em->flush();
        }

        if ($dbLink = $messageFile->getDropboxLink()) {
            header('Location: ' . $dbLink);
            exit;
        }

        header('Content-type: application/octet-stream');
        header('Content-disposition=attachment; filename=' . $messageFile->getPath());
        if ($project) {
            header('Location: /p/' . $project->getId() . '/message/' . $message->getId() . '/' . $messageFile->getPath());
        } else {
            header('Location: /m/' . $message->getUserInfo()->getId() . '/' . $messageFile->getPath());
        }
        exit;
    }

    /**
     * @Route("/messages/thread/close/{uuid}", name="message_thread_close")
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function closeThreadAction(Request $request)
    {
        $em   = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        $threadUuid = $request->get('uuid');
        $thread     = $em->getRepository('App:MessageThread')
                    ->findOneBy(['uuid' => $threadUuid]);

        if (!$thread) {
            return $this->redirect($this->generateUrl('message_home'));
        }

        // make sure they are part of the thread
        if ($thread->getEmployer()->getId() != $user->getId() && $thread->getBidder()->getId() != $user->getId()) {
            return $this->redirect($this->generateUrl('message_home'));
        }

        $thread->setIsOpen(false);
        $em->persist($thread);
        $em->flush();
        return $this->redirect($this->generateUrl('message_home', ['tid' => $threadUuid]));
    }

    /**
     * Warning message before compose
     *
     * @Route("/message/warning/{projectUuid}/{userId}", name="message_warning")
     *
     * @param Request $request
     * @param string  $projectUuid
     * @param string  $userId
     * @Template()
     */
    public function warningAction($projectUuid, $userId, Request $request)
    {
        $session = $request->getSession();
        /** @var Project $project */
        $project = $this->getDoctrine()->getRepository(Project::class)->findOneBy(['uuid' => $projectUuid]);

        if (!$this->get('vocalizr_app.user_restriction')->canDiscussBid() && !$project->getMessaging()) {
            return $this->render(':include/panel:bid_messaging_panel.html.twig');
        }

        if ($session->get('messageWarningSeen')) {
            return $this->forward('App:Message:compose', ['projectUuid' => $projectUuid, 'userId' => $userId]);
        }
        return $this->render('Message/warning.html.twig', []);
    }

    /**
     * Warning message before compose for private messaging
     *
     * @Route("/message/private/warn/{userId}", name="message_warning_private")
     *
     * @param Request $request
     * @param string  $userId
     * @Template()
     */
    public function warningPrivateAction(Request $request, $userId)
    {
        $session = $request->getSession();
        $em      = $this->getDoctrine()->getManager();
        $user    = $this->getUser();

        $messageThread = $em->getRepository('App:MessageThread')
                    ->findOneBy(['project' => null,
                        'employer'         => $user,
                        'is_open'          => true,
                        'bidder'           => $userId, ]);
        if ($messageThread) {
            return $this->render('Message/warningPrivate.html.twig', [
                'redirect' => $this->generateUrl('message_home', ['tid' => $messageThread->getUuid()]),
            ]);
        }

        // Check how many messages they started this month
        $subscriptionPlan = $em->getRepository('App:SubscriptionPlan')->getActiveSubscription($user->getId());

        if ($subscriptionPlan['message_month_limit']) {
            $messageCount = $em->getRepository('App:Counter')->getCount($user, Counter::TYPE_MESSAGE);

            if ($messageCount >= $subscriptionPlan['message_month_limit']) {
                return $this->render('Message/upgrade.html.twig', [
                    'error' => 'You have reached your monthly message limit of ' . $subscriptionPlan['message_month_limit'],
                ]);
            }
        }

        if ($session->get('messageWarningSeen')) {
            return $this->forward('App:Message:composePrivate', ['userId' => $userId]);
        }

        return $this->render('Message/warningPrivate.html.twig', []);
    }
}
