<?php

namespace App\Controller;

use App\Model\UserInfoModel;
use App\Service\PayPalApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Entity\Project;
use App\Entity\UserActionAudit;
use App\Entity\UserWalletTransaction;
use App\Entity\UserWithdraw;

class AdminWithdrawalController extends AbstractController
{
    /**
     * @Route("/admin/withdraw/approvals", name="admin_withdraw_approvals")
     *
     * @param Request            $request
     * @param ContainerInterface $container
     *
     * @return Response
     */
    public function adminWithdrawsList(Request $request, ContainerInterface $container)
    {
        // check the logged in user is an admin
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            $responseData = ['success' => false,
                'message'              => 'Invalid Access', ];
            return new Response(json_encode($responseData));
        }

        $em = $this->getDoctrine()->getManager();
        $query = $em->getRepository(UserWithdraw::class)->findByStatus(UserWithdraw::WITHDRAW_STATUS_WAITING_APPROVE);

        $result = [];

        /** @var UserWithdraw $withdraw */
        foreach ($query->getResult() as $withdraw) {
            $withdrawer = $withdraw->getUserInfo();

            /** @var Project $lastAwardedProject */
            $lastAwardedProject = $em->getRepository(Project::class)->findLastCompletedProjectWhereUserEmployee($withdrawer);

            $auditRepo = $em->getRepository(UserActionAudit::class);

            $sameIps = null;
            $userHasProjectWith = null;

            if ($lastAwardedProject) {
                $userHasProjectWith = $lastAwardedProject->getUserInfo();
            }

            /** @var UserActionAudit $withdrawAudit */
            $withdrawAudit = $auditRepo->findLatestMatchingAuditRecord(
                UserActionAudit::ACTION_WITHDRAW,
                $withdrawer,
                null,
                $withdraw
            );

            $escrowAudit = null;
            if ($userHasProjectWith) {
                /** @var UserActionAudit $escrowAudit */
                $escrowAudit = $auditRepo->findLatestMatchingAuditRecord(
                    UserActionAudit::ACTION_PROJECT_RELEASE_ESCROW,
                    $userHasProjectWith,
                    $lastAwardedProject,
                    null
                );

                $sameIps = $this->compareIps(
                    $userHasProjectWith->getLoginIp(),
                    $withdrawer->getLoginIp()
                );
            }

            $result[] = [
                'withdraw'          => $withdraw,
                'userProjectWith'   => $userHasProjectWith,
                'project'           => $lastAwardedProject,
                'sameIps'           => $sameIps,
                'withdrawIp'        => $withdrawAudit ? $withdrawAudit->getIpAddress() : $withdrawer->getLoginIp(),
                'escrowIp'          => $escrowAudit ? $escrowAudit->getIpAddress() : ($userHasProjectWith
                    ? $userHasProjectWith->getLoginIp(): ''),
            ];
        }

        $paginator  = $container->get('knp_paginator');
        $pagination = $paginator->paginate(
            $result,
            $request->query->get('page', 1)/*page number*/,
            20// items per page
        );

        return $this->render('Admin/withdrawels_waiting_for_approve.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    /**
     * @param $id
     * @return Response
     *
     *
     * @Route("/admin/withdraw/{id}/approve", name="admin_withdraw_approve")
     */
    public function adminApproveWithdraw($id)
    {
        // check the logged in user is an admin
        if (!$this->getUser()->getIsAdmin()) {
            throw new AccessDeniedException();
        }
        $em = $this->getDoctrine()->getManager();

        /** @var UserWithdraw $withdraw */
        $withdraw = $em->getRepository(UserWithdraw::class)->find($id);
        if (!$withdraw) {
            $this->addFlash('error', 'Withdraw not found');
            return $this->redirect($this->generateUrl('admin_withdraw_approvals'));
        }
        if ($withdraw->getStatus() !== UserWithdraw::WITHDRAW_STATUS_WAITING_APPROVE) {
            $this->addFlash('error', 'Invalid withdraw status');
            return $this->redirect($this->generateUrl('admin_withdraw_approvals'));
        }

        $withdraw->setStatus(UserWithdraw::WITHDRAW_STATUS_PENDING);
        $em->flush();

        $this->addFlash('notice', 'Withdraw approved.');
        return $this->redirect($this->generateUrl('admin_withdraw_approvals'));
    }

    /**
     * @return Response
     *
     * @Route("/admin/withdraw/approve", name="admin_withdraw_approve_all")
     */
    public function adminApproveAllWithdraws()
    {
        // check the logged in user is an admin
        if (!$this->getUser()->getIsAdmin()) {
            throw new AccessDeniedException();
        }
        $em = $this->getDoctrine()->getManager();

        $withdraws = $em->getRepository(UserWithdraw::class)->findByStatus(UserWithdraw::WITHDRAW_STATUS_WAITING_APPROVE);
        $withdraws = $withdraws->getResult();
        
        if (!$withdraws) {
            $this->addFlash('error', 'Withdraws not found');
            return $this->redirect($this->generateUrl('admin_withdraw_approvals'));
        }

        /** @var UserWithdraw $withdraw */
        foreach ($withdraws as $withdraw) {
            $withdraw->setStatus(UserWithdraw::WITHDRAW_STATUS_PENDING);
            $em->persist($withdraw);
        }
        $em->flush();

        $this->addFlash('notice', 'Withdraws approved.');
        return $this->redirect($this->generateUrl('admin_withdraw_approvals'));
    }


    /**
     * @param $id
     * @param UserInfoModel     $userInfoModel
     * @param PayPalApiService  $ppApiService
     * @return Response
     *
     * @Route("/admin/withdraw/{id}/deny", name="admin_withdraw_deny")
     */
    public function adminDenyWithdraw($id, UserInfoModel $userInfoModel, PayPalApiService $ppApiService)
    {
        // check the logged in user is an admin
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            throw new AccessDeniedException();
        }
        $em = $this->getDoctrine()->getManager();

        /** @var UserWithdraw $withdraw */
        $withdraw = $em->getRepository(UserWithdraw::class)->find($id);
        if (!$withdraw) {
            $this->addFlash('error', 'Withdraw not found');
            return $this->redirect($this->generateUrl('admin_withdraw_approvals'));
        }
        if ($withdraw->getStatus() !== UserWithdraw::WITHDRAW_STATUS_WAITING_APPROVE) {
            $this->addFlash('error', 'Invalid withdraw status');
            return $this->redirect($this->generateUrl('admin_withdraw_approvals'));
        }

        if (
            $lastAwardedProject = $em->getRepository(Project::class)->findLastCompletedProjectWhereUserEmployee($withdraw->getUserInfo())
        ) {
            $projectCreatorDeposits = $em->getRepository(UserWalletTransaction::class)
                ->findTransactionsByTypeAndUser(UserWalletTransaction::TYPE_DEPOSIT, $lastAwardedProject->getUserInfo());

            $userInfoModel->deactivate($lastAwardedProject->getUserInfo());

//            $ppApiService = $this->get('vocalizr_app.paypal_api');

            foreach ($projectCreatorDeposits as $deposit) {
                $txnId = explode('(',$deposit->getDescription());
                $txnId = trim(array_pop($txnId), ')');
                $ppApiService->refundTransaction($txnId);
            }
        }
        $withdraw->setStatus(UserWithdraw::WITHDRAW_STATUS_CANCELLED);
        $userInfoModel->deactivate($withdraw->getUserInfo());

        $em->flush();

        $this->addFlash('notice', 'Withdraw denies. Users are blocked. All U2 deposits refunded.');
        return $this->redirect($this->generateUrl('admin_withdraw_approvals'));
    }

    /**
     * @param $ip1
     * @param $ip2
     * @return bool
     */
    private function compareIps($ip1, $ip2)
    {
        $ipParts = explode('.', $ip2);

        foreach (explode('.', $ip1) as $key => $ipPart) {
            if ($key > 2) {
                break;
            }
            if ($ipPart !== $ipParts[$key]) {
                return false;
            }
        }
        return true;
    }
}