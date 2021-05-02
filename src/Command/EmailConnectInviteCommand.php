<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EmailConnectInviteCommand extends Command
{
    protected function configure()
    {
        // How often do we run this script
        $this->_timeAgo = '10 minutes';

        $this
                ->setName('vocalizr:email-connect-invites')
                ->setDescription('Email connection invites [Cronjob: Every ' . $this->_timeAgo . ']')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->container  = $container  = $this->getContainer();
        $doctrine         = $container->get('doctrine');
        $em               = $doctrine->getEntityManager();
        $this->dispatcher = $container->get('hip_mandrill.dispatcher');

        $q = $em->getRepository('App:UserConnectInvite')
                ->createQueryBuilder('uc')
                ->select('uc, tui, fui')
                ->innerJoin('uc.to', 'tui')
                ->innerJoin('uc.from', 'fui')
                ->leftJoin('tui.user_pref', 'tup')
                ->where('uc.created_at >= :now AND uc.connected_at IS NULL AND uc.status IS NULL')
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
            $subject = $result->getTo()->getUsernameOrFirstName() . ', I would like to connect on Vocalizr';

            $message = new \Hip\MandrillBundle\Message();
            $message->setSubject($subject);
            $message->setFromEmail('noreply@vocalizr.com');
            $message->setFromName('Vocalizr');
            $message
                ->setTrackOpens(true)
                ->setTrackClicks(true);

            $message->addTo($result->getTo()->getEmail());
            $body = $container->get('templating')->render('VocalizrAppBundle:Mail:connectInvite.html.twig', [
                'result' => $result,
            ]);
            $message->addGlobalMergeVar('BODY', $body);
            $this->dispatcher->send($message, 'default');
        }
    }
}