<?php

namespace App\Command;

use Slot\MandrillBundle\Dispatcher;
use Slot\MandrillBundle\Message;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @property string _timeAgo
 * @property Dispatcher|object dispatcher
 */
class EmailProjectBiddingCheckCommand extends Command
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        $this->container = $container;
    }

    protected function configure()
    {
        // How often do we run this script
        $this->_timeAgo = '1 day';

        $this
                ->setName('vocalizr:email-project-bidding-check')
                ->setDescription('Email to give suggestions to get more bids and ask if they need assistance [Cronjob: Every ' . $this->_timeAgo . ']')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container        = $this->container;
        $doctrine         = $container->get('doctrine');
        $em               = $doctrine->getManager();
        $this->dispatcher = $container->get('hip_mandrill.dispatcher');

        $q = $em->getRepository('App:Project')
                ->createQueryBuilder('p')
                ->select('p, pb, ui')
                ->innerJoin('p.user_info', 'ui')
                ->innerJoin('p.project_bids', 'pb')
                ->where("DATE_FORMAT(p.bids_due, '%Y-%m-%d') = :date AND p.project_type = :projectType AND p.is_active = 1 AND p.project_bid IS NULL AND p.hire_user IS NULL");

        $params = [
            ':date'        => date('Y-m-d', strtotime('+14 days')),
            ':projectType' => 'paid',
        ];
        $q->setParameters($params);

        $results = $q->getQuery()->execute();

        echo 'Total projects ' . count($results) . " projects\n\n";

        // Get list of bids that were placed in the last 5 minutes
        if ($results) {
            foreach ($results as $project) {
                $user = $project->getUserInfo();

                $message = new Message();
                $message->setSubject('How\'s your gig "' . $project->getTitle() . '" going?');
                $message->setFromEmail('help@vocalizr.com');
                $message->setFromName('Luke Chable');
                $message
                    ->setTrackOpens(true)
                    ->setTrackClicks(true);

                $message->addTo($user->getEmail());
                $body = $container->get('twig')->render('Mail:projectBiddingCheck.html.twig', [
                    'userInfo'  => $user,
                    'project'   => $project,
                    'totalBids' => count($project->getProjectBids()),
                ]);
                $message->addGlobalMergeVar('BODY', $body);
                $this->dispatcher->send($message, 'default-luke');
            }
        }

        return 1;
    }
}