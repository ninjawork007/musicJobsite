<?php


namespace Vocalizr\AppBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;

class AdminGstController extends Controller
{

    /**
     * @Route("/admin/gst", name="admin_gst")
     *
     * @param Request $request
     * @return Response
     */
    public function adminGst(Request $request)
    {
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            return $this->redirect($this->generateUrl('dashboard'));
        }
        $years = $this->getDoctrine()->getManager()->getRepository('VocalizrAppBundle:UserWalletTransaction')
            ->findYearsWithRecords();

        return $this->render('@VocalizrApp/Admin/user_gst.html.twig', ['years' => $years]);
    }


    /**
     * @Route("/admin/gst/gig-comissions", name="admin_gst_gig_commissions")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function adminGstGigCommissions(Request $request)
    {
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            throw new AccessDeniedHttpException();
        }

        $year = trim($request->get('year')[0]);
        $quarter = trim($request->get('quarter')[0]);

        if ($year == '' || $quarter == '') {

            $responseData = [
                'success' => false,
                'message' => 'No search year or quarter defined'
            ];

            return new JsonResponse($responseData);
        }

        $gigCommissions = $this->getDoctrine()->getManager()->getRepository('VocalizrAppBundle:UserWalletTransaction')
            ->findGigCommissionsByYearAndQuarter($year, $quarter);

        return new JsonResponse([
            'success'    => true,
            'html'       => $this->renderView(
                'VocalizrAppBundle:Admin:adminGstGigCommissionsResults.html.twig',
                ['results' => $gigCommissions, 'year' => $year, 'quarter' => $quarter]
            ),
        ]);
    }

    /**
     * @Route("/admin/gst/upgrades", name="admin_gst_upgrades")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function adminGstUpgrades(Request $request)
    {
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            throw new AccessDeniedHttpException();
        }

        $year = trim($request->get('year')[0]);
        $quarter = trim($request->get('quarter')[0]);

        if ($year == '' || $quarter == '') {

            $responseData = [
                'success' => false,
                'message' => 'No search year or quarter defined'
            ];

            return new JsonResponse($responseData);
        }

        $gigCommissions = $this->getDoctrine()->getManager()->getRepository('VocalizrAppBundle:UserWalletTransaction')
            ->findUpgradesByYearAndQuarter($year, $quarter);

        return new JsonResponse([
            'success'    => true,
            'html'       => $this->renderView(
                'VocalizrAppBundle:Admin:adminGstUpgradesResults.html.twig',
                ['results' => $gigCommissions, 'year' => $year, 'quarter' => $quarter]
            ),
        ]);
    }

    /**
     * @Route("/admin/gst/subscriptions", name="admin_gst_subscriptions")
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function adminGstSubscriptions(Request $request)
    {
        if (!$this->getUser() || !$this->getUser()->getIsAdmin()) {
            throw new AccessDeniedHttpException();
        }

        $year = trim($request->get('year')[0]);
        $months = trim($request->get('months')[0]);

        if ($year == '' || $months == '') {

            $responseData = [
                'success' => false,
                'message' => 'No search year or month defined'
            ];

            return new JsonResponse($responseData);
        }

        $subs = $this->getDoctrine()->getManager()->getRepository('VocalizrAppBundle:UserSubscription')
            ->findSubscriptionsByYearAndMonths($year, $months);

        return new JsonResponse([
            'success'    => true,
            'html'       => $this->renderView(
                'VocalizrAppBundle:Admin:adminGstSubscriptionsResults.html.twig',
                ['results' => $subs, 'year' => $year, 'months' => $months]
            ),
        ]);
    }

}