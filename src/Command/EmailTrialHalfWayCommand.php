<?php

namespace App\Command;

use Slot\MandrillBundle\Message;
use Stripe\Subscription;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EmailTrialHalfWayCommand extends Command
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
        $this->_timeAgo = '24 hours';

        $this->setName('vocalizr:email-trial-half-way')
             ->setDescription('Email PRO trialers when they reach half way through their trial. [Cronjob: Every ' . $this->_timeAgo . ']');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container        = $this->container;
        $this->em         = $container->get('doctrine')->getManager();
        $this->dispatcher = $container->get('hip_mandrill.dispatcher');

        echo "SCRIPT START - Email Trial Half Way\n";
        $this->checkDate = date('Y-m-d', strtotime('-15 days'));

        $this->processTrialists();

        echo "SCRIPT END - Email Trial Half Way\n\n";
    }

    private function processTrialists()
    {
        $message = new Message();
        $message->setFromEmail('matt@vocalizr.com');
        $message->setFromName('Matt Chable');
        $message->setSubject('Vocalizr Pro - how is it going for you?');
        $message->setPreserveRecipients(false);
        $message->setTrackOpens(true)
                ->setTrackClicks(true);

        // get the pro pro
        $proPlan = $this->em->getRepository('App:SubscriptionPlan')
                ->findOneBy(['static_key' => 'PRO']);

        // get users that are trialists and started their trial 45 days ago

        $q = $this->em->getRepository('App:UserSubscription')
                ->createQueryBuilder('us')
                ->innerJoin('us.user_info', 'ui')
                ->where('us.subscription_plan = :proPlan')
                ->andWhere('ui.is_active = 1')
                ->andWhere("DATE_FORMAT(us.date_commenced, '%Y-%m-%d') = :date")
                ->andWhere('us.date_ended is null')
                ->setParameter(':proPlan', $proPlan)
                ->setParameter(':date', $this->checkDate);

        $results   = $q->getQuery()->execute();
        $trialists = [];

        $stripeApiKey = $this->container->getParameter('stripe_api_key');
        \Stripe\Stripe::setApiKey($stripeApiKey);

        // loop through them and ensure they are still on trial and haven't cancelled
        foreach ($results as $trialist) {

            // get their subscription from stripe
            try {
                $customer   = \Stripe\Customer::retrieve($trialist->getUserInfo()->getStripeCustId());
                $membership = Subscription::retrieve($trialist->getStripeSubscrId());
            } catch (Exception $e) {
                continue;
            }

            // check that they're on trial
            if ($membership->status != 'trialing') {
                continue;
            }

            // check they haven't cancelled
            if ($membership->canceled_at !== null) {
                continue;
            }

            // just confirming their trial did start 45 days ago
            $startDate = date('Y-m-d', $membership->trial_start);
            if ($startDate !== $this->checkDate) {
                continue;
            }

            $trialists[] = $trialist->getUserInfo();
        }

        $this->addRecipientsAndSend($message, $trialists, 'trialHalfWay');
    }

    private function addRecipientsAndSend($message, $recipients, $template)
    {
        if (count($recipients) > 0) {
            foreach ($recipients as $recipient) {
                $message->addTo($recipient->getEmail());
                $body = $this->container->get('twig')->render('Mail:' . $template . 'connection.html.twig', [
                    'userInfo' => $recipient,
                ]);
                $message->addMergeVar($recipient->getEmail(), 'BODY', $body);
            }

            echo 'SENDING ' . $template . ' EMAILS...';
            $this->sendEmail($message, 'default-matt');
            echo "DONE\n\n";
        }
    }

    private function sendEmail($message, $template)
    {
        $this->dispatcher->send($message, $template);
    }
}
