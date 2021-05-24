<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\MembershipSourceHelper;

/**
 * Class PromoController
 * @package App\Controller
 */
class PromoController extends AbstractController
{
    /**
     * @Route(name="promo_stay_home", path="/stayhome")
     *
     * @param Request $request
     * @return Response
     */
    public function stayHomeAction(Request $request)
    {
        $sourceHelper = $this->get('vocalizr_app.service.membership_source_helper');
        $userSource = $sourceHelper->handleRequest($request);
        $userSource->setSource(MembershipSourceHelper::SUB_SOURCE_STAYHOME_PROMO);
        $sourceHelper->setSource($userSource);

        return $this->render('Promo/stayHome.html.twig', [
            'user_source' => $userSource,
            'button_id'   => $this->container->getParameter('paypal_stayhome_button_id'),
        ]);
    }

    /**
     * @Route(name="promo_giveaway", path="/giveaway")
     *
     * @return Response
     */
    public function giveawayAction()
    {
        return $this->render('Promo/giveaway.html.twig');
    }
}