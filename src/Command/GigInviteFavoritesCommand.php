<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Setup the platform
 */
class GigInviteFavoritesCommand extends Command
{
    const COMMAND_NAME = 'vocalizr:gig:invite:favorites';

    const COMMAND_DESC = 'Send email to favorites in case of new Gig';

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        parent::initialize($input, $output);
    }

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
                ->setName(self::COMMAND_NAME)
                ->setDescription(self::COMMAND_DESC)
                ->addOption('projectId', 'p', InputOption::VALUE_OPTIONAL, 'Set the project id', null)
                ->addOption('userId', 'u', InputOption::VALUE_OPTIONAL, 'Set the Gig\'s creator user id', null)
                ->setHelp(
                    <<<EOT
The <info>%command.name%</info> command send email to invite all favorites to a Gig, given its project id and the user id of Gig's creator:

    <info>php %command.full_name% --projectId=[n] --userId=[m]</info>
EOT
                );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $startedAt     = microtime(true);
        $initialMemory = memory_get_usage();

        $projectId = $input->getOption('projectId');
        $userId    = $input->getOption('userId');
        if (is_null($projectId) || (empty($projectId))) {
            throw new \RuntimeException('<error>--projectId=n is required</error>');
        }

        if (is_null($userId) || (empty($userId))) {
            throw new \RuntimeException('<error>--userId=m is required</error>');
        }

        $container = $this->getContainer();
        $em        = $container->get('doctrine.orm.default_entity_manager');

        $project = $em->getRepository('App:Project')->find($projectId);
        if (!$project) {
            throw new \RuntimeException('<error>Project not found: please check the passed project id</error>');
        }

        $user = $em->getRepository('App:UserInfo')->find($userId);
        if (!$user) {
            throw new \RuntimeException('<error>User not found: please check the passed user id</error>');
        }

        $output->writeln(
            sprintf('<info>Sending the invitation emails to favorites: project UUID "%s", user "%s" </info>', $project->getUuid(), $user->getFullName())
        );

        // Get favorites depending on what they are lookign for
        $userInfoFavs = $em->getRepository('App:UserInfo')->getUserFavoritesForInviting($userId, $project);

        if ($userInfoFavs) {
            $dispatcher = $container->get('hip_mandrill.dispatcher');
            $message    = new \Hip\MandrillBundle\Message();

            $favorites = $userInfoFavs[0]->getFavorites();
            foreach ($favorites as $favUserInfo) {
                // check if user has been invited to this project before
                // stops double click issues
                $q = $em->getRepository('App:ProjectInvite')
                        ->createQueryBuilder('pi')
                        ->select('count(pi)')
                        ->where('pi.project = :project')
                        ->andWhere('pi.user_info = :user_info')
                        ->setParameter('project', $project)
                        ->setParameter('user_info', $favUserInfo)
                ;
                $numResults = $q->getQuery()->getSingleScalarResult();
                if ($numResults > 0) {
                    continue;
                }

                $pi = new \App\Entity\ProjectInvite();
                $pi
                        ->setProject($project)
                        ->setUserInfo($favUserInfo)
                ;
                $em->persist($pi);

                $userPref = $favUserInfo->getUserPref();
                if (is_null($userPref) || ($userPref && $userPref->getEmailProjectInvites())) {
                    if (!isset($message)) {
                        $message = new \Hip\MandrillBundle\Message();
                    }
                    $message->addTo($favUserInfo->getEmail());
                    $body = $container->get('templating')->render('VocalizrAppBundle:Mail:projectInvite.html.twig', [
                        'userInfo' => $favUserInfo,
                        'project'  => $project,
                        'user'     => $user,
                    ]);
                    $message->addMergeVar($favUserInfo->getEmail(), 'BODY', $body);
                }
            }

            // If message is set, then send emails
            if (isset($message)) {
                $message
                        ->setSubject('Gig Invitation to "' . $project->getTitle() . '"')
                        ->setFromEmail('noreply@vocalizr.com')
                        ->setFromName('Vocalizr')
                        ->setPreserveRecipients(false)
                        ->setTrackOpens(true)
                        ->setTrackClicks(true)
                ;

                $dispatcher->send($message, 'default', [], true);
            }
        }

        $em->flush();

        $output->writeln('<comment>... Done!</comment>');

        $endedAt     = microtime(true);
        $finalMemory = memory_get_usage();
        $output->writeln(sprintf('<comment>Execution time: %d seconds</comment>', ($endedAt - $startedAt)));
        $output->writeln(sprintf('<comment>Execution memory: %d KB</comment>', ($finalMemory - $initialMemory) / 1024));

        return true;
    }
}
