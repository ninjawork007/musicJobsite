<?php

namespace App\Command;

use Slot\MandrillBundle\Message;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @property string _timeAgo
 */
class EmailBidsEndedCommand extends Command
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
        $this->_timeAgo = '24 hours';

        $this
                ->setName('vocalizr:email-bids-ended')
                ->setDescription('Email gig owner that bids due has ended [Cronjob: Every ' . $this->_timeAgo . ']')
                ->addOption(
                    'skip-email',
                    null,
                    InputOption::VALUE_NONE,
                    'If set, the task will skip sending notificaiton via email'
                );
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
            ->select('p, ui')
            ->innerJoin('p.user_info', 'ui')
            ->where('p.bids_due = :now and p.num_bids > 0')
            ->andWhere('p.is_active = 1')
            ->andWhere('p.awarded_at IS NULL')
            ->andWhere('p.hire_user IS NULL')
            ->andWhere('ui.is_active = 1')
        ;

        $params = [
            ':now' => date('Y-m-d'),
        ];
        $q->setParameters($params);

        $results = $q->getQuery()->execute();

        $output->writeln('SCRIPT START (' . count($results) . ') results');
        if ($results) {
            foreach ($results as $project) {
                $userInfo = $project->getUserInfo();

                $message = new Message();
                $message
                    ->setTrackOpens(true)
                    ->setTrackClicks(true);
                $message->addTo($userInfo->getEmail());

                $templateName = 'projectBidsEnded';
                $subject      = 'Award your Gig!';
                if ($project->getProjectType() == 'contest') {
                    $templateName = 'contestEntriesEnded';
                    $subject      = 'Award your Contest!';
                }
                $body = $container->get('twig')->render('Mail:' . $templateName . 'connection.html.twig', [
                    'userInfo' => $userInfo,
                    'project'  => $project,
                ]);
                $message->setSubject($subject);
                $message->addGlobalMergeVar('BODY', $body);
                $dispatcher->send($message, 'default');
            }
        }
        $output->writeln('SCRIPT END');
    }
}