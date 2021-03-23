<?php


namespace Vocalizr\AppBundle\Controller;


use Doctrine\ORM\EntityManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Router;
use Vocalizr\AppBundle\Entity\PaymentSessionData;
use Vocalizr\AppBundle\Entity\UserCertification;
use Vocalizr\AppBundle\Entity\UserInfo;
use Vocalizr\AppBundle\Form\Type\UserCertifiedType;
use Vocalizr\AppBundle\Service\StripeConfigurationProvider;
use Vocalizr\AppBundle\Service\StripeManager;

class UserCertificationController extends Controller
{

    /**
     * @Route("/getcertified", name="get_certified")
     * @Template()
     */
    public function getCertifiedAction()
    {
        /** @var UserInfo $currentUser */
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->redirect($this->generateUrl('login'));
        } elseif ($currentUser->getIsCertified()) {
            return $this->render('@VocalizrApp/Certified/after_user_certified.html.twig', ['isCertified' => true]);
        }
        /** @var EntityManager $em */
        $em      = $this->getDoctrine()->getManager();
        /** @var Request $request */
        $request = $this->getRequest();

        if (!is_null($request->get('paid'))) {
            return $this->render('@VocalizrApp/Certified/after_user_certified.html.twig');
        }
        $userCertified = new UserCertification();
        $userCertifiedForm = $this->createForm(new UserCertifiedType(), $userCertified);
        /** @var StripeManager $stripe */
        $stripe = $this->get('vocalizr_app.stripe_manager');
        /** @var StripeConfigurationProvider $config */
        $config = $this->get('vocalizr_app.stripe_configuration_provider');
        $returnUrl = $request->headers->get('referer', $this->generateUrl('dashboard', [], Router::ABSOLUTE_URL));
        $paymentSessionData = $em->getRepository('VocalizrAppBundle:PaymentSessionData')
            ->findOneByUserForCertification($currentUser);
        if ($paymentSessionData && ($paymentSessionData->getUserCertification()->getPaid() && $paymentSessionData->getUserCertification()->getSucceed() === null)) {
            return $this->render('@VocalizrApp/Certified/after_user_certified.html.twig');
        }
        if ($request->get('certifiedUser')) {
            $userCertifiedForm->bind($request);

            if ($userCertifiedForm->isValid()) {

                $userCertified->setUserInfo($currentUser);
                $userCertified->setCreatedAt(new \DateTime());
                $prices = $config->getProductPrices('certified_user');
                $session = $stripe->getStripeSession(
                    $userCertified->getUserInfo(),
                    [[
                        'price'    => $prices['yearly'],
                        'quantity' => 1,
                    ]],
                    $returnUrl . '?paid=true',
                    $returnUrl . '?paid=false',
                    'subscription'
                );
                $paymentSessionData = new PaymentSessionData();
                $paymentSessionData->setUser($currentUser);
                $paymentSessionData->setSessionId($session['id']);
                $paymentSessionData->setUserCertification($userCertified);
                $em->persist($userCertified);
                $em->persist($paymentSessionData);
                $em->flush();
                return new JsonResponse($session);
            }
        }
        return $this->render(
            '@VocalizrApp/Certified/certified_user_get_certified.html.twig',
            [
                'userCertifiedForm' => $userCertifiedForm->createView(),
                'user' => $currentUser
            ]
        );
    }

}
