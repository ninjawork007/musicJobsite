<?php

namespace App\Controller;

use App\Entity\AppMessage;
use App\Entity\AppMessageRead;
use App\Entity\EmailChangeRequest;
use App\Form\Type\ProjectSearchType;
use App\Form\Type\UserSearchType;
use App\Service\MembershipSourceHelper;
use Slot\MandrillBundle\Message;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Entity\Notification;
use App\Object\MembershipSourceObject;

class DefaultController extends AbstractController
{
    /**
     * @Route("/", name="home")
     * @Template()
     */
    public function indexAction()
    {
        if ($this->isGranted('ROLE_USER')) {
            return $this->redirect($this->generateUrl('dashboard'));
        }

        $user = new \App\Entity\UserInfo();
        $form = $this->createFormBuilder($user, [
            'validation_groups' => ['register_step1'], ])
                ->add('email', null, [
                    'label' => 'Email Address',
                ])
                ->getForm();

        return $this->render('Default/index.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/faq", name="website_faq")
     * @Template()
     */
    public function faqAction()
    {
        return $this->redirect('http://support.vocalizr.com/');
    }

    /**
     * @Route("/fees", name="website_fees")
     * @Template()
     */
    public function feesAction()
    {
        return $this->render('Default/fees.html.twig', []);
    }

    /**
     * @Route("/terms", name="website_terms")
     * @Template()
     */
    public function termsAction()
    {
        return $this->render('Default/terms.html.twig', []);
    }

    /**
     * @Route("/privacy", name="website_privacy")
     * @Template()
     */
    public function privacyAction()
    {
        return $this->render('Default/privacy.html.twig', []);
    }

    /**
     * @Route("/upgradenow", name="upgrade_now")
     */
    public function upgradenowAction()
    {
        return $this->redirect('/membership');
    }

    /**
     * @Route("/contact", name="website_contact")
     * @Template()
     */
    public function contactAction(Request $request)
    {
        return $this->redirect('http://support.vocalizr.com/');

        $form = $this->createFormBuilder()
                ->add('email', null, [
                    'label' => 'Your email',
                    'attr'  => [
                        'class' => 'form-control',
                    ],
                    'constraints' => [
                        new \Symfony\Component\Validator\Constraints\NotBlank([
                            'message' => 'Please provide your email address',
                        ]),
                        new \Symfony\Component\Validator\Constraints\Email([
                            'message' => 'Invalid email address',
                        ]),
                    ],
                ])
                ->add('name', null, [
                    'label' => 'Name',
                    'attr'  => [
                        'class' => 'form-control',
                    ],
                    'constraints' => [
                        new \Symfony\Component\Validator\Constraints\NotBlank([
                            'message' => 'Please specify your Name',
                        ]),
                    ],
                ])
                ->add('username', null, [
                    'label' => 'Vocalizr username (if applicable)',
                    'attr'  => [
                        'class' => 'form-control',
                    ],
                ])
                ->add('subject', null, [
                    'label' => 'Subject',
                    'attr'  => [
                        'class' => 'form-control',
                    ],
                    'constraints' => [
                        new \Symfony\Component\Validator\Constraints\NotBlank([
                            'message' => 'Please specify a subject',
                        ]),
                    ],
                ])
                ->add('message', TextareaType::class, [
                    'label' => 'Message',
                    'attr'  => [
                        'class' => 'form-control',
                    ],
                    'constraints' => [
                        new \Symfony\Component\Validator\Constraints\NotBlank([
                            'message' => 'Please enter a message',
                        ]),
                    ],
                ])
                ->getForm();

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $data       = $form->getData();
                $dispatcher = $this->get('hip_mandrill.dispatcher');
                $message    = new Message();
                $message
                    ->addTo('luke@vocalizr.com')
                    ->addTo('matt@vocalizr.com')
                    ->addGlobalMergeVar('CONTACTNAME', $data['name'])
                    ->addGlobalMergeVar('CONTACTUSERNAME', $data['username'])
                    ->addGlobalMergeVar('CONTACTEMAIL', $data['email'])
                    ->addGlobalMergeVar('CONTACTSUBJECT', $data['subject'])
                    ->addGlobalMergeVar('CONTACTMESSAGE', $data['message']);
                $result = $dispatcher->send($message, 'website-contact-message');

                return $this->redirect($this->generateUrl('website_contact_confirm'));
            }
        }

        return $this->render('Default/contact.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/contact_confirm", name="website_contact_confirm")
     * @Template()
     */
    public function contactConfirmAction(Request $request)
    {
        return $this->render('Default/contactConfirm.html.twig', []);
    }

    /**
     * @Route("/placeholder", name="home_placeholder")
     * @Template()
     */
    public function placeholderAction()
    {
        if ($this->isGranted('ROLE_USER')) {
            return $this->redirect($this->generateUrl('dashboard'));
        }

        return $this->render('Default/placeholder.html.twig', []);
    }

    /**
     * Handle error request
     * If ajax request, display modal html with error
     * if normal request, throw 404 error
     *
     * @param Request $request
     */
    public function errorAction(Request $request)
    {
        $view = $request->get('_view', 'Default:error.html.twig');
        if ($request->isXmlHttpRequest()) {
            $template = $this->renderView($view, $request->attributes->all());
            return new Response($template, 400);
        }

        throw $this->createNotFoundException($request->get('error'));
    }

    /**
     * @Route("/confirm-email", name="confirm_email")
     * @Template()
     */
    public function confirmEmailAction(Request $request)
    {
        // Check if email change request is valid
        $em = $this->getDoctrine()->getManager();

        if (!$result = $em->getRepository(EmailChangeRequest::class)->findOneBy(['unique_key' => $request->get('key')])) {
            throw $this->createNotFoundException('Invalid email change request');
        }

        $user = $result->getUserInfo();
        $user->setEmail($result->getEmail());

        $em->persist($user);
        $em->remove($result);
        $em->flush();

        return $this->render('Default/confirmEmail.html.twig', ['user' => $user]);
    }

    /**
     * ADE
     *
     * @Route("/ade", name="ade")
     * @Route("/ADE")
     */
    public function adeAction()
    {
        return $this->forward('App:Default:index');
    }

    /**
     * TEMP, REMOVE!
     *
     * @Route("/admin/loginAs", name="loginAs")
     */
    public function loginAsAction(Request $request)
    {
        // check the logged in user is an admin
        if (!$this->getUser()->getIsAdmin()) {
            return $this->redirect($this->generateUrl('dashboard'));
        }

        $id   = $request->get('id');
        $em   = $this->getDoctrine()->getManager();
        $user = $em->getRepository('App:UserInfo')->find($id);

        // Now to log user in
        $firewallName = 'user';
        $token        = new \Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken(
            $user,
            $user->getPassword(),
            $firewallName,
            $user->getRoles()
        );

        $request->getSession()->set('_security_' . $firewallName, serialize($token));

        $this->container->get('security.context')->setToken($token);

        return $this->redirect($this->generateUrl('dashboard'));
    }

    /**
     * @Route("/press", name="press")
     * @Template()
     *
     * @param Request $request
     */
    public function pressAction(Request $request)
    {
        return $this->render('Default/press.html.twig');
    }

    /**
     * @Route("/membership", name="plans")
     * @Route("/membership", name="membership")
     * @Template()
     *
     * @param Request $request
     *
     * @return array|Response
     */
    public function membershipAction(Request $request, MembershipSourceHelper $membershipSourceHelper)
    {
        $membershipSourceHelper->handleRequest(
            $request,
            MembershipSourceObject::STATUS_MEMBERSHIP_PAGE
        );

        return $this->render('Default/membership.html.twig');
    }

    /**
     * @Route("/singers", name="singers")
     * @Template()
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function singersAction(Request $request)
    {
        // Get fee options
        $ymlParser  = new \Symfony\Component\Yaml\Parser();
        $file       = $this->getParameter('kernel.project_dir') . '/config/packages/project.yml';
        $projectYml = $ymlParser->parse(file_get_contents($file));

        $form = $this->createForm(UserSearchType::class, [
            'budget' => $projectYml['budget']
        ]);

        if (!$request->get('search')) {
            $_GET[$form->getName()]['audio'] = true;
        }

        $form->handleRequest($request);

        return $this->render('Default/singers.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/singer-songwriters", name="singersongwriters")
     * @Template()
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function singersongwriterAction(Request $request)
    {
        // Get fee options
        $ymlParser  = new \Symfony\Component\Yaml\Parser();
        $file       = $this->getParameter('kernel.project_dir') . '/config/packages/project.yml';
        $projectYml = $ymlParser->parse(file_get_contents($file));

        $form = $this->createForm(UserSearchType::class, [
                                            'budget' => $projectYml['budget']
                                        ]);

        if (!$request->get('search')) {
            $_GET[$form->getName()]['audio'] = true;
        }

        $form->handleRequest($request);

        return $this->render('Default/singersongwriter.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/vocalizer", name="vocalizer")
     * @Template()
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function vocalizerAction(Request $request)
    {
        return $this->render('Default/vocalizer.html.twig', []);
    }

    /**
     * @Route("/singing-jobs", name="singing_jobs")
     * @Template()
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     */
    public function singingJobsAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $ymlParser  = new \Symfony\Component\Yaml\Parser();
        $file       = $this->getParameter('kernel.project_dir') . '/config/packages/project.yml';
        $projectYml = $ymlParser->parse(file_get_contents($file));

        $form = $this->createForm(ProjectSearchType::class, [
                                    'budget' => $projectYml['budget']
                                ]);

        $form->handleRequest($request);

        return $this->render('Default/singingJobs.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @deprecated - a stub action for correct url generation for user images in emails.
     *
     * @Route("uploads/avatar/{size}/{path}", name="circle_avatar", defaults={"size": "medium"})
     */
    public function circleAvatarAction()
    {
        throw new NotFoundHttpException('Deprecated circle avatar method call');
    }

    /**
     * @Template()
     */
    public function appMessageAction()
    {
        $user = $this->getUser();

        if (!$user) {
            return new Response();
        }

        $em      = $this->getDoctrine()->getManager();
        $message = $em->getRepository(AppMessage::class)
                ->findOneToShow($user);

        if (!$message) {
            return new Response();
        }

        // mark message read for this user
        $em          = $this->getDoctrine()->getManager();
        $messageRead = $em->getRepository('App:AppMessageRead')
                ->findOneBy(['user_info' => $user,
                    'app_message'        => $message, ]);
        if (!$messageRead) {
            $messageRead = new AppMessageRead();
            $messageRead->setAppMessage($message);
            $messageRead->setUserInfo($user);
            $messageRead->setReadAt(new \DateTime());
            $em->persist($messageRead);
            $em->flush();
        }

        return $this->render('Default/app_message.html.twig', [
            'message' => $message
        ]);
    }

    /**
     * Mark an app message as read for the user
     *
     * @Route("/appMessageRead/{id}", name="app_message_read")
     *
     * @param Request $request
     * @param string  $id
     */
    public function appMessageReadAction(Request $request, $id)
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false]);
        }

        $em      = $this->getDoctrine()->getManager();
        $message = $em->getRepository('App:AppMessage')
                ->findOneById($id);

        if (!$message) {
            return new JsonResponse(['success' => false]);
        }

        $em          = $this->getDoctrine()->getManager();
        $messageRead = $em->getRepository('App:AppMessageRead')
                ->findOneBy(['user_info' => $user,
                    'app_message'        => $message, ]);
        if (!$messageRead) {
            $messageRead = new \App\Entity\AppMessageRead();
            $messageRead->setAppMessage($message);
            $messageRead->setUserInfo($user);
            $messageRead->setReadAt(new \DateTime());
            $em->persist($messageRead);
        }
        $messageRead->setClosedAt(new \DateTime());
        $em->flush();

        $message = $em->getRepository('App:AppMessage')
                ->findOneToShow($user);

        if (!$message) {
            return new JsonResponse(['success' => true]);
        }

        // mark message read for this user
        $em          = $this->getDoctrine()->getManager();
        $messageRead = $em->getRepository('App:AppMessageRead')
                ->findOneBy(['user_info' => $user,
                    'app_message'        => $message, ]);
        if (!$messageRead) {
            $messageRead = new \App\Entity\AppMessageRead();
            $messageRead->setAppMessage($message);
            $messageRead->setUserInfo($user);
            $messageRead->setReadAt(new \DateTime());
            $em->persist($messageRead);
            $em->flush();
        }

        $data = ['success' => true,
            'newMessage'   => $this->renderView(
                'Default:appMessage.html.twig',
                ['message' => $message]
            ), ];
        return new JsonResponse($data);
    }

    /**
     * @Template()
     */
    public function notificationsAction()
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->render('Default/notifications.html.twig', [
                    'notifications' => []
                ]);
        }

        $em = $this->getDoctrine()->getManager();

        $qb = $em->getRepository('App:Notification')
            ->createQueryBuilder('n');

        $qb
            ->select('n, aui, auip, aup, p')
            ->innerJoin('n.actioned_user_info', 'aui')
            ->leftJoin('aui.user_pref', 'auip')
            ->leftJoin('n.actioned_user_info', 'aup')
            ->leftJoin('n.project', 'p')

            ->where('n.user_info = :userInfo')
            ->andWhere('n.project IS NULL OR p.is_active = 1')

            ->orderBy('n.created_at', 'DESC')
            ->setMaxResults(100)
        ;

        if (count(Notification::$hiddenNotifications)) {
            $qb->andWhere($qb->expr()->notIn('n.notify_type', Notification::$hiddenNotifications));
        }

        $qb->setParameter('userInfo', $user);
        $notifications = $qb->getQuery()->execute();

        //$notifications = $em->getRepository('App:Notification')
        //        ->findBy(array('user_info' => $user), array('created_at' => 'DESC'));

        return $this->render('Default/notifications.html.twig', [
            'notifications' => $notifications
        ]);
    }

    /**
     * @Route("/notify/read", name="notify_read")
     *
     * @return JsonResponse
     */
    public function notificationsReadAction()
    {
        $user = $this->getUser();

        if (!$user) {
            return new JsonResponse(['success' => false]);
        }

        $notificationRepo = $this->getDoctrine()->getRepository('App:Notification');
        $notificationRepo->setAllRead($user);

        return new JsonResponse([
            'success' => true,
        ]);
    }

    /**
     * @Route("/ferrycorsten", name="ferry")
     *
     * @return JsonResponse
     */
    public function ferryAction()
    {
        return $this->redirect('/contest/sfs-ferry-corsten-2016', 301);
    }
}
