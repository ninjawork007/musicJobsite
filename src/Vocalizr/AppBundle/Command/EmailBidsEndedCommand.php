<?php

namespace Vocalizr\AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @property string _timeAgo
 */
class EmailBidsEndedCommand extends ContainerAwareCommand
{
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
        $container  = $this->getContainer();
        $doctrine   = $container->get('doctrine');
        $em         = $doctrine->getEntityManager();
        $dispatcher = $container->get('hip_mandrill.dispatcher');

        $q = $em
            ->getRepository('VocalizrAppBundle:Project')
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

                $message = new \Hip\MandrillBundle\Message();
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
                $body = $container->get('templating')->render('VocalizrAppBundle:Mail:' . $templateName . 'connection.html.twig', [
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