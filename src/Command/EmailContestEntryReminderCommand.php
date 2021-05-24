<?php

namespace App\Command;

use Slot\MandrillBundle\Message;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use App\Entity\Project;

use App\Entity\ProjectAudio;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EmailContestEntryReminderCommand extends Command
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
        $this->_timeAgo = '1 day';

        $this
                ->setName('vocalizr:email-contest-entry-reminder')
                ->setDescription('Email members who downloaded audio on a contest and remind them to submit entry [Cronjob: Every ' . $this->_timeAgo . ']')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $doctrine         = $this->container->get('doctrine');
        $em               = $doctrine->getManager();
        $this->dispatcher = $this->container->get('hip_mandrill.dispatcher');

        $output->writeln(sprintf("\n## BEGIN TASK: %s", $this->getName()));

        $q = $em->getRepository('App:Project')
                ->createQueryBuilder('p')
                ->select('p, ui')
                ->innerJoin('p.user_info', 'ui')
                ->where("DATE_FORMAT(p.bids_due, '%Y-%m-%d') = :date AND p.is_active = 1 AND p.awarded_at IS NULL and p.project_type = :projectType");

        $params = [
            ':date'        => date('Y-m-d', strtotime('+5 days')),
            ':projectType' => Project::PROJECT_TYPE_CONTEST,
        ];
        $q->setParameters($params);

        $results = $q->getQuery()->execute();

        echo 'Total contests: ' . count($results) . "\n\n";

        // Get list of bids that were placed in the last 5 minutes
        if ($results) {
            foreach ($results as $project) {
                // Get project audio and downloads
                $audio = $em->getRepository('App:ProjectAudio')->findOneBy([
                    'project' => $project->getId(),
                    'flag'    => ProjectAudio::FLAG_FEATURED,
                ]);

                if (!$audio) {
                    continue;
                }

                // Get downloads
                $q = $em->getRepository('App:ProjectAudioDownload')
                        ->createQueryBuilder('pad')
                        ->select('pad, ui, pa, p')
                        ->innerJoin('pad.user_info', 'ui')
                        ->innerJoin('pad.project_audio', 'pa')
                        ->innerJoin('pa.project', 'p')
                        ->where('pad.project_audio = :projectAudio');

                $q->setParameters(['projectAudio' => $audio]);

                $downloads = $q->getQuery()->execute();

                if (!$downloads) {
                    continue;
                }

                echo 'Contest: ' . $project->getTitle() . ' - ' . $project->getUuid() . "\n";
                echo 'Downloads: ' . count($downloads) . "\n";
                $i = 0;
                foreach ($downloads as $download) {
                    $userInfo = $download->getUserInfo();

                    if ($userInfo->getId() == $project->getUserInfo()->getId()) {
                        continue;
                    }

                    // If downloaded only today or yesterday - don't send
                    if ($download->getCreatedAt()->format('Y-m-d') == date('Y-m-d') ||
                            $download->getCreatedAt()->format('Y-m-d') == date('Y-m-d', strtotime('-1 day'))) {
                        continue;
                    }

                    // Check if user has submitted an entry for project
                    $entry = $em->getRepository('App:ProjectBid')->findOneBy([
                        'project'   => $project,
                        'user_info' => $userInfo,
                    ]);
                    // If they have submitted an entry, no need to email them
                    if ($entry) {
                        continue;
                    }

                    $message = new Message();
                    $message->setSubject("Don't forget to submit your entry!");
                    $message->setFromEmail('noreply@vocalizr.com');
                    $message->setFromName('Vocalizr');
                    $message
                        ->setTrackOpens(true)
                        ->setTrackClicks(true);

                    $message->addTo($userInfo->getEmail());
                    $body = $this->container->get('twig')->render('Mail:submitEntryReminder.html.twig', [
                        'userInfo' => $userInfo,
                        'project'  => $project,
                    ]);
                    $message->addGlobalMergeVar('BODY', $body);
                    $this->dispatcher->send($message, 'default');

                    $i++;
                }
                echo 'Reminders sent: ' . $i . "\n\n";
            }
        }

        return 1;
    }
}