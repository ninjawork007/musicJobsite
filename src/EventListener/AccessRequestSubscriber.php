<?php

namespace App\EventListener;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use App\Entity\UserInfo;
use Symfony\Component\Security\Core\Security;
use Twig\Environment;

/**
 * Class AccessRequestSubscriber
 *
 * @package App\EventListener
 */
class AccessRequestSubscriber implements EventSubscriberInterface
{
    private static $allowedEmailNotConfirmedRoutes = [
        'onboarding-verify',
        'register_complete',
        'resend_confirmation_email',
    ];

    private static $allowedRegistrationNotFinishedRoutes = [
        'stripe_session_subscription',
        'paypal_pro_success',
        'register_complete',
    ];

    /**
     * @var ContainerInterface
     */
    private $container;

    private $router;

    private $twig;

    private $security;

    /**
     * AccessRequestSubscriber constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container, Environment $twig, Security $security)
    {
        $this->container = $container;
        $this->router    = $this->container->get('router');
        $this->twig      = $twig;
        $this->security  = $security;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::REQUEST => [
                ['onRequest', 0],
            ],
        ];
    }

    /**
     * @param RequestEvent $event
     */
    public function onRequest(RequestEvent $event)
    {
        $user = $this->getUser();

        // Return if user is not logged in
        // Or already completed registration.
        if (!$user || $event->getRequestType() !== HttpKernelInterface::MASTER_REQUEST || ($user->isRegistrationFinished() && $user->getEmailConfirmed())) {
            return;
        }

        $request = $event->getRequest();

        /** @var Session $session */
        $session = $request->getSession();
        $route   = $request->get('_route');

        if (!$user->getEmailConfirmed()) {
            if (in_array($route, self::$allowedEmailNotConfirmedRoutes)) {
                return;
            }
            if ($request->isXmlHttpRequest()) {
                $response = $this->twig->renderResponse('include/panel/verify_email_panel.html.twig');
            } else {
                $session->getFlashBag()->add('error', 'Please verify your email first');
                $response = new RedirectResponse($this->router->generate('onboarding-verify'));
            }
            $event->setResponse($response);
            $event->stopPropagation();
            return;
        }

        if (!$user->getDateRegistered()) {
            // Redirect user from another pages if he was not finished first registration steps.
            if (strstr($route, 'onboard') === false && !in_array($route, self::$allowedRegistrationNotFinishedRoutes)) {
                $event->setResponse(new RedirectResponse($this->router->generate('onboarding-membership')));
                $event->stopPropagation();
            }
            return;
        }
    }

    /**
     * @return UserInfo|null
     */
    public function getUser()
    {
        if (!$this->security) {
            throw new \LogicException('The SecurityBundle is not registered in your application.');
        }

        if (null === $token = $this->security->getToken()) {
            return null;
        }

        if (!is_object($user = $token->getUser())) {
            return null;
        }

        return $user;
    }
}