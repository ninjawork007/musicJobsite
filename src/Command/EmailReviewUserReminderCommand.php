<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @property string _timeAgo
 * @property \Hip\MandrillBundle\Dispatcher|object dispatcher
 */
class EmailReviewUserReminderCommand extends Command
{
    protected function configure()
    {
        // How often do we run this script
        $this->_timeAgo = '2 days';

        $this
                ->setName('vocalizr:email-review-user-reminder')
                ->setDescription('Email reminders to users who have not reviewed the other party[Cronjob: Every ' . $this->_timeAgo . ']')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container        = $this->getContainer();
        $doctrine         = $container->get('doctrine');
        $em               = $doctrine->getEntityManager();
        $this->dispatcher = $container->get('hip_mandrill.dispatcher');

        // Get projects that were completed 2 days ago
        $q = $em->getRepository('App:Project')
            ->createQueryBuilder('p')

            ->select('p, ui, ur')
            ->innerJoin('p.user_info', 'ui')
            ->leftJoin('p.user_reviews', 'ur')

            ->where("DATE_FORMAT(p.completed_at, '%Y-%m-%d') = :timeAgo")
            ->andWhere('p.is_active = 1')
        ;

        $params = [
            ':timeAgo' => date('Y-m-d', strtotime($this->_timeAgo)),
        ];
        $q->setParameters($params);

        $results = $q->getQuery()->execute();

        echo 'Total projects ' . count($results) . " projects\n\n";

        // Get list of bids that were placed in the last 5 minutes
        if ($results) {
            foreach ($results as $project) {
                $userReviews = $project->getUserReviews();

                echo 'Project: ' . $project->getTitle() . ' - ' . count($userReviews) . '';

                // If more than 2, ignore they have reviewed each other
                if (count($userReviews) >= 2) {
                    echo " - skipped\n";
                    continue;
                }
                echo "\n";

                $users = [
                    'employer' => $project->getUserInfo(),
                    'employee' => $project->getEmployeeUserInfo(),
                ];

                if (count($userReviews) == 1) {
                    $userReview = $userReviews[0];

                    if ($userReview->getReviewedBy()->getId() == $project->getUserInfo()->getId()) {
                        unset($users['employer']);
                    } else {
                        unset($users['employee']);
                    }
                }

                foreach ($users as $key => $user) {
                    $otherUser = $user == $project->getUserInfo() ? $project->getEmployeeUserInfo() : $project->getUserInfo();

                    $message = new \Hip\MandrillBundle\Message();
                    $message->setSubject("Don't forget to review " . $otherUser->getDisplayName());
                    $message
                        ->setTrackOpens(true)
                        ->setTrackClicks(true);

                    $message->addTo($user->getEmail());
                    $body = $container->get('templating')->render('VocalizrAppBundle:Mail:reviewUserReminder.html.twig', [
                        'user'      => $user,
                        'project'   => $project,
                        'otherUser' => $otherUser,
                    ]);
                    $message->addGlobalMergeVar('BODY', $body);
                    $this->dispatcher->send($message, 'default');
                }
            }
        }
    }
}