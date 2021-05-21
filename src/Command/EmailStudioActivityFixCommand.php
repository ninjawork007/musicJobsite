<?php

namespace App\Command;

use Slot\MandrillBundle\Dispatcher;
use Slot\MandrillBundle\Message;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @property Dispatcher|object dispatcher
 * @property string _timeAgo
 */
class EmailStudioActivityFixCommand extends Command
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
        $this->_timeAgo = '6 days';

        $this
                ->setName('vocalizr:email-studio-activity-fix')
                ->setDescription('Email email studio activity to members in studio [Cronjob: Every ' . $this->_timeAgo . ']')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container        = $this->container;
        $doctrine         = $container->get('doctrine');
        $em               = $doctrine->getManager();
        $this->dispatcher = $container->get('hip_mandrill.dispatcher');

        $q = $em->getRepository('App:ProjectFeed')
                ->createQueryBuilder('pf')
                ->innerJoin('pf.project', 'p')
                ->innerJoin('pf.user_info', 'ui')
                ->innerJoin('pf.from_user_info', 'fui')
                ->where("pf.created_at >= :now AND pf.object_type != 'ProjectAsset' AND pf.object_type != 'ProjectDispute' AND pf.feed_read = 0")
                ->orderBy('pf.project', 'ASC')
                ->orderBy('pf.created_at', 'ASC');

        $params = [
            ':now' => date('Y-m-d H:i:s', strtotime('-' . $this->_timeAgo)),
        ];
        $q->setParameters($params);

        $results = $q->getQuery()->execute();

        // Get list of bids that were placed in the last 5 minutes
        if ($results) {
            $prevPf  = null;
            $content = null;
            foreach ($results as $pf) {
                $project = $pf->getProject();
                if (is_null($prevPf) || $project != $prevPf->getProject()) {
                    // If content is not null, then let's send
                    $this->sendEmail($prevPf, $content);

                    // Reset content for new project
                    $content = null;
                }

                $content .= $container->get('twig')->render('Mail:projectFeedRow.html.twig', [
                    'pf' => $pf,
                ]);
                $prevPf = $pf;
            }
            $this->sendEmail($pf, $content);
        }

        return 1;
    }

    private function sendEmail($pf, $content)
    {
        if (is_null($content)) {
            return false;
        }

        $project = $pf->getProject();

        echo $project->getTitle() . "\n";

        $message = new Message();
        $message
            ->setTrackOpens(true)
            ->setTrackClicks(true);

        $message->addTo($pf->getUserInfo()->getEmail());
        $message->addGlobalMergeVar('USER', $pf->getUserInfo()->getUsernameOrFirstName());
        $message->addGlobalMergeVar('PROJECTTITLE', $project->getTitle());
        $message->addGlobalMergeVar('PROJECTURL', $this->container->get('router')->generate('project_studio', [
            'uuid' => $project->getUuid(),
        ], true));
        $message->addGlobalMergeVar('CONTENT', $content);
        $this->dispatcher->send($message, 'project-feed');
    }
}