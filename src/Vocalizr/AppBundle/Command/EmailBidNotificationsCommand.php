<?php

namespace Vocalizr\AppBundle\Command;

use Hip\MandrillBundle\Message;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Vocalizr\AppBundle\Entity\Project;

/**
 * @property string _timeAgo
 */
class EmailBidNotificationsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        // How often do we run this script
        $this->_timeAgo = '5 minutes';

        $this
            ->setName('vocalizr:email-bid-notifications')
            ->setDescription('Email bid notifcations to project owners [Cronjob: Every ' . $this->_timeAgo . ']')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container  = $this->getContainer();
        $doctrine   = $container->get('doctrine');
        $em         = $doctrine->getEntityManager();
        $dispatcher = $container->get('hip_mandrill.dispatcher');

        $q = $em
            ->getRepository('VocalizrAppBundle:Project')
            ->createQueryBuilder('p')

            ->select('p, pb, ui, up, pbui')
            ->innerJoin('p.project_bids', 'pb')
            ->innerJoin('pb.user_info', 'pbui')
            ->innerJoin('p.user_info', 'ui')
            ->leftJoin('ui.user_pref', 'up')    // join user preferences for that user

            ->where('(up.id IS NULL OR up.email_project_bids = 1) ')
            ->andWhere('p.is_active = 1')
            ->andWhere('pb.created_at >= :now AND ui.is_active = 1')

            ->orderBy('pb.created_at', 'ASC')
        ;

        $params = [
            ':now' => date('Y-m-d H:i:s', strtotime('-' . $this->_timeAgo)),
        ];

        $q->setParameters($params);

        /** @var Project[] $results */
        $results = $q->getQuery()->execute();

        // Get list of bids that were placed in the last 5 minutes
        if ($results) {
            foreach ($results as $project) {
                $userInfo    = $project->getUserInfo();
                $projectBids = $project->getProjectBids();

                $message = new Message();
                $message
                    ->setTrackOpens(true)
                    ->setTrackClicks(true);
                $message->addTo($userInfo->getEmail());

                $templateName = 'bidsNotification';
                $subject      = 'Bid' . (count($projectBids) > 1 ? 's' : '') . ' submitted on ' . $project->getTitle();
                if ($project->getProjectType() == 'contest') {
                    $templateName = 'entriesNotification';
                    $subject      = (count($projectBids) > 1 ? 'Entries have' : 'An entry has') . ' been submitted on ' . $project->getTitle();
                }
                $body = $container->get('templating')->render('VocalizrAppBundle:Mail:' . $templateName . 'connection.html.twig', [
                    'userInfo'    => $userInfo,
                    'project'     => $project,
                    'projectBids' => $projectBids,
                ]);
                $message->setSubject($subject);
                $message->addGlobalMergeVar('BODY', $body);
                $dispatcher->send($message, 'default');
            }
        }
    }
}