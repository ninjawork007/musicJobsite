<?php

namespace App\Command;

use Slot\MandrillBundle\Message;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\ProjectFeed;

class EmailStudioActivityCommand extends Command
{
    /**
     * @var OutputInterface
     */
    private $output;
    /**
     * @var \Hip\MandrillBundle\Dispatcher|object
     */
    private $dispatcher;

    protected function configure()
    {
        // How often do we run this script
        $this->_timeAgo = '5 minutes';

        $this
                ->setName('vocalizr:email-studio-activity')
                ->setDescription('Email email studio activity to members in studio [Cronjob: Every ' . $this->_timeAgo . ']')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container        = $this->getContainer();
        $doctrine         = $container->get('doctrine');
        $em               = $doctrine->getEntityManager();
        $this->dispatcher = $container->get('hip_mandrill.dispatcher');
        $this->output = $output;

        $q = $em->getRepository('App:ProjectFeed')
            ->createQueryBuilder('pf')
            ->innerJoin('pf.project', 'p')
            ->innerJoin('pf.user_info', 'ui')
            ->innerJoin('pf.from_user_info', 'fui')
            ->where("pf.created_at >= :now AND pf.object_type != 'ProjectAsset' AND pf.object_type != 'ProjectDispute'")
            ->orderBy('pf.project', 'ASC')
            ->orderBy('pf.created_at', 'ASC')
        ;

        $params = [
            ':now' => date('Y-m-d H:i:s', strtotime('-' . $this->_timeAgo)),
        ];
        $q->setParameters($params);

        /** @var ProjectFeed[] $results */
        $results = $q->getQuery()->execute();

        $output->writeln(sprintf('%s: %d new activities', date('Y-m-d H:i'), count($results)));

        // Get list of bids that were placed in the last 5 minutes
        if ($results) {
            $pf = null;
            /** @var ProjectFeed|null $prevPf */
            $prevPf  = null;
            $content = [];
            foreach ($results as $pf) {
                $project = $pf->getProject();

                // Send previous feed if it exists and is related to different project.
                if (!is_null($prevPf) && $project !== $prevPf->getProject()) {
                    // If content is not null, then let's send
                    $this->sendEmail($prevPf, join($content));

                    // Reset content for new project
                    $content = [];
                }

                // Add current project feed's view to content array.
                $content[] = $container->get('templating')->render('VocalizrAppBundle:Mail:projectFeedRow.html.twig', [
                    'pf' => $pf,
                ]);
                $prevPf = $pf;
            }

            // Send the last pf.
            $this->sendEmail($pf, join($content));
        }
        $output->writeln('done');
    }

    /**
     * @param ProjectFeed|null $pf
     * @param string $content
     * @return bool
     */
    private function sendEmail($pf, $content)
    {
        try {
            if (empty($content) || !$pf) {
                $this->output->writeln('Could not send message, empty project feed or message content');
                return false;
            }

            $project = $pf->getProject();

            $this->output->writeln(sprintf(
                "Send '%s' feed message for project '%s' to user '%s'",
                $pf->getObjectType(),
                $project->getTitle(),
                $pf->getUserInfo()->getEmail()
            ));

            $message = new Message();
            $message
                ->setTrackOpens(true)
                ->setTrackClicks(true);

            $message->addTo($pf->getUserInfo()->getEmail());
            $message->addGlobalMergeVar('USER', $pf->getUserInfo()->getUsernameOrFirstName());
            $message->addGlobalMergeVar('PROJECTTITLE', $project->getTitle());
            $message->addGlobalMergeVar('PROJECTURL', $this->getContainer()->get('router')->generate('project_studio', [
                'uuid' => $project->getUuid(),
            ], true));
            $message->addGlobalMergeVar('CONTENT', $content);
            $this->dispatcher->send($message, 'project-feed');
            return true;
        } catch (\Exception $exception) {
            $this->output->writeln('An exception occurred while sending a message: ' . $exception->getMessage());
        } catch (\Error $exception) {
            $this->output->writeln('An exception occurred while sending a message: ' . $exception->getMessage());
        }

        return false;
    }
}