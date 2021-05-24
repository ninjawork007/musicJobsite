<?php


namespace App\EventListener;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use App\Entity\UserInfo;
use App\Entity\UserTotalOnline;

class ControllerListener
{

    /** @var ContainerInterface */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        $controller = $event->getController();

        if ($event->getRequestType() === HttpKernelInterface::MASTER_REQUEST) {
            if ($controller[0] instanceof Controller) {
                try {
                    /** @var UserInfo $user */
                    $user = $controller[0]->getUser();

                    if (!$user) {
                        return;
                    }
                    $doctrine = $this->container->get('doctrine');
                    $userOnline = $user->getUserOnline();

                    if (!$userOnline) {
                        $userOnline = new UserTotalOnline();
                        $userOnline->setUser($user);
                        $userOnline->setLastActionAt(new \DateTime());
                        $user->setUserOnline($userOnline);
                        $doctrine->getManager()->persist($user);
                    }

                    $newTime = strtotime((new \DateTime())->format('Y-m-d H:i:s'));
                    $lastTime = strtotime($userOnline->getLastActionAt()->format('Y-m-d H:i:s'));

                    $diff = $newTime - $lastTime;
                    if ($diff < 300) {
                        $userOnline->setTotalTime($userOnline->getTotalTime() + $diff);
                    } elseif ($userOnline->getId() && $diff < 2) {
                        return;
                    } else {
                        $userOnline->setTotalTime($userOnline->getTotalTime() + 60);
                    }

                    $userOnline->setLastActionAt(new \DateTime());
                    $doctrine->getManager()->persist($userOnline);
                    $doctrine->getManager()->flush();

                } catch (\Error $e) {} catch (\Exception $e) {}
            }
        }
    }
}