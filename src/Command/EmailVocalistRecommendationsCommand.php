<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EmailVocalistRecommendationsCommand extends Command
{
    const BUNCH_SIZE = 10;  // bunch emails to reduce calls to mandrill

    private $projects = [];

    protected function configure()
    {
        // How often do we run this script
        $this->_timeAgo = '3 days';

        $this->setName('vocalizr:email-vocalist-recommendations')
             ->setDescription('Email recommendations to vocalists referencing gigs that have been created that they may be interested in looking at.  [Cronjob: Every ' . $this->_timeAgo . ']');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->container  = $container  = $this->getContainer();
        $this->em         = $container->get('doctrine')->getEntityManager();
        $this->dispatcher = $container->get('hip_mandrill.dispatcher');

        $this->message = new \Hip\MandrillBundle\Message();
        $this->message->setPreserveRecipients(false);
        $this->message
            ->setTrackOpens(true)
            ->setTrackClicks(true);

        echo "SCRIPT START - Email Vocalist Recommendations\n";
        $today     = new \DateTime();
        $yesterday = clone $today;
        $yesterday->sub(new \DateInterval('P3D')); // change this first day to P100D

        // get projects that are
        // - published publicly
        // - created yesterday
        $q = $this->em->getRepository('App:Project')
                ->createQueryBuilder('p')
                ->select('p, u')
                ->innerJoin('p.user_info', 'u')
                ->where('p.publish_type = :publishType AND p.is_active = 1 and p.published_at IS NOT NULL')
                ->andWhere('p.featured = 1')
                ->andWhere('p.looking_for = :vocalist')
                ->andWhere('p.published_at >= :yesterday')
                //->andWhere('p.published_at < :today')
                ->andWhere('p.project_bid is null')
                ->setParameter('publishType', \App\Entity\Project::PUBLISH_PUBLIC)
                ->setParameter('vocalist', 'vocalist')
                ->setParameter('yesterday', $yesterday);
        //->setParameter('today', $today);

        $this->featuredProjects = $q->getQuery()->execute();

        $q = $this->em->getRepository('App:Project')
                ->createQueryBuilder('p')
                ->select('p, u')
                ->innerJoin('p.user_info', 'u')
                ->where('p.publish_type = :publishType AND p.is_active = 1 and p.published_at IS NOT NULL')
                ->andWhere('p.featured = 0')
                ->andWhere('p.looking_for = :vocalist')
                ->andWhere('p.published_at >= :yesterday')
                //->andWhere('p.published_at < :today')
                ->andWhere('p.project_bid is null')
                ->setParameter('publishType', \App\Entity\Project::PUBLISH_PUBLIC)
                ->setParameter('vocalist', 'vocalist')
                ->setParameter('yesterday', $yesterday);
        //->setParameter('today', $today);

        $this->projects = $q->getQuery()->execute();

        if (count($this->projects) == 0 && count($this->featuredProjects) == 0) {
            echo "NO NEW PROJECTS\n";
            echo "SCRIPT END - Email Vocalist Recommendations\n\n";
            return;
        }

        echo "Got projects\n\n";
        echo 'Getting users...';

        // get all the vocalists - this will need to be altered due to performance
        $q = $this->em->getRepository('App:UserInfo')
                ->createQueryBuilder('u')
                ->select('u, up')
                ->leftJoin('u.user_pref', 'up') // join user preferences for that user
                ->where('u.is_active = true')
                ->andWhere('u.is_vocalist = true')
                ->andWhere('(up.id IS NULL OR up.email_new_projects = 1 OR up.email_new_collabs = 1)');
        $users = $q->getQuery()->execute();

        echo "Done\n";

        echo 'Processing users (' . count($users) . ')....';

        $i = 0;
        foreach ($users as $user) {
            $i++;
            // determine which of the created projects this user has not bid no yet
            $this->processUser($user);

            if ($i % 20 == 0) {
                $this->sendEmail();

                $this->dispatcher = $container->get('hip_mandrill.dispatcher');
                $this->message    = new \Hip\MandrillBundle\Message();
                $this->message->setPreserveRecipients(false);
                $this->message
                    ->setTrackOpens(true)
                    ->setTrackClicks(true);
            }
        }

        echo "DONE\n";
        echo "SCRIPT END - Email Vocalist Recommendations\n\n";
    }

    private function processUser($user)
    {
        $projects         = $this->projects;
        $featuredProjects = $this->featuredProjects;

        // Loop through featured projects
        foreach ($featuredProjects as $key => $project) {
            // check gender match
            if ($project->getGender()) {
                if ($project->getGender() == 'female' && $user->getGender() != 'f') {
                    unset($featuredProjects[$key]);
                    continue;
                } elseif ($project->getGender() == 'male' && $user->getGender() != 'm') {
                    unset($featuredProjects[$key]);
                    continue;
                }
            }

            // check studio access match
            if ($project->getStudioAccess() && !$user->getStudioAccess()) {
                unset($featuredProjects[$key]);
                continue;
            }

            // Check pro required
            if ($project->getProRequired() && !$user->getIsCertified()) {
                unset($featuredProjects[$key]);
                continue;
            }

            // Check to see if they have already bidded
            $bid = $this->em->getRepository('App:ProjectBid')->
                    findOneBy(['project' => $project, 'user_info' => $user]);
            if ($bid) {
                unset($featuredProjects[$key]);
                continue;
            }
        }

        // Look through projects, if they are not looking for that type, then remove
        foreach ($projects as $key => $project) {

            // check gender match
            if ($project->getGender()) {
                if ($project->getGender() == 'female' && $user->getGender() != 'f') {
                    unset($projects[$key]);
                    continue;
                } elseif ($project->getGender() == 'male' && $user->getGender() != 'm') {
                    unset($projects[$key]);
                    continue;
                }
            }

            // check studio access match
            if ($project->getStudioAccess() && !$user->getStudioAccess()) {
                unset($projects[$key]);
                continue;
            }

            // Check pro required
            if ($project->getProRequired() && !$user->getIsCertified()) {
                unset($projects[$key]);
                continue;
            }

            // Check to see if they have already bidded
            $bid = $this->em->getRepository('App:ProjectBid')->
                    findOneBy(['project' => $project, 'user_info' => $user]);
            if ($bid) {
                unset($projects[$key]);
                continue;
            }

            // Check to see if they have vocalist fee
            if ($user->getVocalistFee()) {
                if ($project->getBudgetTo() < $user->getVocalistFee()) {
                    unset($projects[$key]);
                }
            }

//            // check vocal styles match
//            $projectStyles = $project->getVocalStyles();
//
//            if (count($projectStyles) === 0) {
//                continue;
//            }
//
//            $userStyles = $user->getUserVocalStyles();
//            $matchedStyle = false;
//
//            foreach ($projectStyles as $style) {
//                foreach ($userStyles as $userStyle) {
//                    if ($style->getId() == $userStyle->getVocalStyle()->getId()) {
//                        $matchedStyle = true;
//                        break;
//                    }
//                }
//                if ($matchedStyle) {
//                    break;
//                }
//            }
//
//            if (!$matchedStyle) {
//                unset($projects[$key]);
//                continue;
//            }
        }

        if (count($projects) == 0 && count($featuredProjects) == 0) {
            return false;
        }

        $gigsHtml = $this->container->get('templating')->render('VocalizrAppBundle:Mail:vocalistProjectRecommendations.html.twig', [
            'user'             => $user,
            'projects'         => $projects,
            'featuredProjects' => $featuredProjects,
        ]);

        $data = [
            'USERNAME' => $user->getUsernameOrFirstName(),
            'GIGS'     => $gigsHtml,
        ];
        $this->message->addTo($user->getEmail());
        $this->message->addMergeVars($user->getEmail(), $data);
    }

    private function sendEmail()
    {
        echo "Sending emails\n";
        $this->dispatcher->send($this->message, 'gig-recommendations-to-vocalists');
    }
}