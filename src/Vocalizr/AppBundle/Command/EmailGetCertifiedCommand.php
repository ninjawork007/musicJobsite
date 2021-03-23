<?php


namespace Vocalizr\AppBundle\Command;


use Doctrine\ORM\EntityManager;
use Hip\MandrillBundle\Message;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Vocalizr\AppBundle\Entity\UserInfo;

class EmailGetCertifiedCommand extends ContainerAwareCommand
{

    protected $container;
    protected $em;
    protected $dispatcher;
    protected $message;

    protected function configure()
    {
        // How often do we run this script
        $this->_timeAgo = '10 minutes';

        $this->setName('vocalizr:get_certified')
        ->setDescription('Send Email Get Certified');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->container  = $container  = $this->getContainer();
        $this->em         = $container->get('doctrine')->getEntityManager();
        $this->dispatcher = $container->get('hip_mandrill.dispatcher');
        $checkDateStart   = new \DateTime('-10 minutes');

        /** @var Message message */
        $this->message = new \Hip\MandrillBundle\Message();
        $this->message->setPreserveRecipients(false);
        $this->message
            ->setTrackOpens(true)
            ->setTrackClicks(true);
        /** @var UserInfo[] $usersInfo */
        $usersInfo = $this->em->getRepository('VocalizrAppBundle:UserInfo')
            ->createQueryBuilder('ui')
            ->andWhere('ui.is_active = 1')
            ->andWhere('ui.date_activated <= :dateStart')
            ->andWhere("ui.getCertifiedMailSend = false")
            ->setParameter(':dateStart', $checkDateStart)
            ->getQuery()
            ->getResult();

        foreach ($usersInfo as $userInfo) {
            $userInfo->setGetCertifiedMailSend(true);
            $this->em->persist($userInfo);
            $this->message->addTo($userInfo->getEmail());
            $this->message->setSubject("Want to be a Vocalizr Certified Professional?");
        }
        $this->em->flush();
        $this->sendEmail();
    }

    private function sendEmail()
    {
        echo "Sending emails\n";
        $this->dispatcher->send($this->message, 'get-certified');
    }
}
