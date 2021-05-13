<?php

namespace App\Command;

use Doctrine\ORM\EntityManager;
use Slot\MandrillBundle\Message;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use App\Repository\UserCertificationRepository;
use App\Service\MembershipSourceHelper;

/**
 * Class EmailDailyReportCommand
 *
 * @package App\Command
 *
 * @property ContainerInterface $container
 * @property EntityManager      $em
 */
class EmailDailyReportCommand extends Command
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

        $this->setName('vocalizr:email-daily-report')
             ->setDescription('Email a report to vocalizr directors with a summary of activity from the last 24 hours.  [Cronjob: Every ' . $this->_timeAgo . ']');

        $this
            ->addOption('no-email', 'm', InputOption::VALUE_NONE, 'Do not send any email.')
            ->addOption('no-spreadsheet', 'g', InputOption::VALUE_NONE, 'Do not update spreadsheet.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container       = $this->container;
        $this->em        = $container->get('doctrine')->getManager();
        $dispatcher      = $container->get('hip_mandrill.dispatcher');

        echo "SCRIPT START\n\n";
        $today     = new \DateTime();
        $yesterday = clone $today;
        $yesterday->sub(new \DateInterval('P1D'));

        $yesterdayMidnight = (clone $yesterday);
        $yesterdayMidnight->modify('midnight');

        $monthStart = clone $today;
        $monthStart->modify('first day of this month 00:00:00');

        $weekAgo = clone $today;
        $weekAgo->modify('-1 week')->modify('midnight');

        // determine total number of users
        $q = $this->em->getRepository('App:UserInfo')
                ->createQueryBuilder('ui')
                ->select('count(ui)')
                ->where('ui.is_active = true');
        $numUsers = $q->getQuery()->getSingleScalarResult();
        echo 'NUM USERS: ' . $numUsers . "\n";

        // determine total number of subscribed users
        $q = $this->em->getRepository('App:UserInfo')
                ->createQueryBuilder('ui')
                ->select('count(ui)')
                ->where('ui.is_active = true')
                ->andWhere('ui.subscription_plan is not null');
        $numSubscribedUsers = $q->getQuery()->getSingleScalarResult();
        echo 'NUM SUBSCRIBED USERS: ' . $numSubscribedUsers . "\n";

        // determine total number of producers
        $q = $this->em->getRepository('App:UserInfo')
                ->createQueryBuilder('ui')
                ->select('count(ui)')
                ->where('ui.is_active = true')
                ->andWhere('ui.is_producer = true');
        $numProducers = $q->getQuery()->getSingleScalarResult();
        echo 'NUM PRODUCERS: ' . $numProducers . "\n";

        // determine total number of vocalists
        $q = $this->em->getRepository('App:UserInfo')
                ->createQueryBuilder('ui')
                ->select('count(ui)')
                ->where('ui.is_active = true')
                ->andWhere('ui.is_vocalist = true');
        $numVocalists = $q->getQuery()->getSingleScalarResult();
        echo 'NUM VOCALISTS: ' . $numVocalists . "\n";

        // determine total number of connected users
        $q = $this->em->getRepository('App:UserConnect')
                ->createQueryBuilder('uc')
                ->select('count(uc)')
                ->where('uc.engaged = 1');
        $numConnections = $q->getQuery()->getSingleScalarResult();
        echo 'NUM CONNECTIONS: ' . $numConnections . "\n";

        // determine total number of new user connections
        $q = $this->em->getRepository('App:UserConnect')
            ->createQueryBuilder('uc')
            ->select('count(uc)')
            ->where('uc.engaged = 1')
            ->andWhere('uc.created_at > :yesterday')
            ->setParameter('yesterday', $yesterday)
        ;
        $numNewConnections = $q->getQuery()->getSingleScalarResult();
        echo 'NUM CONNECTIONS: ' . $numNewConnections . "\n";

        // determine number of gigs published this month
        $q = $this->em->getRepository('App:Project')
                ->createQueryBuilder('p')
                ->select('count(p)')
                ->where('p.publish_type is not null')
                ->andWhere('p.published_at > :monthStart')
                ->setParameter('monthStart', $monthStart);
        $numGigsPublishedThisMonth = $q->getQuery()->getSingleScalarResult();
        echo 'NUM GIGS PUBLISHED THIS MONTH: ' . $numGigsPublishedThisMonth . "\n";

        // determine number of gigs currently published
        $q = $this->em->getRepository('App:Project')
                ->createQueryBuilder('p')
                ->select('count(p)')
                ->where('p.publish_type is not null')
                ->andWhere('p.published_at is not null')
                ->andWhere('p.project_bid is null')
                ->andWhere('p.bids_due >= :today')
                ->setParameter('today', $today);
        $numGigsCurrentlyPublished = $q->getQuery()->getSingleScalarResult();
        echo 'NUM GIGS CURRENTLY PUBLISHED: ' . $numGigsCurrentlyPublished . "\n";

        // determine number of gigs in the studio currently
        $q = $this->em->getRepository('App:Project')
                ->createQueryBuilder('p')
                ->select('count(p)')
                ->where('p.publish_type is not null')
                ->andWhere('p.project_bid is not null')
                ->andWhere('p.is_complete = false');
        $numGigsInStudio = $q->getQuery()->getSingleScalarResult();
        echo 'NUM GIGS CURRENTLY IN STUDIO: ' . $numGigsInStudio . "\n";

        // determine number of users who started but didn't complete registration
        $q = $this->em->getRepository('App:UserInfo')
                ->createQueryBuilder('ui')
                ->select('count(ui)')
                ->where('ui.is_active = false')
                ->andWhere('ui.date_registered > :yesterday')
                ->setParameter('yesterday', $yesterday);
        $numIncompleteUsers = $q->getQuery()->getSingleScalarResult();
        echo 'NUM INCOMPLETE USERS: ' . $numIncompleteUsers . "\n";

        // determine number of new users
        $q = $this->em->getRepository('App:UserInfo')
                ->createQueryBuilder('ui')
                ->select('count(ui)')
                ->where('ui.is_active = true')
                ->andWhere('ui.date_registered > :yesterday')
                ->setParameter('yesterday', $yesterday);
        $numNewUsers = $q->getQuery()->getSingleScalarResult();
        echo 'NUM NEW USERS: ' . $numNewUsers . "\n";

        // determine number of new subscriptions
        $q = $this->em->getRepository('App:UserSubscription')
                ->createQueryBuilder('us')
                ->select('count(us)')
                ->where('us.is_active = true')
                ->andWhere('us.date_commenced > :yesterday')
                ->setParameter('yesterday', $yesterday);
        $numNewSubscribers = $q->getQuery()->getSingleScalarResult();
        echo 'NUM NEW SUBSCRIBERS: ' . $numNewSubscribers . "\n";

        // determine number cancelled subscriptions
        $q = $this->em->getRepository('App:UserSubscription')
                ->createQueryBuilder('us')
                ->select('count(us)')
                ->where('us.is_active = false')
                ->andWhere('us.date_ended > :yesterday')
                ->setParameter('yesterday', $yesterday);
        $numCancelledSubscribers = $q->getQuery()->getSingleScalarResult();
        echo 'NUM CANCELLED SUBSCRIBERS: ' . $numCancelledSubscribers . "\n";

        // determine number cancelled subscriptions by user
        $numCancelledSubsByUser = $this->em->getRepository('App:UserSubscription')
            ->findCountCancelledSubsByUser();
        echo 'NUM CANCELLED SUBSCRIBERS BY USER: ' . $numCancelledSubsByUser . "\n";

        // determine number cancelled subscriptions by stripe
        $numCancelledSubsByStripe = $this->em->getRepository('App:UserSubscription')
            ->findCountCancelledSubsByStripe();
        echo 'NUM CANCELLED SUBSCRIBERS BY STRIPE/PAYPAL: ' . $numCancelledSubsByStripe . "\n";

        // determine number of new producers
        $q = $this->em->getRepository('App:UserInfo')
                ->createQueryBuilder('ui')
                ->select('count(ui)')
                ->where('ui.is_active = true')
                ->andWhere('ui.is_producer = true')
                ->andWhere('ui.date_registered > :yesterday')
                ->setParameter('yesterday', $yesterday);
        $numNewProducers = $q->getQuery()->getSingleScalarResult();
        echo 'NUM NEW PRODUCERS: ' . $numNewProducers . "\n";

        // determine number of new vocalists
        $q = $this->em->getRepository('App:UserInfo')
                ->createQueryBuilder('ui')
                ->select('count(ui)')
                ->where('ui.is_active = true')
                ->andWhere('ui.is_vocalist = true')
                ->andWhere('ui.date_registered > :yesterday')
                ->setParameter('yesterday', $yesterday);
        $numNewVocalists = $q->getQuery()->getSingleScalarResult();
        echo 'NUM NEW VOCALISTS: ' . $numNewVocalists . "\n";
        $q = $this->em->getRepository('App:UserInfo')
                ->createQueryBuilder('ui')
                ->select('count(ui)')
                ->where('ui.is_active = true')
                ->andWhere('ui.subscription_plan is not null')
                ->andWhere('ui.date_registered > :yesterday')
                ->setParameter('yesterday', $yesterday);
        $numNewSubscribedUsers = $q->getQuery()->getSingleScalarResult();
        echo 'NUM NEW SUBSCRIBED USERS: ' . $numNewSubscribedUsers . "\n";

        // determine number of new gigs (unpublished)
        $q = $this->em->getRepository('App:Project')
                ->createQueryBuilder('p')
                ->select('count(p)')
                ->where('p.publish_type is null')
                ->andWhere('p.created_at > :yesterday')
                ->setParameter('yesterday', $yesterday);
        $numNewGigs = $q->getQuery()->getSingleScalarResult();
        echo 'NUM NEW GIGS: ' . $numNewGigs . "\n";

        // determine number of new gigs published
        $q = $this->em->getRepository('App:Project')
                ->createQueryBuilder('p')
                ->select('count(p)')
                ->where('p.publish_type is not null')
                ->andWhere('p.published_at > :yesterday')
                ->setParameter('yesterday', $yesterday);
        $numNewPublishedGigs = $q->getQuery()->getSingleScalarResult();
        echo 'NUM NEW PUBLISHED GIGS: ' . $numNewPublishedGigs . "\n";

        // determine number of bids placed
        $q = $this->em->getRepository('App:ProjectBid')
                ->createQueryBuilder('pb')
                ->select('count(pb)')
                ->where('pb.created_at > :yesterday')
                ->setParameter('yesterday', $yesterday);
        $numNewBids = $q->getQuery()->getSingleScalarResult();
        echo 'NUM NEW BIDS: ' . $numNewBids . "\n";

        // determine number of gigs awarded
        $q = $this->em->getRepository('App:Project')
                ->createQueryBuilder('p')
                ->select('count(p)')
                ->where('p.awarded_at > :yesterday')
                ->setParameter('yesterday', $yesterday);
        $numAwardedGigs = $q->getQuery()->getSingleScalarResult();
        echo 'NUM AWARDED GIGS: ' . $numAwardedGigs . "\n";

        // determine number of hire now requests
        $q = $this->em->getRepository('App:Project')
                ->createQueryBuilder('p')
                ->select('count(p)')
                ->where('p.publish_type = :private')
                ->andWhere('p.published_at > :yesterday')
                ->andWhere('p.hire_user is not null')
                ->andWhere('p.awarded_at is null')
                ->setParameter('yesterday', $yesterday)
                ->setParameter('private', 'private');
        $numNewHireNowRequests = $q->getQuery()->getSingleScalarResult();
        echo 'NUM NEW HIRE NOW REQUESTS: ' . $numNewHireNowRequests . "\n";

        // determine number of hire now gigs awarded
        $q = $this->em->getRepository('App:Project')
                ->createQueryBuilder('p')
                ->select('count(p)')
                ->where('p.publish_type = :private')
                ->andWhere('p.hire_user is not null')
                ->andWhere('p.awarded_at > :yesterday')
                ->setParameter('yesterday', $yesterday)
                ->setParameter('private', 'private');
        $numNewHireNowAwarded = $q->getQuery()->getSingleScalarResult();
        echo 'NUM NEW HIRE NOW AWARDED: ' . $numNewHireNowAwarded . "\n";

        // determine number of gigs completed
        $q = $this->em->getRepository('App:Project')
                ->createQueryBuilder('p')
                ->select('count(p)')
                ->where('p.completed_at > :yesterday')
                ->setParameter('yesterday', $yesterday);
        $numCompletedGigs = $q->getQuery()->getSingleScalarResult();
        echo 'NUM COMPLETED GIGS: ' . $numCompletedGigs . "\n";

        // determine number of connection requests
        $q = $this->em->getRepository('App:UserConnectInvite')
                ->createQueryBuilder('uci')
                ->select('count(uci)')
                ->where('uci.created_at > :yesterday')
                ->setParameter('yesterday', $yesterday);
        $numConnectionRequests = $q->getQuery()->getSingleScalarResult();
        echo 'NUM CONNECTION REQUESTS: ' . $numConnectionRequests . "\n";

        // determine number of connection accepts
        $q = $this->em->getRepository('App:UserConnectInvite')
                ->createQueryBuilder('uci')
                ->select('count(uci)')
                ->where('uci.connected_at > :yesterday')
                ->setParameter('yesterday', $yesterday);
        $numConnectionAccepts = $q->getQuery()->getSingleScalarResult();
        echo 'NUM CONNECTION ACCEPTS: ' . $numConnectionAccepts . "\n";

        // determine number of wallet deposits
        $q = $this->em->getRepository('App:UserWalletTransaction')
                ->createQueryBuilder('uwt')
                ->select('count(uwt)')
                ->where('uwt.created_at > :yesterday')
                ->andWhere('uwt.amount > 0')
                ->setParameter('yesterday', $yesterday);
        $numWalletDeposits = $q->getQuery()->getSingleScalarResult();
        echo 'NUM WALLET DEPOSITS: ' . $numWalletDeposits . "\n";

        // new wallet withdrawel requests
        $q = $this->em->getRepository('App:UserWithdraw')
                ->createQueryBuilder('uw')
                ->select('count(uw)')
                ->where('uw.created_at > :yesterday')
                ->andWhere('uw.status = :pending')
                ->setParameter('yesterday', $yesterday)
                ->setParameter('pending', 'pending');
        $numWithdrawRequests = $q->getQuery()->getSingleScalarResult();
        echo 'NUM WALLET WITHDRAW REQUESTS: ' . $numWithdrawRequests . "\n";

        // new engine room orders
        $q = $this->em->getRepository('App:EngineOrder')
                ->createQueryBuilder('e')
                ->select('count(e)')
                ->where('e.created_at > :yesterday')
                ->andWhere('e.status = :paid')
                ->setParameter('yesterday', $yesterday)
                ->setParameter('paid', 'PAID');
        $numPaidEngineOrders = $q->getQuery()->getSingleScalarResult();
        echo 'NUM NEW ENGINE ORDERS: ' . $numPaidEngineOrders . "\n";

        // completed engine room orders
        $q = $this->em->getRepository('App:EngineOrder')
                ->createQueryBuilder('e')
                ->select('count(e)')
                ->where('e.created_at > :yesterday')
                ->andWhere('e.status = :completed')
                ->setParameter('yesterday', $yesterday)
                ->setParameter('completed', 'COMPLETED');
        $numCompletedEngineOrders = $q->getQuery()->getSingleScalarResult();
        echo 'NUM COMPLETED ENGINE ORDERS: ' . $numCompletedEngineOrders . "\n";

        // determine number of engine room orders paid this month
        $q = $this->em->getRepository('App:EngineOrder')
                ->createQueryBuilder('e')
                ->select('count(e.id)')
                ->where("e.status != 'DRAFT'")
                ->andWhere('e.created_at > :monthStart')
                ->setParameter('monthStart', $monthStart);
        $numEngineOrdersThisMonth = $q->getQuery()->getSingleScalarResult();
        echo 'NUM ENGINE ORDERS THIS MONTH: ' . $numEngineOrdersThisMonth . "\n";

        $q = $this->em->getRepository('App:ProjectBid')->createQueryBuilder('pb')
            ->select('count(pb.id)')
            ->leftJoin('pb.user_info', 'u')
            ->where('u.subscription_plan IS NOT NULL')
            ->andWhere('pb.created_at > :yesterday')
            ->setParameter('yesterday', $yesterday)
        ;
        $numProBidsThisDay = $q->getQuery()->getSingleScalarResult();
        $q                 = $this->em->getRepository('App:ProjectBid')->createQueryBuilder('pb')
            ->select('count(pb.id)')
            ->leftJoin('pb.user_info', 'u')
            ->where('u.subscription_plan IS NULL')
            ->andWhere('pb.created_at >= :yesterday')
            ->setParameter('yesterday', $yesterday)
        ;
        $numNotProBidsThisDay = $q->getQuery()->getSingleScalarResult();
        echo 'Number of bids (in the last 24hrs) by users who ARE subscribed to PRO: ' . $numProBidsThisDay . "\n";
        echo 'Number of bids (in the last 24hrs) by users who ARE NOT subscribed to PRO: ' . $numNotProBidsThisDay . "\n";

        $q = $this->em->getRepository('App:ProjectBid')->createQueryBuilder('pb')
            ->select('count(pb.id)')
            ->leftJoin('pb.user_info', 'u')
            ->where('u.date_activated >= :weekAgo')
            ->andWhere('pb.created_at >= :yesterday') //TODO: clarify, should we count only yesterday's bids.
            ->setParameter('yesterday', $yesterday)
            ->setParameter('weekAgo', $weekAgo)
        ;
        $num1WeekUserBids = $q->getQuery()->getSingleScalarResult();
        echo "Number of bids by Week 1 Users (users who are new to the system within the last 7 days): $num1WeekUserBids \n";

        $q = $this->em->getRepository('App:Project')->createQueryBuilder('p')
            ->select('count(p.id)')
            ->leftJoin('p.user_info', 'u')
            ->where('u.subscription_plan IS NULL')
            ->andWhere('p.created_at >= :yesterday')
            ->setParameter('yesterday', $yesterday)
        ;
        $numNewNotProProjects = $q->getQuery()->getSingleScalarResult();
        echo "Number of new Gigs or Contests created (in the last 24hrs) by people who are NOT subscribed to PRO: $numNewNotProProjects \n";

        $q = $this->em->getRepository('App:Project')->createQueryBuilder('p')
            ->select('count(p.id)')
            ->leftJoin('p.user_info', 'u')
            ->where('u.subscription_plan IS NOT NULL')
            ->andWhere('p.created_at >= :yesterday')
            ->setParameter('yesterday', $yesterday)
        ;
        $numNewProProjects = $q->getQuery()->getSingleScalarResult();
        echo "Number of new Gigs or Contests created (in the last 24hrs) by people who ARE subscribed to PRO: $numNewProProjects \n";

        $q = $this->em->getRepository('App:UserInfo')->createQueryBuilder('u')
            ->select('count(u.id)')
            ->innerJoin('u.user_subscriptions', 'us')
            ->where('us.date_commenced > :yesterday')
            ->andWhere("u.date_activated >= DATE_ADD(us.date_commenced, '-1', 'day')")
            ->setParameter('yesterday', $yesterday)
        ;
        $num1DayUsersPro = $q->getQuery()->getSingleScalarResult();
        echo "Number of NEW users who have subscribed to PRO within 24hours of signing up to Vocalizr: $num1DayUsersPro \n";

        $q = $this->em->getRepository('App:UserInfo')->createQueryBuilder('u')
            ->select('count(u.id)')
            ->innerJoin('u.user_subscriptions', 'us')
            ->where('us.date_commenced > :yesterday')
            ->andWhere("u.date_activated >= DATE_ADD(us.date_commenced, '-7', 'day')")
            ->setParameter('yesterday', $yesterday)
        ;
        $num1WeekUsersPro = $q->getQuery()->getSingleScalarResult();
        echo "Number of NEW users who have subscribed to PRO within 1 week of signing up to Vocalizr: $num1WeekUsersPro \n";

        $q = $this->em->getRepository('App:UserInfo')->createQueryBuilder('u')
            ->select('count(u.id) as sub_count, us.source')
            ->innerJoin('u.user_subscriptions', 'us')
            ->where('us.date_commenced > :yesterday')
            ->andWhere('us.source <> :excluded_source')
            ->andWhere('us.is_active = 1')
            ->groupBy('us.source')
            ->setParameters([
                'yesterday'       => $yesterday,
                'excluded_source' => MembershipSourceHelper::SUB_SOURCE_DIRECT,
            ])
        ;
        $newSubscriptionsBySource         = $q->getQuery()->getResult();
        $tempIndexedSubscriptionsBySource = [];
        foreach ($newSubscriptionsBySource as $subscription) {
            $tempIndexedSubscriptionsBySource[$subscription['source']] = (int) $subscription['sub_count'];
        }
        $newSubscriptionsBySource = $tempIndexedSubscriptionsBySource;
        foreach (MembershipSourceHelper::$sources as $source) {
            if (!array_key_exists($source, $newSubscriptionsBySource)) {
                $newSubscriptionsBySource[$source] = 0;
            }
        }
        echo "Number of users who subscribed to PRO via Hamburger menu: {$newSubscriptionsBySource[MembershipSourceHelper::SUB_SOURCE_HAMBURGER_MENU]}\n";
        echo "Number of users who subscribed to PRO via Banner: {$newSubscriptionsBySource[MembershipSourceHelper::SUB_SOURCE_BANNER]}\n";
        echo "Number of NEW users who subscribed to PRO in onboarding: {$newSubscriptionsBySource[MembershipSourceHelper::SUB_SOURCE_ONBOARD]}\n";
        echo "Number of users who subscribed to PRO via Connections Modal: {$newSubscriptionsBySource[MembershipSourceHelper::SUB_SOURCE_CONNECTIONS_MODAL]}\n";
        echo "Number of users who subscribed to PRO via bid 'Make Your bid PRO' button: {$newSubscriptionsBySource[MembershipSourceHelper::SUB_SOURCE_MAKE_YOUR_BID_PRO_BUTTON]}\n";

        $q = $this->em->getRepository('App:MessageThread')->createQueryBuilder('mt')
            ->select('count(mt.id)')
            ->leftJoin('mt.employer', 'eu')
            ->where('mt.created_at > :yesterday')
            ->andWhere('eu.subscription_plan IS NULL')
            ->andWhere('mt.project IS NOT NULL')
            ->setParameters([
                'yesterday' => $yesterday,
            ])
        ;
        $numNotProThreadsThisDay = $q->getQuery()->getSingleScalarResult();
        $q                       = $this->em->getRepository('App:MessageThread')->createQueryBuilder('mt')
            ->select('count(mt.id)')
            ->leftJoin('mt.employer', 'eu')
            ->where('mt.created_at > :yesterday')
            ->andWhere('eu.subscription_plan IS NOT NULL')
            ->andWhere('mt.project IS NOT NULL')
            ->setParameters([
                'yesterday' => $yesterday,
            ])
        ;
        $numProThreadsThisDay = $q->getQuery()->getSingleScalarResult();
        echo "Number of chats opened via bids by Gig creators who are NOT subscribed to PRO: $numNotProThreadsThisDay\n";
        echo "Number of chats opened via bids for Gig creators who ARE subscribed to PRO: $numProThreadsThisDay\n";

        /** @var UserCertificationRepository $certificationsRepository */
        $certificationsRepository = $this->em->getRepository('App:UserCertification');

        $newCertRequests24h          = $certificationsRepository->findRequestsCount($yesterday);
        $newCertRequestsMonth        = $certificationsRepository->findRequestsCount($monthStart);
        $successfulCertRequests24h   = $certificationsRepository->findRequestsCount($yesterday, true);
        $successfulCertRequestsMonth = $certificationsRepository->findRequestsCount($monthStart, true);

        if (!$input->getOption('no-email')) {
            $message = new Message();

            $message
//            ->addTo('timofey.n@zimalab.com')    //TODO: delete this after testing.
//            ->addTo('ekaterina.n@zimalab.com')  //TODO: delete this after testing.
                ->addTo('team@vocalizr.com')
            ;
            $message->setTrackOpens(false)
                ->setTrackClicks(false)
                ->addGlobalMergeVar('NUMUSERS', $numUsers)
                ->addGlobalMergeVar('NUMSUBSCRIBERS', $numSubscribedUsers)
                ->addGlobalMergeVar('NUMPRODUCERS', $numProducers)
                ->addGlobalMergeVar('NUMVOCALISTS', $numVocalists)
                ->addGlobalMergeVar('NUMCONNECTIONS', $numConnections)
                ->addGlobalMergeVar('NUMGIGSPUBLISHEDTHISMONTH', $numGigsPublishedThisMonth)
                ->addGlobalMergeVar('NUMGIGSCURRENTLYPUBLISHED', $numGigsCurrentlyPublished)
                ->addGlobalMergeVar('NUMGIGSINSTUDIO', $numGigsInStudio)
                ->addGlobalMergeVar('NUMINCOMPLETEUSERS', $numIncompleteUsers)
                ->addGlobalMergeVar('NUMNEWUSERS', $numNewUsers)
                ->addGlobalMergeVar('NUMNEWSUBSCRIBERS', $numNewSubscribers)
                ->addGlobalMergeVar('NUMCANCELLEDSUBSCRIBERS', $numCancelledSubscribers)
                ->addGlobalMergeVar('NUMCANCELLEDSUBSCRIBERSBYUSERS', $numCancelledSubsByUser)
                ->addGlobalMergeVar('NUMCANCELLEDSUBSCRIBERSBYSTRIPE', $numCancelledSubsByStripe)
                ->addGlobalMergeVar('NUMNEWPRODUCERS', $numNewProducers)
                ->addGlobalMergeVar('NUMNEWVOCALISTS', $numNewVocalists)
                ->addGlobalMergeVar('NUMNEWGIGS', $numNewGigs)
                ->addGlobalMergeVar('NUMNEWPUBLISHEDGIGS', $numNewPublishedGigs)
                ->addGlobalMergeVar('NUMNEWBIDS', $numNewBids)
                ->addGlobalMergeVar('NUMAWARDEDGIGS', $numAwardedGigs)
                ->addGlobalMergeVar('NUMNEWHIRENOWREQUESTS', $numNewHireNowRequests)
                ->addGlobalMergeVar('NUMNEWHIRENOWAWARDED', $numNewHireNowAwarded)
                ->addGlobalMergeVar('NUMCOMPLETEDGIGS', $numCompletedGigs)
                ->addGlobalMergeVar('NUMCONNECTIONREQUESTS', $numConnectionRequests)
                ->addGlobalMergeVar('NUMCONNECTIONACCEPTS', $numConnectionAccepts)
                ->addGlobalMergeVar('NUMWALLETDEPOSITS', $numWalletDeposits)
                ->addGlobalMergeVar('NUM_PAID_ENGINEROOM', $numPaidEngineOrders)
                ->addGlobalMergeVar('NUM_COMPLETED_ENGINEROOM', $numCompletedEngineOrders)
                ->addGlobalMergeVar('NUM_ENGINE_ORDER_THIS_MONTH', $numEngineOrdersThisMonth)
                ->addGlobalMergeVar('NUMWALLETWITHDRAWREQUESTS', $numWithdrawRequests)
            ;

            $this->addGlobalMergeVars($message, [
                'NUM_NOT_PRO_THREADS_THIS_DAY' => $numNotProThreadsThisDay,
                'NUM_NOT_PRO_BIDS_THIS_DAY'    => $numNotProBidsThisDay,
                'NUM_NEW_NOT_PRO_PROJECTS'     => $numNewNotProProjects,
                'NUM_NEW_PRO_PROJECTS'         => $numNewProProjects,
                'NUM_PRO_THREADS_THIS_DAY'     => $numProThreadsThisDay,
                'NUM_PRO_BIDS_THIS_DAY'        => $numProBidsThisDay,
                'NUM_1_WEEK_USER_BIDS'         => $num1WeekUserBids,
                'NUM_1_WEEK_USERS_PRO'         => $num1WeekUsersPro,
                'NUM_1_DAY_USERS_PRO'          => $num1DayUsersPro,

                'NEW_CERT_REQUESTS_24H'        => $newCertRequests24h,
                'NEW_CERT_REQUESTS_MONTH'      => $newCertRequestsMonth,
                'SUCCESSFUL_CERT_REQUESTS_24H' => $successfulCertRequests24h,
                'SUCCESSFUL_REQUESTS_MONTH'    => $successfulCertRequestsMonth,
            ]);

            foreach ($newSubscriptionsBySource as $source => $count) {
                $message->addGlobalMergeVar('NUM_SUB_' . strtoupper($source), $count);
            }

            $dispatcher->send($message, 'daily-report-v2');
        } else {
            $output->writeln('Skip message sending');
        }

        if (!$input->getOption('no-spreadsheet')) {
            try {
                $this->writeStatisticsToGoogleSheet([
                    'Total number of users'                  => $numUsers,
                    'Total number of producers'              => $numProducers,
                    'Total number of vocalists'              => $numVocalists,
                    'Total number of Subscribers'            => $numSubscribedUsers,
                    'Total number of connected users'        => $numConnections,
                    'Gigs published this month'              => $numGigsPublishedThisMonth,
                    'Gigs currently published'               => $numGigsCurrentlyPublished,
                    'Gigs currently in studio'               => $numGigsInStudio,
                    'Engine Room Orders This Month'          => $numEngineOrdersThisMonth,
                    'Number of new incomplete registrations' => $numIncompleteUsers,
                    'Number of new users'                    => $numNewUsers,
                    'Number of new producers'                => $numNewProducers,
                    'Number of new vocalists'                => $numNewVocalists,
                    'Number of new subscriptions'            => $numNewSubscribers,
                    'Number of cancelled subscriptions'      => $numCancelledSubscribers,
                    'Number of cancelled subscriptions by Users'      => $numCancelledSubsByUser,
                    'Number of cancelled subscriptions by Stripe/Paypal'      => $numCancelledSubsByStripe,
                    'Number of new connection requests'      => $numConnectionRequests,
                    'Number of new connections'              => $numNewConnections,
                    'Number of new gigs created'             => $numNewGigs,
                    'Number of new published gigs'           => $numNewPublishedGigs,
                    'Number of new bids'                     => $numNewBids,
                    'Number of new awarded gigs'             => $numAwardedGigs,
                    'Number of new completed gigs'           => $numCompletedGigs,
                    'Number of new HIRE NOW requests'        => $numNewHireNowRequests,
                    'Number of new HIRE NOW awarded'         => $numNewHireNowAwarded,
                    'New Paid Orders'                        => $numPaidEngineOrders,
                    'Completed Orders by Engineer'           => $numCompletedEngineOrders,
                    'Number of new wallet deposits'          => $numWalletDeposits,
                    'Number of new wallet withdraw requests' => $numWithdrawRequests,

                    'Number of bids (in the last 24hrs) by users who ARE NOT subscribed to PRO'  => $numNotProBidsThisDay,
                    'Number of bids (in the last 24hrs) by users who ARE subscribed to PRO'      => $numProBidsThisDay,
                    'Number of new bids by Week 1 Users'                                         => $num1WeekUserBids,

                    'Number of new Gigs or Contests created (in the last 24hrs) by people who are NOT subscribed to PRO' => $numNewNotProProjects,
                    'Number of new Gigs or Contests created (in the last 24hrs) by people who ARE subscribed to PRO'     => $numNewProProjects,
                    'Number of NEW users who have subscribed to PRO within 24hours of signing up to Vocalizr'            => $num1DayUsersPro,
                    'Number of NEW users who have subscribed to PRO within 1 week of signing up to Vocalizr'             => $num1WeekUsersPro,

                    'Number of users who subscribed to PRO via Hamburger menu'                      => $newSubscriptionsBySource[MembershipSourceHelper::SUB_SOURCE_HAMBURGER_MENU],
                    'Number of users who subscribed to PRO via Banner'                              => $newSubscriptionsBySource[MembershipSourceHelper::SUB_SOURCE_BANNER],
                    'Number of NEW users who subscribed to PRO via onboarding'                      => $newSubscriptionsBySource[MembershipSourceHelper::SUB_SOURCE_ONBOARD],
                    'Number of users who subscribed to PRO via Connections Modal'                   => $newSubscriptionsBySource[MembershipSourceHelper::SUB_SOURCE_CONNECTIONS_MODAL],
                    'Number of users who subscribed to PRO via bid \'Make Your bid PRO\' button'    => $newSubscriptionsBySource[MembershipSourceHelper::SUB_SOURCE_MAKE_YOUR_BID_PRO_BUTTON],
                    'Number of chats opened via bids by Gig creators who are NOT subscribed to PRO' => $numNotProThreadsThisDay,
                    'Number of chats opened via bids for Gig creators who ARE subscribed to PRO'    => $numProThreadsThisDay,

                    'Number of unique users who have signed in today'                              => '',
                    'Number of unique users who have signed in this week'                          => '',
                    'Number of unique users who have signed in this month'                         => '',
                    'Number of users who have signed in more than once today'                      => '',
                    'Number of users who have signed in more than once this week'                  => '',
                    'Number of users who have signed in more than once this month'                 => '',
                    'Number of users who have signed in via a \'new Gigs/Contests for you\' email' => '',

                    'New certification requests in last 24hrs'      => $newCertRequests24h,
                    'New certification requests this month'         => $newCertRequestsMonth,
                    'Total successful certifications in last 24hrs' => $successfulCertRequests24h,
                    'Total successful certifications this month'    => $successfulCertRequestsMonth,
                ]);
            } catch (\Exception $exception) {
                $msg = "Can't send statistics to google sheets: " . $exception->getMessage();
                echo $msg;
                error_log($msg);
            }
        } else {
            $output->writeln('Skip google spreadsheet part');
        }
        echo "\nSCRIPT COMPLETE\n\n";
    }

    /**
     * @param Message $message
     * @param array   $vars
     */
    private function addGlobalMergeVars(Message $message, $vars)
    {
        foreach ($vars as $varName => $value) {
            $message->addGlobalMergeVar($varName, $value);
        }
    }

    /**
     * @param array $statistics
     *
     * @throws \Exception
     * @throws \Google_Exception
     */
    private function writeStatisticsToGoogleSheet($statistics = [])
    {
        $google = $this->container->get('vocalizr_app.google_sheets');
        $google->initClient();
        try {
            $google->openSheet(
                $this->container->getParameter('statistics_google_sheet_id'),
                $this->container->getParameter('statistics_google_list_name')
            );
        } catch (InvalidArgumentException $e) {
            $msg = "Can't send statistics to google: parameters statistics_google_sheet_id and " .
                'statistics_google_list_name must be specified in parameters.yml.'
            ;
            error_log($msg);
            echo $msg;
        }
        $statTokenized     = [];
        $statColumn        = [];
        $yesterdayMidnight = new \DateTime('-1 day midnight');
        $dateRowIndex      = 1;
        $dateColumnOffset  = 1;
        $dateRow           = $google->getRow($dateRowIndex, $dateColumnOffset);

        $lastColumnIndexInSheet = null;
        $lastIndex              = null;
        $lastDate               = null;

        if ($dateRow) {
            foreach ($dateRow as $rowIndex => $dateString) {
                $date = \DateTime::createFromFormat('d/m/Y', $dateString);
                if (!$date) {
                    continue;
                }
                $lastDate  = $date;
                $lastIndex = $rowIndex + $dateColumnOffset;
                if ($date == $yesterdayMidnight) {
                    $lastColumnIndexInSheet = $lastIndex;
                    break;
                }
            }
        }
        if ($lastColumnIndexInSheet === null && $lastDate) {
            $datesDiff = $lastDate->diff($yesterdayMidnight);
            for ($dayOffset = 1; $dayOffset <= $datesDiff->days; $dayOffset++) {
                $lastColumnIndexInSheet = $lastIndex + $dayOffset;
                $date                   = clone $lastDate;
                $date->modify('+' . $dayOffset . ' days midnight');
                $google->setColumn($lastIndex + $dayOffset, $dateRowIndex, [$date->format('d/m/Y')]);
            }
        }

        if ($lastColumnIndexInSheet === null) {
            $lastColumnIndexInSheet = count($dateRow);     // + 1 as an offset in select.
        }

        $column = $google->getColumn(0, 3);

        $column = array_map([$this, 'tokenize'], $column);

        foreach ($statistics as $statName => $statistic) {
            $statTokenized[$this->tokenize($statName)] = $statistic;
        }

        foreach ($column as $colIndex => $sheetStatName) {
            $statFound = false;
            foreach ($statTokenized as $statName => $statValue) {
                if (strpos($sheetStatName, $statName) !== false) {
                    $statColumn[$colIndex] = $statValue;
                    $statFound             = true;
                    unset($statTokenized[$statName]);
                }
            }
            if (!$statFound) {
                if ($sheetStatName) {
                    echo "Stats for sheet row '{$sheetStatName}' was not found\n";
                }
                $statColumn[$colIndex] = '';
            }
        }
        ksort($statColumn);
        array_unshift($statColumn, '');
        array_unshift($statColumn, $yesterdayMidnight->format('d/m/Y'));
        $statColumn = array_values($statColumn);

        $google->setColumn($lastColumnIndexInSheet + 1, $dateRowIndex, $statColumn);
    }

    /**
     * @param string $string
     *
     * @return string
     */
    private function tokenize($string)
    {
        $string = preg_replace('/\s|[^a-z]*/u', '', strtolower($string));
        return $string;
    }
}
