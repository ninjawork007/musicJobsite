<?php


namespace Vocalizr\AppBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Vocalizr\AppBundle\Entity\PayPalTransaction;
use Vocalizr\AppBundle\Entity\Project;
use Vocalizr\AppBundle\Entity\StripeCharge;
use Vocalizr\AppBundle\Entity\UserInfo;
use Vocalizr\AppBundle\Entity\UserWalletTransaction;
use Vocalizr\AppBundle\Service\MembershipSourceHelper;
use Vocalizr\AppBundle\Service\RevenueManager;

class AdminRevenueController extends Controller
{
    /**
     * @Route("/admin/revenue", name="admin_revenue")
     */
    public function indexAction()
    {
        $this->checkAdmin();

        $revenueManager = $this->get('vocalizr_app.revenue_manager');

        $revenueCurrentMonth = $revenueManager->revenueCurrentMonth();
        $revenueAllTime = $revenueManager->revenueAllTime();

        return $this->render('@VocalizrApp/Admin/admin_revenue.html.twig', ['revenueCurrentMonth' => $revenueCurrentMonth, 'revenueAllTime' => $revenueAllTime]);
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