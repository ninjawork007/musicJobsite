<?php


namespace App\Controller;


use App\Service\MandrillService;
use App\Service\StripeManager;
use Doctrine\ORM\OptimisticLockException;

use Slot\MandrillBundle\Message;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\PaymentSessionData;
use App\Entity\UserCertification;
use App\Entity\UserInfo;

class AdminUserCertificationController extends AbstractController
{
    /**
     * @Route("/admin/user_confirmation/list", name="userConfirmationList")
     *
     * @param Request            $request
     * @param ContainerInterface $container
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function userConfirmationListAction(Request $request, ContainerInterface $container)
    {
        $this->checkAdmin();

        $userCertificationsQuery = $this->getDoctrine()->getRepository('App:UserCertification')
            ->findUserConfirmationsQuery();
        $paginator  = $container->get('knp_paginator');
        $pagination = $paginator->paginate(
            $userCertificationsQuery,
            $request->query->get('page', 1)/*page number*/,
            20// items per page
        );

        return $this->render('Admin/user_certification_waiting_for_approve.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    /**
     * @Route("/admin/user_confirmation/approve/{id}", name="userConfirmationApprove")
     * @param $id
     * @return RedirectResponse
     * @throws OptimisticLockException
     */
    public function userConfirmationApproveAction($id)
    {
        $this->checkAdmin();

        $em = $this->getDoctrine()->getManager();
        /** @var UserCertification $userCertification */
        $userCertification = $em->getRepository(UserCertification::class)
            ->find($id);
        if (is_null($userCertification)) {
            $this->addFlash('error', 'User not found');
            return $this->redirect($this->generateUrl('userConfirmationList'));
        }
        $this->userConfirmationApprove($userCertification);

        $this->addFlash('notice', 'Certification approved');
        return $this->redirect($this->generateUrl('userConfirmationList'));
    }

    /**
     * @Route("/admin/user_confirmation/approve_all", name="userConfirmationApproveAll")
     * @return RedirectResponse
     * @throws OptimisticLockException
     */
    public function userConfirmationApproveAllAction()
    {
        $this->checkAdmin();

        /** @var UserCertification[] $userCertified */
        $userCertifications = $this->getDoctrine()->getRepository(UserCertification::class)
            ->findBy(['paid' => true, 'validatedAt' => null]);
        foreach ($userCertifications as $userCertification) {
            $this->userConfirmationApprove($userCertification);
        }
        $this->addFlash('notice', 'Certification approved');

        return $this->redirect($this->generateUrl('userConfirmationList'));

    }

    /**
     * @Route("/admin/user_confirmation/deny/{id}", name="userConfirmationDeny")
     * @param $id
     * @param StripeManager $stripeManager
     * @return RedirectResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function userConfirmationDenyAction($id, StripeManager $stripeManager)
    {
        $this->checkAdmin();

        $em = $this->getDoctrine()->getManager();
        $session = $this->get('session');
        /** @var UserCertification $userCertified */
        $userCertified = $em->getRepository(UserCertification::class)->find($id);

        if (!$userCertified) {
            $this->addFlash('error', 'User not found');
            return $this->redirect($this->generateUrl('userConfirmationList'));
        }

        /** @var PaymentSessionData $paymentSessionData */
        $paymentSessionData = $em->getRepository('App:PaymentSessionData')
            ->findOneBy(['userCertification' => $userCertified->getId()])
        ;

        if ($paymentSessionData) {

//            $stripeManager = $this->get('vocalizr_app.stripe_manager');

            $error = null;

            // avoid possible crash.
            $refundResponseData['status'] = null;

            // Refund charge if any.
            if ($paymentSessionData->getStripeCharge() && $paymentSessionData->getStripeCharge()->getData()) {
                $chargeData = json_decode($paymentSessionData->getStripeCharge()->getData(), true);
                $refundResponseData = $stripeManager->getRefund($chargeData['data']['object']['id']);
                if (!$refundResponseData || $refundResponseData['status'] != 'succeeded') {
                    $error = 'could not refund charge.';
                    error_log('Certification: could not refund charge. Wrong statues returned.');
                }
            } else {
                error_log('Certification: could not refund charge. No charge data found related to the subscription');
                $error = 'could not refund charge';
            }

            $subscriptionResponseData = $stripeManager->getCancelSubscription($paymentSessionData->getSubscriptionId());

            if ($subscriptionResponseData['status'] != 'canceled') {
                error_log(
                    'Certification: could not cancel subscription ' . $subscriptionResponseData['id'] .
                    '. Status: ' . $subscriptionResponseData['status']
                );
                $error = 'could not cancel subscription';
            }

            if ($error) {
                $session->setFlash('error', 'Unable to cancel subscription or refund money: ' . $error);
                return $this->redirect($this->generateUrl('userConfirmationList'));
            }
        } else {
            $session->setFlash('notice', 'No payment session data found for certification attempt.');
        }

        $this->userConfirmationDeny($userCertified);
        $this->addFlash('notice', 'Certification attempt successfully denied.');

        return $this->redirect($this->generateUrl('userConfirmationList'));
    }

    /**
     * @param UserCertification $userCertification
     * @param MandrillService   $mandrill
     *
     * @return UserCertification
     */
    private function userConfirmationApprove(UserCertification $userCertification, MandrillService $mandrill)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var UserInfo $userInfo */
        $userInfo = $userCertification->getUserInfo();
        $userCertification->setValidatedAt(new \DateTime());
        $userCertification->setSucceed(true);
        $userInfo->setIsCertified(true);
        $em->flush();

//        $mandrill = $this->container->get('vocalizr_app.service.mandrill');
        $message = new Message();
        $message
            ->setTrackOpens(true)
            ->setTrackClicks(true);

        $body = $this->render('Mail/certified.html.twig', [
            'userInfo' => $userInfo,
        ]);
        $message->addGlobalMergeVar('BODY', $body);
        $mandrill->sendMessage($userInfo->getEmail(), 'Congratulations, you\'re Certified!', 'certification-successful-new-9-nov', [], $message);

        return $userCertification;
    }

    /**
     * @param UserCertification $userCertification
     * @param MandrillService   $mandrill
     * @return UserCertification
     */
    private function userConfirmationDeny(UserCertification $userCertification, MandrillService $mandrill)
    {
        $em = $this->getDoctrine()->getManager();
        /** @var UserInfo $userInfo */
        $userInfo = $userCertification->getUserInfo();
        $userCertification->setValidatedAt(new \DateTime());
        $userCertification->setSucceed(false);
        $userInfo->setIsCertified(false);
        $em->persist($userInfo);
        $em->persist($userCertification);
        $em->flush();

//        $mandrill = $this->container->get('vocalizr_app.service.mandrill');
        $message = new Message();
        $message->setPreserveRecipients(false);
        $message
            ->setTrackOpens(true)
            ->setTrackClicks(true);
        $mandrill->sendMessage($userInfo->getEmail(), 'Certification unsuccessful', 'certification-unsuccessful', [], $message);

        return $userCertification;
    }

    /**
     * @throws AccessDeniedHttpException
     */
    private function checkAdmin()
    {
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            throw new AccessDeniedHttpException();
        }
    }
}
