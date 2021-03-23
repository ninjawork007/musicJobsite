<?php

namespace Vocalizr\AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ContestFixEntryCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        // How often do we run this script
        $this->_timeAgo = '1 day';

        $this
                ->setName('vocalizr:contest-fix-entry')
                ->setDescription('Fix contest entry vote counts')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->container  = $container  = $this->getContainer();
        $doctrine         = $container->get('doctrine');
        $em               = $doctrine->getEntityManager();
        $this->dispatcher = $container->get('hip_mandrill.dispatcher');

        $output->writeln(sprintf("\n## BEGIN TASK: %s", $this->getName()));

        $q = $em->getRepository('VocalizrAppBundle:ProjectBid')
                ->createQueryBuilder('pb')
                ->select('pb, ui')
                ->innerJoin('pb.user_info', 'ui')
                ->where('pb.project = 3727 AND pb.vote_count > 291')
                //->andWhere('ui.id IN (18636, 18862)')
                ->groupBy('pb.user_info')
                ->orderBy('pb.vote_count', 'DESC');

        $results = $q->getQuery()->execute();

        echo 'Total Bids: ' . count($results) . "\n\n";
        $votes = [];

        // Get list of bids that were placed in the last 5 minutes
        if ($results) {
            foreach ($results as $projectBid) {
                // Get entries for bid
                $entries = $em->getRepository('VocalizrAppBundle:EntryVote')->findBy([
                    'project_bid' => $projectBid->getId(),
                ]);

                //echo "User: ".$projectBid->getUserInfo()->getUsername()." - Current Votes: ".$projectBid->getVoteCount().", New Votes: ";
                $ips       = [];
                $voteCount = 0;
                foreach ($entries as $entry) {
                    $ipRange  = explode('.', $entry->getIpAddr());
                    $ipSearch = $ipRange[0] . '.' . $ipRange[1] . '.' . $ipRange[2];
                    $ipSearch = $ipRange[0] . '.' . $ipRange[1];

                    if (!in_array($ipSearch, $ips)) {
                        $ips[] = $ipSearch;
                        $voteCount++;
                    }
                }
                $votes[$voteCount] = 'User: ' . $projectBid->getUserInfo()->getUsername() . " - $voteCount (Old Votes: " . $projectBid->getVoteCount() . ')';
                $projectBid->setVoteCount($voteCount);
                $em->flush();
            }
        }

        ksort($votes);
        krsort($votes);
        foreach ($votes as $vote) {
            echo $vote . "\r\n";
        }
    }
}