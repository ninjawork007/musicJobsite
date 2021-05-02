<?php


namespace App\Controller;


use Doctrine\ORM\EntityManager;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Router;
use App\Entity\PaymentSessionData;
use App\Entity\UserCertification;
use App\Entity\UserInfo;
use App\Form\Type\UserCertifiedType;
use App\Service\StripeConfigurationProvider;
use App\Service\StripeManager;

class UserCertificationController extends AbstractController
{

    /**
     * @Route("/getcertified", name="get_certified")
     *
     * @param Request $request
     * @param StripeManager $stripe
     * @param StripeConfigurationProvider $config
     * @return JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getCertifiedAction(Request $request, StripeManager $stripe, StripeConfigurationProvider $config)
    {
        /** @var UserInfo $currentUser */
        $currentUser = $this->getUser();
        if (!$currentUser) {
            return $this->redirect($this->generateUrl('login'));
        } elseif ($currentUser->getIsCertified()) {
            return $this->render('Certified/after_user_certified.html.twig', ['isCertified' => true]);
        }
        /** @var EntityManager $em */
        $em      = $this->getDoctrine()->getManager();

        if (!is_null($request->get('paid'))) {
            return $this->render('Certified/after_user_certified.html.twig');
        }
        $userCertified = new UserCertification();
        $userCertifiedForm = $this->createForm(UserCertifiedType::class, $userCertified);

        $returnUrl = $request->headers->get('referer', $this->generateUrl('dashboard', [], Router::ABSOLUTE_URL));
        $paymentSessionData = $em->getRepository('App:PaymentSessionData')
            ->findOneByUserForCertification($currentUser);
        if ($paymentSessionData && ($paymentSessionData->getUserCertification()->getPaid() && $paymentSessionData->getUserCertification()->getSucceed() === null)) {
            return $this->render('Certified/after_user_certified.html.twig');
        }
        if ($request->get('certifiedUser')) {
            $userCertifiedForm->handleRequest($request);

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
            'Certified/certified_user_get_certified.html.twig',
            [
                'userCertifiedForm' => $userCertifiedForm->createView(),
                'user' => $currentUser
            ]
        );
    }

}
