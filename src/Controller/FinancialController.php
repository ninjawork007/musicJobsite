<?php

namespace App\Controller;

use App\Service\UserRestrictionService;
use Knp\Component\Pager\Paginator;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

use Symfony\Component\HttpFoundation\Session\Session;
use App\Entity\Project;

// Forms
use App\Entity\UserActionAudit;
use App\Entity\UserInfo;
use App\Entity\UserWalletTransaction;
use App\Entity\UserWithdraw;
use App\Form\Type\UserWithdrawType;
use App\Service\MembershipSourceHelper;

class FinancialController extends AbstractController
{
    /**
     * Finances Dashboard
     *
     * @Route("/user/finances", name="user_financial")
     * @Template
     *
     * @param Request $request
     *
     * @return array|RedirectResponse
     */
    public function indexAction(Request $request, ContainerInterface $container, UserRestrictionService $userRestrictionService)
    {
        $isPaid = ($request->get('paid', false) === false) ? false : true;
        if ($isPaid) {
            $this->get('vocalizr_app.model.user_audit')->logAction(UserActionAudit::ACTION_DEPOSIT);
            return new RedirectResponse($this->generateUrl('user_financial'));
        }
        $em   = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        $wtQuery = $em->getRepository('App:UserWalletTransaction')
            ->findTransactionsByUserQb($user->getId());

        $paginator  = $container->get('knp_paginator');
        $pagination = $paginator->paginate(
            $wtQuery,
            $request->query->get('page', 1)/*page number*/,
            20// limit per page
        );

        // Get payment escrows
        $escrows = $em->getRepository('App:ProjectEscrow')
                ->getEscrowsUserInvolved($user->getId());

        /** @var Session $session */
        $session = $request->getSession();

        if ($userRestrictionService->canWithdrawInstantly()) {
            $session->getFlashBag()->get(MembershipSourceHelper::SUB_SOURCE_INSTANT_WITHDRAWALS_MODAL);
        } else {
            $session->getFlashBag()->set(MembershipSourceHelper::SUB_SOURCE_INSTANT_WITHDRAWALS_MODAL, true);
        }

        return $this->render('Financial/index.html.twig', [
            'pagination' => $pagination,
            'escrows'    => $escrows,
        ]);
    }

    /**
     * Finance Deposit
     *
     * @Route("/user/finances/deposit", name="financial_deposit")
     * @Template
     */
    public function depositAction(Request $request)
    {
        $em     = $this->getDoctrine()->getManager();
        /** @var UserInfo $user */
        $user   = $this->getUser();
        $paypal = $this->get('service.paypal');

        if ($user && $user->isVerificationsExists() && !$user->isVerified() && !$user->isRequestedVerificationRecently()) {
            $this->get('session')->getFlashBag()->add('error', 'Your latest verification attempt failed. Please, try again.');
        }

        $subscriptionPlan = $em->getRepository('App:SubscriptionPlan')->getActiveSubscription($user->getId());

        return [
            'subscriptionPlan' => $subscriptionPlan,
            'paypal'           => $paypal,
        ];
    }

    /**
     * Finances Withdraw
     *
     * @Route("/user/finances/withdraw", name="financial_withdraw")
     * @Route("/user/finances/withdraw/cancel/{withdrawId}", name="financial_withdraw_cancel")
     * @Template
     * @param Request $request
     * @param UserRestrictionService $UserRestrictionService
     * @param ContainerInterface $container
     *
     * @return RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function withdrawAction(Request $request, UserRestrictionService $UserRestrictionService, ContainerInterface $container)
    {
        $em           = $this->getDoctrine()->getManager();
        /** @var UserInfo $user */
        $user         = $this->getUser();
        $withdrawForm = $this->createForm(new UserWithdrawType($user));
        $helper       = $this->get('service.helper');

        /** @var Session $session */
        $session                     = $request->getSession();
        $showInstantWithdrawalsModal = false;
        $showWrongEmailModal         = false;

        if (!$UserRestrictionService->canWithdrawInstantly() && $session->getFlashBag()->get(MembershipSourceHelper::SUB_SOURCE_INSTANT_WITHDRAWALS_MODAL)) {
            $showInstantWithdrawalsModal = true;
        }

        // If withdraw id exists, they want to delete
        if ($request->get('withdrawId')) {
            if (
                $withdrawToCancel = $em->getRepository('App:UserWithdraw')
                    ->find($request->get('withdrawId'))
            ) {
                if (
                    $withdrawToCancel->getStatus() === UserWithdraw::WITHDRAW_STATUS_PENDING ||
                    $withdrawToCancel->getStatus() === UserWithdraw::WITHDRAW_STATUS_CANCELLED ||
                    $withdrawToCancel->getStatus() === UserWithdraw::WITHDRAW_STATUS_WAITING_APPROVE
                ) {
                    $withdrawToCancel->setStatus(UserWithdraw::WITHDRAW_STATUS_CANCELLED);
                    $withdrawToCancel->setStatusReason('User requested cancellation');
                } else {
                    $withdrawToCancel->setStatus(UserWithdraw::WITHDRAW_STATUS_CANCEL_REQUESTED);
                    $withdrawToCancel->setStatusReason('User requested cancellation');
                }
                $em->persist($withdrawToCancel);
                $em->flush();
            }

            $request->query->set('notice', 'Your withdrawal request has been cancelled');
        }
        if ($user && $user->isVerificationsExists() && !$user->isVerified() && !$user->isRequestedVerificationRecently()) {
            $this->get('session')->getFlashBag()->add('error', 'Your latest verification attempt failed. Please, try again.');
        }

        $walletBalance          = $user->getWallet();
        $lastWithdrawalRequest  = $em->getRepository(UserWithdraw::class)->findLastWithdrawal($user);

        if ($request->isMethod('POST')) {
            if (!$user->isVerified()) {
                $this->get('session')->getFlashBag()->add('error', 'For your safety & security, we require identity verification before proceeding.');
                return $this->redirect($this->generateUrl('financial_withdraw'));
            }

            // Prepare money string
            $formData           = $request->get($withdrawForm->getName());
            $formData['amount'] = $helper->getMoneyAsInt($formData['amount']);
            $withdrawForm->handleRequest($formData);

            if ($withdrawForm->isValid()) {
                /** @var UserWithdraw $newWithdraw */
                $newWithdraw = $withdrawForm->getData();

                $blocked = $container->getParameter('withdraw_emails');

                if (in_array($newWithdraw->getPaypalEmail(), $blocked) || in_array($user->getWithdrawEmail(), $blocked)) {
                    error_log("WITHDRAW: User from blocklist tried to make withdraw");
                    $body = $this->container->get('templating')->render('VocalizrAppBundle:Mail:paypalAlertWithdraw.html.twig', [
                        'user' => $user,
                        'paymentEmail' => $newWithdraw->getPaypalEmail(),
                        'paymentAmount' => $newWithdraw->getAmount(),
                    ]);

                    $this->get('vocalizr_app.service.mandrill')->sendWithdrawAlert($body, $this->container->getParameter('kernel.environment'));
                }

                if (
                    $formData['amount'] <= 0 || ($formData['amount'] * 100) > $user->getWallet()
                ) {
                    if ($user->getWallet() > 0) {
                        $request->query->set('error', 'Invalid amount entered. Enter an amount less than or equal to $' . number_format($user->getWallet() / 100, 2, '.', ''));
                    } else {
                        $request->query->set('error', 'Invalid amount entered. Your wallet is empty.');
                    }
                } elseif (!$UserRestrictionService->canWithdrawOnEmail($newWithdraw->getPaypalEmail())) {
                    $showWrongEmailModal = true;
                } else {
                    $newWithdraw->setUserInfo($user);
                    $newWithdraw->setStatus(UserWithdraw::WITHDRAW_STATUS_WAITING_APPROVE);
                    $newWithdraw->setDescription('PayPal withdrawal to email ' . $newWithdraw->getPaypalEmail());
                    $newWithdraw->setAmount($formData['amount'] * 100); // In cents
                    $em->persist($newWithdraw);

                    if (!$user->getWithdrawEmail()) {
                        $user->setWithdrawEmail($newWithdraw->getPaypalEmail());
                    }
                    $em->flush($newWithdraw);

                    $this->createWithdrawRequestWalletTransaction($newWithdraw);

                    // Reset form
                    $withdrawForm = $this->createForm(new UserWithdrawType($user));

                    $msg = 'Your withdrawal request has been successfully submitted. It should be proccessed in the next 3-5 days.';
                    if ($user->isSubscribed()) {
                        $msg = 'Your withdrawal request has been successfully submitted. It should hit your account shortly.';
                    }
                    $this->get('session')->getFlashBag()->add('notice', $msg);
                    return $this->redirect($this->generateUrl('financial_withdraw'));
                }
            } else {
                $this->get('session')->getFlashBag()->add('error', $withdrawForm->getErrorsAsString());
            }
        } else {
            $withdrawForm->get('amount')->setData($user->getWallet() / 100);
        }

        // Get withdraw requests
        $withdrawRequests = $em->getRepository('App:UserWithdraw')
                ->getByUserQuery($user->getId());

        $paginator  = $paginator->get('knp_paginator');
        $pagination = $paginator->paginate(
            $withdrawRequests,
            $this->get('request')->query->get('page', 1)/*page number*/,
            10// limit per page
        );

        return $this->render('Financial/withdraw.html.twig', [
            'withdrawRequests'      => $withdrawRequests,
            'withdrawForm'          => $withdrawForm->createView(),
            'walletBalance'         => $walletBalance,
            'lastWithdrawelRequest' => $lastWithdrawalRequest,
            'pagination'            => $pagination,
            'displayModal'          => $showInstantWithdrawalsModal,
            'wrongEmailModal'       => $showWrongEmailModal,
        ]);
    }

    /**
     * @Route("/user/finances/refund/contest/{uuid}/refund", name="finances_contest_refund")
     * @Template
     *
     * @param Request $request
     * @param string  $uuid
     */
    public function refundAction(Request $request, $uuid)
    {
        $user = $this->getUser();
        $em   = $this->getDoctrine()->getManager();

        /** @var Project|null $project */
        $project = $em->getRepository('App:Project')
            ->findOneBy([
                'user_info'    => $user,
                'uuid'         => $uuid,
                'project_type' => Project::PROJECT_TYPE_CONTEST,
            ])
        ;

        if (!$project) {
            return $this->forward('VocalizrAppBundle:Default:error', [
                'error' => 'Invalid contest',
            ]);
        }

        $projectEscrow = $project->getProjectEscrow();
        // Make sure project still has escrow
        if ($projectEscrow->getRefunded()) {
            return $this->forward('VocalizrAppBundle:Default:error', [
                'error' => 'This contest has already been refunded',
            ]);
        }

        // If confirming the refund
        if ($request->isMethod('POST')) {
            // Make sure it hasn't been awarded
            if ($project->getProjectBid()) {
                return $this->forward('VocalizrAppBundle:Default:error', [
                    'error' => 'You cannot get a refund on a contest that has been awarded',
                ]);
            }

            $refund = $this->get('vocalizr_app.model.project')->deactivateProject($project);

            if ($project->getProjectType() === Project::PROJECT_TYPE_CONTEST) {
                foreach ($project->getProjectBids() as $projectBid) {
                    $this->get('vocalizr_app.service.mandrill')->sendMessage(
                        $projectBid->getUserInfo()->getEmail(),
                        'Contest Closed Early',
                        'contest-closed-early',
                        [
                            'contest' => $project->getTitle(),
                        ]
                    );
                }
            }

            // Redirect
            if ($refund === false) {
                $this->get('session')->getFlashBag()->add('error', 'Refund error');
            } else {
                $this->get('session')->getFlashBag()->add('notice', 'We have refunded $' . (number_format($project->getBudgetTo(), 2)) . ' into your card');
            }
            return $this->redirect($this->generateUrl('user_financial'));
        }

        return [
            'project' => $project,
        ];
    }

    /**
     * @param UserWithdraw $newWithdraw
     * @return UserWalletTransaction
     */
    private function createWithdrawRequestWalletTransaction(UserWithdraw $newWithdraw)
    {
        $em = $this->getDoctrine()->getManager();

        $transaction = new UserWalletTransaction();
        $transaction
            ->setAmount('-' . $newWithdraw->getAmount())
            ->setType(UserWalletTransaction::TYPE_WITHDRAW_REQUEST)
            ->setUserInfo($newWithdraw->getUserInfo())
            ->setCurrency($this->container->getParameter('default_currency'))
            ->setDescription($newWithdraw->getDescription())
            ->setEmail($newWithdraw->getPaypalEmail())
            ->setCustomId(UserWalletTransaction::TYPE_WITHDRAW_REQUEST . '_' . $newWithdraw->getId())
            ->setData(json_encode([
                'status'        => $newWithdraw->getStatus(),
                'withdraw_id'   => $newWithdraw->getId(),
                'status_string' => $newWithdraw->getStatusString(),
            ]))
        ;
        $em->persist($transaction);
        $em->flush();

        return $transaction;
    }

    /**
     * @param UserInfo $user
     * @return float|int
     */
    private function getWithheldBalance(UserInfo $user)
    {
        $notFinishedWithdrawals = $this
            ->getDoctrine()
            ->getManager()
            ->getRepository(UserWithdraw::class)->findByStatusesAndUser($user, [
            UserWithdraw::WITHDRAW_STATUS_PENDING,
            UserWithdraw::WITHDRAW_STATUS_IN_PROGRESS,
            UserWithdraw::WITHDRAW_STATUS_WAITING_APPROVE,
        ]);

        $balance = 0;
        foreach ($notFinishedWithdrawals as $withdraw) {
            $txn = $this->getDoctrine()->getRepository(UserWalletTransaction::class)
                ->findByCustomId('WITHDRAW_' . $withdraw->getId());
            if (!$txn) {
                continue;
            }
            $balance += $withdraw->getAmount();
        }
        return $balance;
    }
}
