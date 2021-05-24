<?php


namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\PayPalTransaction;
use App\Entity\Project;
use App\Entity\StripeCharge;
use App\Entity\UserInfo;
use App\Entity\UserWalletTransaction;
use App\Service\MembershipSourceHelper;
use App\Service\RevenueManager;
use Symfony\Component\HttpFoundation\Response;

class AdminRevenueController extends AbstractController
{
    /**
     * @Route("/admin/revenue", name="admin_revenue")
     * @param RevenueManager $revenueManager
     *
     * @return Response
     */
    public function indexAction(RevenueManager $revenueManager)
    {
        $this->checkAdmin();

        $revenueCurrentMonth = $revenueManager->revenueCurrentMonth();
        $revenueAllTime = $revenueManager->revenueAllTime();

        return $this->render('Admin/admin_revenue.html.twig', [
            'revenueCurrentMonth' => $revenueCurrentMonth,
            'revenueAllTime'      => $revenueAllTime
        ]);
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