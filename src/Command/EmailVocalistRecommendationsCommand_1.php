<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class EmailVocalistRecommendationsCommand_1 extends Command
{
    const BUNCH_SIZE = 10;  // bunch emails to reduce calls to mandrill

    private $projects = [];

    protected function configure()
    {
        // How often do we run this script
        $this->_timeAgo = '24 hours';

        $this->setName('vocalizr:email-vocalist-recommendations3')
             ->setDescription('Email recommendations to vocalists referencing gigs that have been created that they may be interested in looking at.  [Cronjob: Every ' . $this->_timeAgo . ']');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->container  = $container  = $this->getContainer();
        $this->em         = $container->get('doctrine')->getManager();
        $this->dispatcher = $container->get('hip_mandrill.dispatcher');

        $this->message = new \Hip\MandrillBundle\Message();
        $this->message->setPreserveRecipients(false);
        $this->message
            ->setTrackOpens(true)
            ->setTrackClicks(true);

        echo "SCRIPT START - Email Vocalist Recommendations\n";
        $today     = new \DateTime();
        $yesterday = clone $today;
        $yesterday->sub(new \DateInterval('P1D')); // change this first day to P100D

        // get projects that are
        // - published publicly
        // - created yesterday
        $q = $this->em->getRepository('App:Project')
                ->createQueryBuilder('p')
                ->select('p, u')
                ->innerJoin('p.user_info', 'u')
                ->where('p.publish_type = :publishType')
                ->andWhere('p.project_type = :projectType')
                ->andWhere('p.looking_for = :vocalist')
                ->andWhere('p.published_at >= :yesterday')
                ->andWhere('p.published_at < :today')
                ->andWhere('p.project_bid is null')
                ->setParameter('publishType', \App\Entity\Project::PUBLISH_PUBLIC)
                ->setParameter('projectType', \App\Entity\Project::PROJECT_TYPE_PAID)
                ->setParameter('vocalist', 'vocalist')
                ->setParameter('yesterday', $yesterday)
                ->setParameter('today', $today)
                ->orderBy('p.user_info');

        $this->projects = $q->getQuery()->execute();

        if (count($this->projects) == 0) {
            echo "NO NEW PROJECTS\n";
            echo "SCRIPT END - Email Vocalist Recommendations\n\n";
            return;
        }

        $this->generateProjectHtml();

        // get all the vocalists - this will need to be altered due to performance
        $q = $this->em->getRepository('App:UserInfo')
                ->createQueryBuilder('u')
                ->select('u')
                ->leftJoin('u.user_pref', 'up') // join user preferences for that user
                ->where('u.is_active = true')
                ->andWhere('u.is_vocalist = true')
                ->andWhere('(up.id IS NULL OR up.email_new_projects = 1)');
        $vocalists = $q->getQuery()->execute();

        foreach ($vocalists as $vocalist) {

            // determine which of the created projects this user has not bid no yet

            $this->processVocalist($vocalist);
        }

        echo 'SENDING EMAILS...';
        $this->sendEmail();
        echo "DONE\n";
        echo "SCRIPT END - Email Vocalist Recommendations\n\n";
    }

    private function generateProjectHtml()
    {
        $this->projectHtml = '<table cellpadding="0" cellspacing="0" border="0" width="100%">';

        foreach ($this->projects as $project) {
            $this->projectHtml .= '<tr>';

            $this->projectHtml .= '<td>';

            $this->projectHtml .= '<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: 13px; padding-top: 13px; padding-right: 18px; padding-bottom: 13px; padding-left: 18px; border: 1px solid #e6e6e6; background: #f6f6f6;">';

            $this->projectHtml .= '<tr>';

            $this->projectHtml .= '<td>';

            $this->projectHtml .= '<div style="padding-bottom: 5px;"><a href="' . $this->container->get('router')->generate('project_view', [
                'uuid' => $project->getUuid(),
            ], true) . '" style="color: #14b9d6; font-size: 16px; font-weight: bold;">' . $project->getTitle() . '</a></div>';
            $this->projectHtml .= '<div style="font-size: 12px; color: #333333; padding-bottom: 5px; font-weight: bold;">Looking for: <span style="font-weight: normal; margin-right: 18px;">' . ($project->getGender() ? ucwords($project->getGender()) : '') . ' ' . $project->getLookingFor() . '</span></div>';
            $this->projectHtml .= '<div style="font-size: 12px; color: #333333; padding-bottom: 5px; font-weight: bold;">';
            if ($project->getProjectType() == 'paid') {
                $this->projectHtml .= 'Budget: <span style="font-weight: normal; margin-right: 18px;">$' . $project->getBudgetFrom() . ($project->getBudgetTo() > 0 ? ' - $' . $project->getBudgetTo() : '+') . '</span>';
            } else {
                $this->projectHtml .= '<span style="color: #ffffff; border-radius: 2px; display: inline-block; font-size: 10px; font-weight: bold; height: 17px; padding-top: 1px; padding-left: 10px; padding-bottom: 1px; padding-right: 11px; line-height: 17px; background: #f69d00; margin-right: 18px;">COLLABORATION</span>';
            }
            if ($project->getRoyalty() > 0) {
                $this->projectHtml .= 'Royalties offered: <span style="font-weight: normal;">' . $project->getRoyalty() . '%';
                if ($project->getRoyaltyMechanical()) {
                    $this->projectHtml .= ' (M)';
                }
                if ($project->getRoyaltyPerformance()) {
                    $this->projectHtml .= ' (P)';
                }
                $this->projectHtml .= '</span>';
            }
            $this->projectHtml .= '</div>';
            $this->projectHtml .= '<div style="font-size: 12px; color: #333333; padding-bottom:20px;">' . nl2br($project->getDescription()) . '</div>';
            $this->projectHtml .= '<a href="' . $this->container->get('router')->generate('project_view', [
                'uuid' => $project->getUuid(),
            ], true) . '" style="color: #fff; font-size: 14px; background-color: #14b9d6; text-decoration: none; border-radius: 3px; padding: 8px 16px; border-width: 1px; border-style: solid; border-color: #1aadc7;">Place your bid</a>';
            $this->projectHtml .= '<div style="height:10px;"></div></td>';

            $this->projectHtml .= '<td style="text-align: right; vertical-align: top; padding-top: 21px;">';
            $this->projectHtml .= '</td>';

            $this->projectHtml .= '</tr>';

            $this->projectHtml .= '</table>';

            $this->projectHtml .= '</td>';

            $this->projectHtml .= '</tr>';
        }

        $this->projectHtml .= '</table>';
    }

    private function processVocalist($vocalist)
    {
        $this->message->addTo($vocalist->getEmail());
        $this->message->addMergeVar($vocalist->getEmail(), 'USERNAME', $vocalist->getUsernameOrFirstName());
    }

    private function sendEmail()
    {
        $this->message->addGlobalMergeVar('GIGS', $this->projectHtml);
        $this->dispatcher->send($this->message, 'gig-recommendations-to-vocalists');
    }
}