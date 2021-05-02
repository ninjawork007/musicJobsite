<?php

namespace App\Command;

use Slot\MandrillBundle\Message;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\Project;

/**
 * @property \Hip\MandrillBundle\Dispatcher|object dispatcher
 * @property \Doctrine\ORM\EntityManager em
 * @property Message message
 */
class EmailEmployerRecommendationsCommand extends Command
{
    const BUNCH_SIZE = 10;  // bunch emails to reduce calls to mandrill

    private $lastUser = null;

    private $userData = '';

    private $emailBuffer = [];

    protected function configure()
    {
        // How often do we run this script
        $this->_timeAgo = '24 hours';

        $this->setName('vocalizr:email-employer-recommendations')
             ->setDescription('Email recommendations to users who have created gigs the day after they create, and 7 days before bidding ends.  [Cronjob: Every ' . $this->_timeAgo . ']');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container        = $this->getContainer();
        $this->em         = $container->get('doctrine')->getEntityManager();
        $this->dispatcher = $container->get('hip_mandrill.dispatcher');

        $this->message = new Message();
        $this->message->setPreserveRecipients(false);
        $this->message
            ->setTrackOpens(true)
            ->setTrackClicks(true);

        echo "SCRIPT START\n";
        $today     = new \DateTime();
        $yesterday = clone $today;
        $yesterday->sub(new \DateInterval('P5D'));

        // get projects that are
        // - published publicly
        // - created yesterday
        // - looking for vocalists
        $q = $this->em->getRepository('App:Project')
            ->createQueryBuilder('p')

            ->select('p, u')
            ->innerJoin('p.user_info', 'u')
            ->leftJoin('u.user_pref', 'up') // join user preferences for that user

            ->where('p.publish_type = :publishType')
            ->andWhere('p.published_at >= :yesterday')
            ->andWhere('p.published_at < :today')
            ->andWhere('p.project_bid is null')
            ->andWhere('p.looking_for = :lookingFor')
            ->andWhere('p.is_active = 1')
            ->andWhere('(up.id IS NULL OR up.email_vocalist_suggestions = 1)')

            ->orderBy('p.user_info')

            ->setParameters([
                'publishType' => Project::PUBLISH_PUBLIC,
                'yesterday'   => $yesterday,
                'today'       => $today,
                'lookingFor'  => 'vocalist',
            ])
        ;

        $results = $q->getQuery()->execute();

        foreach ($results as $project) {
            if ($this->lastUser === null) {
                $this->lastUser = $project->getUserInfo();
            } elseif ($this->lastUser != $project->getUserInfo()) {
                $this->processLastUser();
                $this->lastUser = $project->getUserInfo();
                $this->userData = '';
            }

            // required
            $lookingFor   = $project->getLookingFor();
            $gender       = $project->getGender();
            $city         = $project->getCity();
            $language     = $project->getLanguage();
            $studioAccess = $project->getStudioAccess();

            $matchOn = ['city' => false];
            if ($project->getCity()) {
                $matchOn['city'] = true;
            }

            $matchedUsers = $this->getMatchedUsers($project, $matchOn);
            if (count($matchedUsers) === 0 && $matchOn['city'] === true) {
                // try again ignoring city
                $matchOn['city'] = false;
                $matchedUsers    = $this->getMatchedUsers($project, $matchOn);
            }

            // process the matches users to determine best match
            if (count($matchedUsers) === 0) {
                continue;
            }

            $this->userData .= $this->generateProjectHtml($project, $matchedUsers);
        }

        if ($this->lastUser) {
            $this->processLastUser();
            $this->sendBunchOfEmails();
        }
    }

    private function generateProjectHtml($project, $matchedUsers)
    {
        $this->projectHtml = '<table cellpadding="0" cellspacing="0" border="0" width="100%">';
        $this->projectHtml .= '<tr>';
        $this->projectHtml .= '<td>';
        $this->projectHtml .= '<table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin-bottom: 13px; padding-top: 13px; padding-right: 18px; padding-bottom: 13px; padding-left: 18px; border: 1px solid #e6e6e6; background: #f6f6f6;">';
        $this->projectHtml .= '<tr>';
        $this->projectHtml .= '<td style="font-size: 16px; font-weight: bold; padding-bottom: 5px;">';
        $this->projectHtml .= 'Gig: <a href="' . $this->getContainer()->get('router')->generate('project_view', [
            'uuid' => $project->getUuid(),
        ], true) . '" style="color: #14b9d6; font-size: 16px; font-weight: bold;">' . $project->getTitle() . '</a>';
        $this->projectHtml .= '</td>';
        $this->projectHtml .= '</tr>';

        $this->projectHtml .= '<tr>';
        $this->projectHtml .= '<td style="font-size: 12px; color: #333333;">';
        $this->projectHtml .= 'Check out these vocalists and invite them to bid on your gig.';
        $this->projectHtml .= '</td>';
        $this->projectHtml .= '</tr>';

        $counter = 0;
        foreach ($matchedUsers as $user) {
            $counter++;
            $this->projectHtml .= '<tr>';

            $this->projectHtml .= '<td>';

            $this->projectHtml .= '<table cellpadding="0" cellspacing="0" border="0" width="100%" style="padding-top: 13px; padding-right: 18px; padding-bottom: 13px; padding-left: 0; ' . ($counter < count($matchedUsers) ? 'border-bottom-width: 1px; border-bottom-style: solid; border-bottom-color: #999;' : '') . '">';

            $this->projectHtml .= '<tr>';

            $this->projectHtml .= '<td>';

            $this->projectHtml .= '<table cellpadding="0" cellspacing="0" border="0" width="100%">';
            $this->projectHtml .= '<tr>';
            $this->projectHtml .= '<td style="width: 80px;"><img src="http://www.vocalizr.com/uploads/avatar/small/' . $user->getAvatar() . '" width="60" height="60" style="border-radius: 60px;"></td>';

            $this->projectHtml .= '<td style="width: 300px;">';
            $this->projectHtml .= '<div style="padding-bottom: 5px;"><a href="' . $this->getContainer()->get('router')->generate('user_view', [
                'username' => $user->getUsername(),
            ], true) . '" style="color: #14b9d6; font-size: 14px; font-weight: bold;">' . $user->getUsername() . '</a></div>';

            $this->projectHtml .= '<div style="font-size:12px;color:#333333;padding-bottom:5px;font-weight:bold">Gender: <span style="font-weight: normal; margin-right: 15px;">' . ($user->getGender() == 'm' ? 'Male' : 'Female') . '</span> Location: <span style="font-weight: normal;">' . $user->getCity() . ', ' . strtoupper($user->getCountry()) . '</span></div>';
            $this->projectHtml .= '<div style="font-size:12px;color:#333333;padding-bottom:5px;font-weight:bold">Studio Access: <span style="font-weight: normal; margin-right: 15px;">' . ($user->getStudioAccess() == 1 ? 'Yes' : 'No') . '</span></div>';
            $this->projectHtml .= '</td>';
            $this->projectHtml .= '<td>';
            $this->projectHtml .= '<a href="' . $this->getContainer()->get('router')->generate('user_view', [
                'username' => $user->getUsername(),
            ], true) . '" style="color:#fff;font-size:14px;background-color:#14b9d6;text-decoration:none;border-radius:3px;padding:8px 16px;border-width:1px;border-style:solid;border-color:#1aadc7" target="_blank">View profile</a>';
            $this->projectHtml .= '</td>';
            $this->projectHtml .= '</tr>';
            $this->projectHtml .= '</table>';
            $this->projectHtml .= '</td>';

            $this->projectHtml .= '</tr>';

            $this->projectHtml .= '</table>';

            $this->projectHtml .= '</td>';

            $this->projectHtml .= '</tr>';
        }
        $this->projectHtml .= '</table>';
        $this->projectHtml .= '</td>';
        $this->projectHtml .= '</tr>';
        $this->projectHtml .= '</table>';
        return $this->projectHtml;
    }

    /**
     * Grab matching users. Has optionals in case we get no results and
     * want to trim back the criteria a bit
     *
     * @param type $project
     * @param type $matchOn
     *
     * @return array
     */
    private function getMatchedUsers($project, $matchOn)
    {
        $q = $this->em->getRepository('App:UserInfo')
            ->createQueryBuilder('ui')
            ->select('ui, uvt, uvs, uvc, ug')
            ->addSelect('RAND() as HIDDEN rand')
            ->leftJoin('ui.user_voice_tags', 'uvt')
            ->leftJoin('ui.user_vocal_characteristics', 'uvc')
            ->leftJoin('ui.user_vocal_styles', 'uvs')
            ->leftJoin('ui.genres', 'ug')
            ->leftJoin('ui.user_audio', 'ua', 'WITH', 'ua.default_audio = 1')
            ->where('ui.is_active = true')
            ->andWhere('ui.path IS NOT NULL')
            ->andWhere('ua.id IS NOT NULL');

        if ($project->getLookingFor() == 'vocalist') {
            $q->andWhere('ui.is_vocalist = true');
        } else {
            $q->andWhere('ui.is_producer = true');
        }
        if ($project->getGender()) {
            if ($project->getGender() == 'female') {
                $q->andWhere('ui.gender = :gender')
                  ->setParameter('gender', 'f');
            } else {
                $q->andWhere('ui.gender = :gender')
                  ->setParameter('gender', 'm');
            }
        }
        if ($project->getStudioAccess()) {
            $q->andWhere('ui.studio_access = true');
        }
        if ($matchOn['city']) {
            $q->andWhere('ui.city = :city')
              ->setParameter('city', $project->getCity());
        }
        $q->orderBy('rand');
        $q->setMaxResults(6);

        return $q->getQuery()->execute();
    }

    /**
     * Processes the data for the last user and queues the email to be
     * sent to them
     */
    private function processLastUser()
    {
//        if ($this->userData !== '') {
//            $this->message->addTo($this->lastUser->getEmail());
//            $this->message->addMergeVars($this->lastUser->getEmail(), array('USERNAME' => $this->lastUser->getUsernameOrFirstName(),
//                                                                            'VOCALISTS' => $this->userData));
//        }
        if ($this->userData !== '') {
            $this->message->addTo('robert@vocaizr.com');
            $this->message->addMergeVars('robert@vocalizr.com', ['USERNAME' => $this->lastUser->getUsernameOrFirstName(),
                'VOCALISTS'                                                 => $this->userData, ]);
        }
    }

    private function sendBunchOfEmails()
    {
        $this->emailBuffer = [];
        $this->dispatcher->send($this->message, 'employer-project-recommendations');
    }
}