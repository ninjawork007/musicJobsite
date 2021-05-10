<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use App\Entity\Project;

/**
 * @property \Hip\MandrillBundle\Dispatcher|object dispatcher
 */
class EmailProjectAwardReminderCommand extends Command
{
    protected function configure()
    {
        // How often do we run this script
        $this->_timeAgo = '1 day';

        $this
                ->setName('vocalizr:email-project-award-reminder')
                ->setDescription('Email project owner about awarding gig [Cronjob: Every ' . $this->_timeAgo . ']')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container        = $this->getContainer();
        $doctrine         = $container->get('doctrine');
        $em               = $doctrine->getManager();
        $this->dispatcher = $container->get('hip_mandrill.dispatcher');

        $output->writeln(sprintf("\n## BEGIN TASK: %s", $this->getName()));

        $q = $em->getRepository('App:Project')
                ->createQueryBuilder('p')
                ->select('p, ui')
                ->innerJoin('p.user_info', 'ui')
                ->where("DATE_FORMAT(p.bids_due, '%Y-%m-%d') = :date AND p.num_bids > 0 AND p.is_active = 1 AND p.awarded_at IS NULL");

        $params = [
            ':date' => date('Y-m-d', strtotime('-3 days')),
        ];
        $q->setParameters($params);

        $results = $q->getQuery()->execute();

        echo 'Total projects ' . count($results) . " projects\n\n";

        // Get list of bids that were placed in the last 5 minutes
        if ($results) {
            foreach ($results as $project) {
                $user = $project->getUserInfo();

                if ($project->getProjectType() == Project::PROJECT_TYPE_CONTEST) {
                    $subject  = 'Please award your contest "' . $project->getTitle() . '"';
                    $template = 'contestAwardReminder';
                } else {
                    $subject  = 'Please award your Gig "' . $project->getTitle() . '"';
                    $template = 'projectAwardReminder';
                }

                $message = new \Hip\MandrillBundle\Message();
                $message->setSubject($subject);
                $message->setFromEmail('help@vocalizr.com');
                $message->setFromName('Luke Chable');
                $message
                    ->setTrackOpens(true)
                    ->setTrackClicks(true);

                $message->addTo($user->getEmail());
                $body = $container->get('twig')->render('Mail:' . $template . 'connection.html.twig', [
                    'userInfo' => $user,
                    'project'  => $project,
                ]);
                $message->addGlobalMergeVar('BODY', $body);
                $this->dispatcher->send($message, 'default-luke');
            }
        }
    }
}