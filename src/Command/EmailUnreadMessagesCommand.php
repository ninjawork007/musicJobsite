<?php

namespace App\Command;

use Slot\MandrillBundle\Message;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EmailUnreadMessagesCommand extends Command
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        $this->container = $container;
    }

    protected function configure()
    {
        // How often do we run this script
        $this->_timeAgo = '10 minutes';

        $this
                ->setName('vocalizr:email-unread-messages')
                ->setDescription('Email unread messages [Cronjob: Every ' . $this->_timeAgo . ']')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container        = $this->container;
        $doctrine         = $container->get('doctrine');
        $em               = $doctrine->getManager();
        $this->dispatcher = $container->get('hip_mandrill.dispatcher');

        $q = $em->getRepository('App:Message')
                ->createQueryBuilder('m')
                ->select('m, mt')
                ->innerJoin('m.message_thread', 'mt')
                ->innerJoin('m.to_user_info', 'tu')
                ->leftJoin('tu.user_pref', 'tup')
                ->where('m.created_at >= :now AND m.read_at IS NULL')
                ->andWhere('mt.bidder_last_read IS NOT NULL')
                ->andWhere('(tup.id IS NULL OR tup.email_messages = 1) AND tu.is_active = 1')
                ->groupBy('m.to_user_info, mt.project')
                ->orderBy('mt.created_at', 'ASC');

        $params = [
            ':now' => date('Y-m-d H:i:s', strtotime('-' . $this->_timeAgo)),
        ];
        $q->setParameters($params);

        $query = $q->getQuery();

        // Loop through results, and get
        $results = $query->execute();

        foreach ($results as $result) {
            $params = [
                ':now' => date('Y-m-d H:i:s', strtotime('-' . $this->_timeAgo)),
            ];
            $messageThread = $result->getMessageThread();
            $project       = $result->getMessageThread()->getProject();
            if ($project && !$project->getIsActive()) {
                continue;
            }
            $toUserInfo   = $result->getToUserInfo();
            $fromUserInfo = $result->getUserInfo();

            // Get unread messages for that project / person
            $qb = $em->getRepository('App:Message')
                    ->createQueryBuilder('m')
                    ->select('m, mt, mf')
                    ->innerJoin('m.message_thread', 'mt')
                    ->leftJoin('m.message_files', 'mf')
                    ->where('m.created_at >= :now AND m.read_at IS NULL')
                    ->andWhere('m.to_user_info = :userInfo')
                    ->orderBy('mt.created_at', 'ASC');

            if ($project) {
                $qb->andWhere('mt.project = :project');
                $params['project'] = $project->getId();
            }

            $params['userInfo'] = $toUserInfo->getId();
            $qb->setParameters($params);

            $query2   = $qb->getQuery();
            $messages = $query2->execute();

            if ($project) {
                echo "\n" . count($messages) . ' Messages for ' . $toUserInfo->getDisplayName() . ' for project: ' . $project->getTitle();

                $subject = 'UNREAD MESSAGE' . (count($messages) > 1 ? 'S' : '') . ' from ' . $fromUserInfo->getDisplayName() . ' on gig "' . $project->getTitle() . '"';
            } else {
                echo "\n" . count($messages) . ' Messages for ' . $toUserInfo->getDisplayName();

                $subject = 'UNREAD PRIVATE MESSAGE' . (count($messages) > 1 ? 'S' : '') . ' from ' . $fromUserInfo->getDisplayName();
            }

            $message = new Message();
            $message->setSubject($subject);
            $message->setFromEmail('noreply@vocalizr.com');
            $message->setFromName('Vocalizr');
            $message
                ->setTrackOpens(true)
                ->setTrackClicks(true);

            $message->addTo($toUserInfo->getEmail());
            $body = $container->get('twig')->render('Mail:unreadMessages.html.twig', [
                'toUserInfo'    => $toUserInfo,
                'fromUserInfo'  => $fromUserInfo,
                'project'       => $project,
                'messages'      => $messages,
                'messageThread' => $messageThread,
            ]);
            $message->addGlobalMergeVar('BODY', $body);
            $this->dispatcher->send($message, 'default');
        }
    }
}