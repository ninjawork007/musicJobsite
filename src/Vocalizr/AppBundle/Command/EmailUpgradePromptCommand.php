<?php


namespace Vocalizr\AppBundle\Command;


use Doctrine\ORM\EntityManager;
use Hip\MandrillBundle\Message;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Vocalizr\AppBundle\Entity\SubscriptionPlan;
use Vocalizr\AppBundle\Entity\UserInfo;

class EmailUpgradePromptCommand extends ContainerAwareCommand
{

    protected $container;
    protected $em;
    protected $dispatcher;
    protected $message;

    protected function configure()
    {
        // How often do we run this script
        $this->_timeAgo = '24 hours';

        $this->setName('vocalizr:upgrade_prompt')
        ->setDescription('Send Email upgrade prompt');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->container  = $container  = $this->getContainer();
        $this->em         = $container->get('doctrine')->getEntityManager();
        $this->dispatcher = $container->get('hip_mandrill.dispatcher');

        $this->newMessage();

        /** @var UserInfo[] $usersConnect */
        $usersConnect = $this->em->getRepository('VocalizrAppBundle:UserInfo')
            ->findByConnectInvite();

        /** @var UserInfo[] $usersProjectInvites */
        $usersProjectInvites = $this->em->getRepository('VocalizrAppBundle:UserInfo')
            ->findByProjectInvite();

        /** @var UserInfo[] $usersHireNowInvites */
        $usersHireNowInvites = $this->em->getRepository('VocalizrAppBundle:UserInfo')
            ->findByHireNowInvite();

        foreach ($usersConnect as $userInfo) {
            $this->message->addTo($userInfo->getEmail());
            $this->message->setSubject("You have a connection request");
        }
        $this->sendEmailConnect();

        $this->newMessage();
        foreach ($usersProjectInvites as $userInfo) {
            $this->message->addTo($userInfo->getEmail());
            $this->message->setSubject("You have been invited to bid");
        }
        $this->sendEmailProject();

        $this->newMessage();
        foreach ($usersHireNowInvites as $userInfo) {
            $this->message->addTo($userInfo->getEmail());
            $this->message->setSubject("Someone wants to hire you!");
        }
        $this->sendEmailHireNow();
    }

    private function newMessage()
    {
        $this->message = new \Hip\MandrillBundle\Message();
        $this->message->setPreserveRecipients(false);
        $this->message
            ->setTrackOpens(true)
            ->setTrackClicks(true);
    }

    private function sendEmailConnect()
    {
        echo "Sending emails connect\n";
        $this->dispatcher->send($this->message, 'upgrade-prompt-connections');
    }

    private function sendEmailProject()
    {
        echo "Sending emails project\n";
        $this->dispatcher->send($this->message, 'upgrade-prompt-invite-to-bid');
    }

    private function sendEmailHireNow()
    {
        echo "Sending emails hire now\n";
        $this->dispatcher->send($this->message, 'upgrade-prompt-hire-now');
    }
}
