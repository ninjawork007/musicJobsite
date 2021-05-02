<?php

namespace App\Controller;

use App\Service\MembershipSourceHelper;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Object\MembershipSourceObject;
use App\Service\PayPalService;

/**
 * Class PaypalController
 *
 * @package App\Controller
 */
class PaypalController extends AbstractController
{
    /**
     * Paypal IPN
     *
     * @Route("/paypal/ipn", name="paypal_ipn")
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function paypalIpnAction(Request $request, PayPalService $payPalService)
    {
//        /** @var PayPalService $payPalService */
//        $payPalService = $this->container->get('service.paypal');
        if ($request->getMethod() == 'POST') {
            $payPalService->processIpn();
        }

        /*
        $str = 'a:30:{s:8:"txn_type";s:13:"subscr_signup";s:9:"subscr_id";s:14:"I-4TV32R9Y70XM";s:9:"last_name";s:6:"Smythe";s:17:"option_selection1";s:11:"Pay Monthly";s:17:"residence_country";s:2:"AU";s:11:"mc_currency";s:3:"USD";s:9:"item_name";s:25:"Vocalizr PRO Subscription";s:7:"amount1";s:4:"0.00";s:8:"business";s:21:"payments@vocalizr.com";s:7:"amount3";s:4:"9.00";s:9:"recurring";s:1:"1";s:11:"verify_sign";s:56:"AAf8mV68Q-rlcfs2HGr7Dtk95pSbAZwAH.CU588ZNJT77Op0k7oIOco-";s:12:"payer_status";s:8:"verified";s:11:"payer_email";s:30:"j.smythe@jswebsolutions.com.au";s:10:"first_name";s:4:"John";s:14:"receiver_email";s:21:"payments@vocalizr.com";s:8:"payer_id";s:13:"4QBFD9MTT64LL";s:12:"option_name1";s:9:"Frequency";s:9:"reattempt";s:1:"1";s:11:"item_number";s:9:"PRO_TRIAL";s:11:"subscr_date";s:25:"02:01:30 Feb 11, 2018 PST";s:6:"btn_id";s:9:"150323081";s:6:"custom";s:24:"u55b6d84ab8a050.43542796";s:7:"charset";s:12:"windows-1252";s:14:"notify_version";s:3:"3.8";s:7:"period1";s:3:"1 M";s:10:"mc_amount1";s:4:"0.00";s:7:"period3";s:3:"1 M";s:10:"mc_amount3";s:4:"9.00";s:12:"ipn_track_id";s:13:"5f2a2a516c2a5";}';
        $data = unserialize($str);
        $payPalService->processIpn($data);
        /**
         * LOCAL TESTING
        // Sign up
        $str = "amount1=0.00&amount3=9.00&address_status=unconfirmed&subscr_date=13%3A25%3A09+Feb+08%2C+2018+PST&payer_id=PNZT8E8YGEV3W&address_street=1+Cheeseman+Ave+Brighton+East&mc_amount1=0.00&mc_amount3=9.00&charset=windows-1252&address_zip=3001&first_name=test&reattempt=1&address_country_code=AU&address_name=test+buyer&notify_version=3.8&subscr_id=I-D6KW6SUFA2KP&custom=u5343db8e75fb38.96199589&payer_status=verified&business=payments-facilitator%40vocalizr.com&address_country=Australia&address_city=Melbourne&verify_sign=AFGoTmaSzwI5h82KBXRnoScay7oPA7vVfmvqOj8V4jNCiRCuPDt0YO-L&payer_email=payments-buyer%40vocalizr.com&btn_id=3835950&last_name=buyer&address_state=Victoria&receiver_email=payments-facilitator%40vocalizr.com&recurring=1&txn_type=subscr_signup&item_name=Vocalizr+PRO+-+Monthly&mc_currency=USD&item_number=PRO&residence_country=AU&test_ipn=1&period1=1+M&period3=1+M&ipn_track_id=49d659b951e";

        // Payment
        $str = "mc_gross=9.00&protection_eligibility=Eligible&address_status=unconfirmed&payer_id=PNZT8E8YGEV3W&address_street=1+Cheeseman+Ave+Brighton+East&payment_date=14%3A48%3A34+Feb+08%2C+2018+PST&payment_status=Completed&charset=windows-1252&address_zip=3001&first_name=test&mc_fee=0.52&address_country_code=AU&address_name=test+buyer&notify_version=3.8&subscr_id=I-FVS4JP9HBAJT&custom=u5343db8e75fb38.96199589&payer_status=verified&business=payments-facilitator%40vocalizr.com&address_country=Australia&address_city=Melbourne&verify_sign=A9.v3Z12UkkZ8kOnhrv2qAmjnv2SAYlB-49Y-VARG5Y1OGyHEjMhDwts&payer_email=payments-buyer%40vocalizr.com&txn_id=3UM92255EP1890011&payment_type=instant&btn_id=3835963&last_name=buyer&address_state=Victoria&receiver_email=payments-facilitator%40vocalizr.com&payment_fee=0.52&receiver_id=YRDCSLDJ6TZKU&txn_type=subscr_payment&item_name=Vocalizr+PRO+-+Monthly+-+TEST&mc_currency=USD&item_number=PRO&residence_country=AU&test_ipn=1&transaction_subject=Vocalizr+PRO+-+Monthly+-+TEST&payment_gross=9.00&ipn_track_id=bde2beb47f6e2";

        // Cancel
        //$str = "amount1=0.00&amount3=9.00&address_status=unconfirmed&subscr_date=14%3A20%3A54+Feb+08%2C+2018+PST&payer_id=PNZT8E8YGEV3W&address_street=1+Cheeseman+Ave+Brighton+East&mc_amount1=0.00&mc_amount3=9.00&charset=windows-1252&address_zip=3001&first_name=test&reattempt=1&address_country_code=AU&address_name=test+buyer&notify_version=3.8&subscr_id=I-D6KW6SUFA2KP&custom=u5343db8e75fb38.96199589&payer_status=verified&business=payments-facilitator%40vocalizr.com&address_country=Australia&address_city=Melbourne&verify_sign=A2X.vtnsjfTZd.PC7PrwBhtuirHiA03jXa1-FyuMjFQp.lsESQIsWxOO&payer_email=payments-buyer%40vocalizr.com&btn_id=3835950&last_name=buyer&address_state=Victoria&receiver_email=payments-facilitator%40vocalizr.com&recurring=1&txn_type=subscr_cancel&item_name=Vocalizr+PRO+-+Monthly&mc_currency=USD&item_number=PRO&residence_country=AU&test_ipn=1&period1=1+M&period3=1+M&ipn_track_id=a29a93953bc3f";

        parse_str($str, $data);
        $payPalService->processIpn($data);

        exit;
         *
         */
        return new JsonResponse(['success' => true]);
    }

    /**
     * Paypal Membership Successful
     *
     * @Route("/pp/success", name="paypal_pro_success")
     * @Template
     *
     * @param Request $request
     *
     * @return Response|RedirectResponse
     */
    public function proSuccessAction(Request $request, MembershipSourceHelper $tracker)
    {
        $isStripe = (bool) $request->get('stripe');

        $user = $this->getUser();

//        $tracker = $this->container->get('vocalizr_app.service.membership_source_helper');
        $track   = $tracker->getSource();
        $tracker->delete();

        if (!$user) {
            return $this->redirect($this->generateUrl('home'));
        }
        if (!$user->getDateRegistered()) {
            return $this->redirect($this->generateUrl('onboarding-success'));
        }

        if ($track->isNeedReturn()) {
            if (
                ($isStripe && $track->getStatus() === MembershipSourceObject::STATUS_START_PAYING) ||
                (!$isStripe && $track->getStatus() > MembershipSourceObject::STATUS_MEMBERSHIP_PAGE)
            ) {
                return $this->redirect($track->getReturnUrl());
            }
        }

        return $this->render('Paypal/proSuccess.html.twig', [
            'user'  => $user,
            'track' => $track,
        ]);
    }

    /**
     * Paypal PRO Promo
     *
     * @Route("/paypal/pro/promo/{plan}", name="paypal_pro_promo", defaults={"plan" = ""})
     * @Template
     *
     * @param Request $request
     *
     * @return array|RedirectResponse|Response
     */
    public function promoAction(Request $request)
    {
        $user = $this->getUser();

        if (date('Y-m-d') > '2018-04-10') {
            return $this->redirect('/');
        }

        if ($request->get('plan') && $this->getUser()) {
            return $this->render('Paypal/promoRedirect.html.twig');
        }

        $em      = $this->getDoctrine()->getManager();
        $proPlan = $em->getRepository('App:SubscriptionPlan')->findOneBy([
            'static_key' => 'PRO',
        ]);

        return ['proPlan' => $proPlan];
    }

    /**
     * Paypal PRO Promo
     *
     * @Route("/paypal/pro/login", name="paypal_pro_login")
     * @Template
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function promoLoginAction(Request $request)
    {
        return $this->redirect($this->generateUrl('paypal_pro_promo'));
    }
}
