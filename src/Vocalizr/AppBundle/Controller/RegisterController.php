<?php

namespace Vocalizr\AppBundle\Controller;

use Hip\MandrillBundle\Message;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Stripe\Customer;
use Stripe\Stripe;
use Stripe\Subscription;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use Vocalizr\AppBundle\Entity\UserInfo;
use Vocalizr\AppBundle\Entity\UserSubscription;

use Vocalizr\AppBundle\Form\Type\RegisterType;
use Vocalizr\AppBundle\Object\MembershipSourceObject;
use Vocalizr\AppBundle\Service\MembershipSourceHelper;
use Vocalizr\AppBundle\Service\PayPalService;

/**
 * Class RegisterController
 *
 * @package Vocalizr\AppBundle\Controller
 */
class RegisterController extends Controller
{
    /**
     * @Route("/onboarding/signup", name="register")
     * @Template()
     *
     * @param Request $request
     *
     * @return array|RedirectResponse
     *
     * @throws \Exception
     * @throws \Symfony\Component\Form\Exception\Exception
     */
    public function indexAction(Request $request)
    {
        $user = $this->getUser();
        if ($user) {
            if ($user->getDateRegistered()) {
                return $this->redirect($this->generateUrl('dashboard'));
            }
            return $this->redirect($this->generateUrl('onboarding-membership'));
        }
        if ($this->getUser()) {
            return $this->redirect($this->generateUrl('dashboard'));
        }
        $em = $this->getDoctrine()->getManager();

        $user         = new UserInfo();
        $registerForm = $this->createForm(new RegisterType(), $user);

        if ($request->getMethod() == 'POST') {
            $registerForm->bind($request);
            if ($registerForm->isValid()) {
                /**
                 * Get if email already exists
                 *
                 * @var UserInfo|null $existingUser
                 */
                if ($existingUser = $em->getRepository('VocalizrAppBundle:UserInfo')->findFirstByEmail($user->getEmail())) {
                    if ($existingUser->getEmailConfirmed()) {
                        $request->query->set('error', 'Sorry! Please correct the errors below to complete sign up');
                        $registerForm->get('email')->addError(new FormError('Email already exists'));
                        return ['form' => $registerForm->createView()];
                    }
                    // If email isn't confirmed, then set data as existing user and resend activation
                    $user = $existingUser;
                }

                try {
                    // Set salt
                    $user->initSalt();

                    // encode and set the password for the user,
                    // these settings match our config
                    $encoder  = new MessageDigestPasswordEncoder('sha1', false, 1);
                    $password = $encoder->encodePassword($user->getPassword(), $user->getSalt());
                    $user
                        ->setPassword($password)
                        ->setEmailConfirmed(false)
                        ->setIsActive(true)
                        ->setDateActivated(new \DateTime())
                    ;

                    $em->persist($user);
                    $em->flush();

                    // Now to log user in
                    $firewallName = 'user';
                    $token        = new UsernamePasswordToken(
                        $user,
                        $user->getPassword(),
                        $firewallName,
                        $user->getRoles()
                    );

                    $request->getSession()->set('_security_' . $firewallName, serialize($token));

                    $this->container->get('security.context')->setToken($token);

                    $request->getSession()->set('pro_prompt', 1);

                    $dispatcher = $this->get('hip_mandrill.dispatcher');
                    $message    = new Message();
                    $message
                        ->addTo($user->getEmail())
                        ->addGlobalMergeVar('CONFIRMURL', $this->generateUrl('register_complete', ['unique_str' => $user->getUniqueStr()], true))
                        ->setTrackOpens(true)
                        ->setTrackClicks(true);
                    $result = $dispatcher->send($message, 'register-confirm');

                    if ($result[0]['status'] != 'sent') {
                        throw new \Exception('Sorry! The confirmation email could not be sent. Please try again soon!');
                    }

                    // Now to log user in
                    $firewallName = 'user';
                    $token        = new UsernamePasswordToken(
                        $user,
                        $user->getPassword(),
                        $firewallName,
                        $user->getRoles()
                    );

                    $request->getSession()->set('_security_' . $firewallName, serialize($token));

                    $this->container->get('security.context')->setToken($token);

                    $request->getSession()->set('pro_prompt', 1);
                    return $this->redirect($this->generateUrl('onboarding-verify'));
                } catch (\Exception $e) {
                    $request->query->set('error', $e->getMessage());
                }
            } else {
                $request->query->set('error', 'Sorry! Please correct the errors below to complete sign up');
            }
        }

        return ['form' => $registerForm->createView()];
    }

    /**
     * @Route("onboarding/verify", name="onboarding-verify")
     */
    public function verificationRequiredAction()
    {
        /** @var UserInfo $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirect($this->generateUrl('home'));
        }
        if ($user->getEmailConfirmed()) {
            return $this->redirect($this->generateUrl('onboarding-membership'));
        }

        return $this->render('@VocalizrApp/Register/verification_required.html.twig');
    }

    /**
     * @Route("onboarding/membership", name="onboarding-membership")
     */
    public function onboardMembershipAction()
    {
        /** @var UserInfo $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirect($this->generateUrl('home'));
        }
        if ($user->getDateRegistered()) {
            return $this->redirect($this->generateUrl('dashboard'));
        }

        return $this->render('@VocalizrApp/Register/onboard_membership.html.twig');
    }

    /**
     * @Route("onboarding/payment", name="onboarding-payment")
     *
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function onboardPaymentAction(Request $request)
    {
        /** @var UserInfo $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirect($this->generateUrl('home'));
        }
        if ($user->getDateRegistered()) {
            return $this->redirect($this->generateUrl('dashboard'));
        }

        $em          = $this->getDoctrine()->getManager();
        $userSource  = (new MembershipSourceObject())->setSource(MembershipSourceHelper::SUB_SOURCE_ONBOARD);
        $currentPlan = $em->getRepository('VocalizrAppBundle:SubscriptionPlan')->getActiveSubscription($this->getUser()->getId());
        if ($currentPlan['static_key'] == 'PRO') {
            return $this->redirect($this->generateUrl('dashboard'));
        }
        if ($request->isMethod('POST')) {
            $proPlan = $em->getRepository('VocalizrAppBundle:SubscriptionPlan')->findOneBy(['static_key' => 'PRO']);

            $token        = $_POST['stripeToken'];
            $stripeApiKey = $this->container->getParameter('stripe_api_key');
            Stripe::setApiKey($stripeApiKey);

            $monthPlan = 'PRO MONTHLY';
            $yearPlan  = 'PRO YEARLY';

            // Which plan will they go on
            $plan   = $monthPlan;
            $amount = PayPalService::MONTHLY_PAYMENT_GROSS;
            if (isset($_POST['freq'])) {
                if ($_POST['freq'] == 'yearly') {
                    $plan   = $yearPlan;
                    $amount = PayPalService::YEARLY_PAYMENT_GROSS;
                }
            }

            if ($user->getStripeCustId()) {
                $customer = Customer::retrieve($user->getStripeCustId());

                // update the customers card details to the ones just entered - in case old card is expired
                $customer->source = $token; // obtained with Checkout
                $customer->save();

                $result = Subscription::create(['plan' => $plan]);
                $subId  = $result['id'];
            } else {
                $customer = Customer::create([
                    'source' => $token,
                    'plan'   => $plan,
                    'email'  => $user->getEmail(),
                ]);

                $subId = Subscription::all(['customer' => $customer->id, ['limit' => 1]])[0]['id'];
                $this->getUser()->setStripeCustId($customer->id);
            }

            $userSubscription = new UserSubscription();
            $userSubscription
                ->setUserInfo($this->getUser())
                ->setStripeSubscrId($subId)
                ->setPaypalSubscrId(null)
                ->setSubscriptionPlan($proPlan)
                ->setDateCommenced(new \DateTime())
                ->setSource($userSource)
            ;
            $em->persist($userSubscription);

            $this->getUser()->setSubscriptionPlan($proPlan);
            $em->flush();

            $mandrillService = $this->get('vocalizr_app.service.mandrill');

            $mandrillService->sendSubscriptionRenewedMessage($this->getUser(), $amount, $customer);

            return $this->redirect($this->generateUrl('onboarding-success'));
        }

        return $this->render('@VocalizrApp/Register/onboard_payment.html.twig', [
            'user_source' => $userSource,
            'userSub'     => false,
        ]);
    }

    /**
     * @Route("onboarding/success", name="onboarding-success")
     */
    public function onboardProSuccessAction()
    {
        /** @var UserInfo $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirect($this->generateUrl('home'));
        }
        if ($user->getDateRegistered()) {
            return $this->redirect($this->generateUrl('dashboard'));
        }

        return $this->render('@VocalizrApp/Register/onboard_success.html.twig');
    }

    /**
     * @Route("onboarding/finish", name="onboarding-finish")
     *
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function finishRegistration(Request $request)
    {
        /** @var UserInfo $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->redirect($this->generateUrl('home'));
        }
        if ($user->getDateRegistered()) {
            return $this->redirect($this->generateUrl('dashboard'));
        }

        $user->setDateRegistered(new \DateTime());
        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();

        $request->getSession()->set(HintController::SHOW_CONFIRM_EMAIL_MODAL, ['open_delay' => 60]);

        return $this->redirect($this->generateUrl('user_edit'));
    }

    /**
     * @Route("onboarding/welcome/{unique_str}", name="register_complete")
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param $unique_str
     *
     * @return RedirectResponse|Response
     * @Template()
     */
    public function completeRegistrationAction(Request $request, $unique_str)
    {
        $em = $this->getDoctrine()->getManager();

        /** @var UserInfo $user */
        $user = $this->getUser();

        // check that the unique str is parsed and is valid
        // load the used based on the unique_str
        $emailUser = $em->getRepository('VocalizrAppBundle:UserInfo')->findOneBy(['unique_str' => $unique_str]);

        $error = false;

        if (!$emailUser) {
            $error = 'Incorrect verify link. Check if you copied correct link and try again.';
        } elseif ($emailUser->getEmailConfirmed()) {
            return $this->redirect($this->generateUrl('dashboard'));
        }

        if ($user && $user !== $emailUser) {
            $error = 'You\'re logged in as ' . $user->getUsername() . '. Please logout and login as ' . $emailUser->getUsername();
        }

        if (!$error) {
            $emailUser->setEmailConfirmed(true);
            $em->persist($emailUser);
            $em->flush();
        }

        return $this->render('@VocalizrApp/Register/onboard_confirm_email.html.twig', [
            'success' => $error ? false : true,
            'error'   => $error,
        ]);
    }

    /**
     * Resend the user their confirmation email
     *
     * @Route("/user/resendConfirmationEmail", name="resend_confirmation_email")
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @Template()
     */
    public function projectStatusWidgetAction(Request $request)
    {
        $em   = $this->getDoctrine()->getManager();
        $user = $this->getUser();

        $dispatcher = $this->get('hip_mandrill.dispatcher');
        $message    = new Message();
        $message
            ->addTo($user->getEmail())
            ->addGlobalMergeVar('CONFIRMURL', $this->generateUrl('register_complete', ['unique_str' => $user->getUniqueStr()], true))
            ->setTrackOpens(true)
            ->setTrackClicks(true);
        $result = $dispatcher->send($message, 'register-confirm');

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse([
                'success' => true,
                'html'    => $this->renderView(
                    'VocalizrAppBundle:Register:resent_confirm_email.html.twig'
                ),
            ]);
        } else {
            /** @var Session $session */
            $session = $request->getSession();
            $session->getFlashBag()->add('notice', '');
            return $this->redirect($request->headers->get('referer'));
        }
    }
}
