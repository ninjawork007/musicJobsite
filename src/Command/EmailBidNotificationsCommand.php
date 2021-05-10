<?php

namespace App\Command;

use Slot\MandrillBundle\Message;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\Project;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @property string _timeAgo
 */
class EmailBidNotificationsCommand extends Command
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
        $this->_timeAgo = '5 minutes';

        $this
            ->setName('vocalizr:email-bid-notifications')
            ->setDescription('Email bid notifcations to project owners [Cronjob: Every ' . $this->_timeAgo . ']')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container  = $this->container;
        $doctrine   = $container->get('doctrine');
        $em         = $doctrine->getManager();
        $dispatcher = $container->get('hip_mandrill.dispatcher');

        $q = $em
            ->getRepository('App:Project')
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
                $body = $container->get('twig')->render('Mail:' . $templateName . 'connection.html.twig', [
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