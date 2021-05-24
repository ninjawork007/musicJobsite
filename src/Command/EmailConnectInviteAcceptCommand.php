<?php

namespace App\Command;

use Slot\MandrillBundle\Message;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EmailConnectInviteAcceptCommand extends Command
{
    private $container;

    /**
     * DeferredSubscriptionCancelCommand constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        $this->container = $container;
    }

    protected function configure()
    {
        // How often do we run this script
        $this->_timeAgo = '10 minutes';

        $this
                ->setName('vocalizr:email-connect-invite-accept')
                ->setDescription('Email connection invites that were accepted [Cronjob: Every ' . $this->_timeAgo . ']')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container        = $this->container;
        $doctrine         = $container->get('doctrine');
        $em               = $doctrine->getManager();
        $this->dispatcher = $container->get('hip_mandrill.dispatcher');

        $q = $em->getRepository('App:UserConnectInvite')
                ->createQueryBuilder('uc')
                ->select('uc, tui, fui')
                ->innerJoin('uc.to', 'tui')
                ->innerJoin('uc.from', 'fui')
                ->leftJoin('fui.user_pref', 'tup')
                ->where('uc.connected_at >= :now AND uc.status = 1')
                ->andWhere('(tup.id IS NULL OR tup.email_connections = 1)')
                ->orderBy('uc.created_at', 'ASC');

        $params = [
            ':now' => date('Y-m-d H:i:s', strtotime('-' . $this->_timeAgo)),
        ];
        $q->setParameters($params);

        $query = $q->getQuery();

        // Loop through results, and get
        $results = $query->execute();

        foreach ($results as $result) {
            $subject = 'You are now connected with ' . $result->getTo()->getDisplayName();

            $message = new Message();
            $message->setSubject($subject);
            $message->setFromEmail('noreply@vocalizr.com');
            $message->setFromName('Vocalizr');
            $message
                ->setTrackOpens(true)
                ->setTrackClicks(true);

            $message->addTo($result->getFrom()->getEmail());
            $body = $container->get('twig')->render('Mail:connectInviteAccept.html.twig', [
                'result' => $result,
            ]);
            $message->addGlobalMergeVar('BODY', $body);
            $this->dispatcher->send($message, 'default');
        }

        return 1;
    }
}