<?php

namespace App\Handler;

use Doctrine\Persistence\ManagerRegistry as Doctrine;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;
use App\Controller\HintController;
use App\Entity\UserActionAudit;
use App\Entity\UserInfo;
use App\Model\UserActionAuditModel;

/**
 * Class AuthenticationHandler
 *
 * @package App\Handler
 */
class AuthenticationHandler implements AuthenticationSuccessHandlerInterface, AuthenticationFailureHandlerInterface, LogoutSuccessHandlerInterface
{
    /** @var \Doctrine\ORM\EntityManager */
    private $em;

    private $router;

    private $securityContext;

    private $container;
    private $tokenInterface;

    /**
     * @var UserActionAuditModel
     */
    private $userActionAudit;

    /**
     * Constructor
     *
     * @param Doctrine $doctrine
     * @param AuthorizationCheckerInterface $securityContext
     * @param ContainerInterface $container
     * @param RouterInterface $router
     * @param UserActionAuditModel $userActionAuditSecurity
     * @param TokenStorageInterface $tokenInterface
     */
    public function __construct(Doctrine $doctrine,
                                AuthorizationCheckerInterface $securityContext,
                                ContainerInterface $container,
                                RouterInterface $router,
                                UserActionAuditModel $userActionAuditSecurity,
                                TokenStorageInterface $tokenInterface
    )
    {
        $this->em              = $doctrine->getManager();
        $this->securityContext = $securityContext;
        $this->container       = $container;
        $this->router          = $router;
        $this->userActionAudit = $userActionAuditSecurity;
        $this->tokenInterface  = $tokenInterface;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        /** @var UserInfo $user */
        $user = $token->getUser();
        $session = $request->getSession();

        // If user isn't active
        if (!$user->getIsActive()) {
            $session->invalidate();

            $session->getFlashBag()->add('error', 'Your account is not active');

            // Log them out
            $this->tokenInterface->setToken(null);

            // Redirect to resend activation page
            return new RedirectResponse($this->router->generate('login'));
        }

        $user->setLoginIp(!empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR']);
        $user->setLastLogin(new \DateTime());
        $this->em->persist($user);
        $this->em->flush();
        $this->userActionAudit->logAction(UserActionAudit::ACTION_LOGIN, $user);
        if (!$user->getEmailConfirmed()) {
            $request->getSession()->set(HintController::SHOW_CONFIRM_EMAIL_MODAL, true);
        }

        if ($targetPath = $request->getSession()->get('_security.user.target_path')) {
            return new RedirectResponse($targetPath);
        }
        return new RedirectResponse($this->container->get('router')->generate('dashboard'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $referer = $request->headers->get('referer');
        $request->getSession()->setFlash('error', $exception->getMessage());

        return new RedirectResponse($referer);
    }

    public function onLogoutSuccess(Request $request)
    {
        return new RedirectResponse($this->router->generate('home'));
    }
}
