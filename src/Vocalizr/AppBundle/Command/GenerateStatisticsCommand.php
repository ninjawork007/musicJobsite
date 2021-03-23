<?php

namespace Vocalizr\AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @property \Doctrine\ORM\EntityManager em
 * @property ContainerInterface container
 */
class GenerateStatisticsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        // How often do we run this script
        $this->_timeAgo = '24 hours';

        $this->setName('vocalizr:generate-statistics')
             ->setDescription('Generate application statistics for Vocalizr.com  [Cronjob: Every ' . $this->_timeAgo . ']');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->container = $container = $this->getContainer();
        $this->em        = $container->get('doctrine')->getEntityManager();

        echo "SCRIPT START\n\n";
        $today = new \DateTime();
        $today->setTime(0, 0, 0);

        // remove all the old data
        $this->em->getRepository('VocalizrAppBundle:Statistics')
                    ->createQueryBuilder('s')
                    ->delete()
                    ->getQuery()
                    ->execute();

        // process daily
        $q = $this->em->getRepository('VocalizrAppBundle:Statistics')
                    ->createQueryBuilder('s')
                    ->select('max(s.end_date)')
                    ->where('s.statistics_type = :day')
                    ->setParameter('day', 'day');
        $maxDate = $q->getQuery()->getSingleScalarResult();

        if (!$maxDate) {
            $startDate = new \DateTime();
            $startDate->setDate(2014, 2, 1);
        } else {
            $startDate = new \DateTime($maxDate);

            // if max date is yesterday then don't do anything since we already
            // done it up to date
            $yesterday = clone $today;
            $yesterday->sub(new \DateInterval('P1D'));
            if ($startDate == $yesterday) {
                echo ">> UP TO DATE -- NOTHING TO PROCESS\n";
                echo "\nSCRIPT COMPLETE\n\n";
                exit;
            }
        }
        $startDate->setTime(0, 0, 0);
        //$this->processStats('day', 'P1D', $startDate, $today);

        // process weekly
        $q = $this->em->getRepository('VocalizrAppBundle:Statistics')
                    ->createQueryBuilder('s')
                    ->select('max(s.end_date)')
                    ->where('s.statistics_type = :week')
                    ->setParameter('week', 'week');
        $maxDate = $q->getQuery()->getSingleScalarResult();

        if (!$maxDate) {
            $startDate = new \DateTime();
            $startDate->setDate(2014, 2, 1);
        } else {
            $startDate = new \DateTime($maxDate);
        }
        $startDate->setTime(0, 0, 0);
        //$this->processStats('week', 'P7D', $startDate, $today);

        // process monthly
        $q = $this->em->getRepository('VocalizrAppBundle:Statistics')
                    ->createQueryBuilder('s')
                    ->select('max(s.end_date)')
                    ->where('s.statistics_type = :month')
                    ->setParameter('month', 'month');
        $maxDate = $q->getQuery()->getSingleScalarResult();

        if (!$maxDate) {
            $startDate = new \DateTime();
            $startDate->setDate(2014, 2, 1);
        } else {
            $startDate = new \DateTime($maxDate);
        }
        $startDate->setTime(0, 0, 0);
        $this->processStats('month', 'P1M', $startDate, $today);

        echo "\nSCRIPT COMPLETE\n\n";
    }

    private function processStats($label, $interval, $startDate, $today)
    {
        echo "\n\nPROCESSING: " . $label . " STATS\n";

        $processDate = clone $startDate;
        while ($processDate < $today) {
            if ($label == 'week' && $processDate->format('D') !== 'Mon') {
                $processDate->add(new \DateInterval('P1D'));
                continue;
            }
            if ($label == 'month' && $processDate->format('d') !== '01') {
                $processDate->add(new \DateInterval('P1D'));
                continue;
            }

            $endDate = clone $processDate;
            $endDate->add(new \DateInterval($interval));
            $recordedEndDate = clone $endDate;
            $recordedEndDate->sub(new \DateInterval('P1D'));

            echo 'PROCESS FROM: ' . $processDate->format('Y-m-d') . ' TO: ' . $recordedEndDate->format('Y-m-d') . "\n";

            $statsEntry = new \Vocalizr\AppBundle\Entity\Statistics();
            $statsEntry->setStatisticsType($label);
            $statsEntry->setStartDate($processDate);
            $statsEntry->setEndDate($recordedEndDate);

            // determine number of new users for date range
            $q = $this->em->getRepository('VocalizrAppBundle:UserInfo')
                    ->createQueryBuilder('ui')
                    ->select('count(ui)')
                    ->where('ui.is_active = true')
                    ->andWhere('ui.date_activated >= :start')
                    ->andWhere('ui.date_activated < :end')
                    ->setParameter('start', $processDate)
                    ->setParameter('end', $endDate);
            $newUsers = $q->getQuery()->getSingleScalarResult();
            $statsEntry->setUsers($newUsers);

            // determine number of new vocalists for date range
            $q = $this->em->getRepository('VocalizrAppBundle:UserInfo')
                    ->createQueryBuilder('ui')
                    ->select('count(ui)')
                    ->where('ui.is_active = true')
                    ->andWhere('ui.is_vocalist = true')
                    ->andWhere('ui.date_activated >= :start')
                    ->andWhere('ui.date_activated < :end')
                    ->setParameter('start', $processDate)
                    ->setParameter('end', $endDate);
            $newVocalists = $q->getQuery()->getSingleScalarResult();
            $statsEntry->setVocalists($newVocalists);

            // determine number of new producers for date range
            $q = $this->em->getRepository('VocalizrAppBundle:UserInfo')
                    ->createQueryBuilder('ui')
                    ->select('count(ui)')
                    ->where('ui.is_active = true')
                    ->andWhere('ui.is_producer = true')
                    ->andWhere('ui.date_activated >= :start')
                    ->andWhere('ui.date_activated < :end')
                    ->setParameter('start', $processDate)
                    ->setParameter('end', $endDate);
            $newProducers = $q->getQuery()->getSingleScalarResult();
            $statsEntry->setProducers($newProducers);

            // determine number of new gigs for date range
            $q = $this->em->getRepository('VocalizrAppBundle:Project')
                    ->createQueryBuilder('p')
                    ->select('count(p)')
                    ->where('p.created_at >= :start')
                    ->andWhere('p.created_at < :end')
                    ->setParameter('start', $processDate)
                    ->setParameter('end', $endDate);
            $newGigs = $q->getQuery()->getSingleScalarResult();
            $statsEntry->setGigs($newGigs);

            // determine number of published gigs for date range
            $q = $this->em->getRepository('VocalizrAppBundle:Project')
                    ->createQueryBuilder('p')
                    ->select('count(p)')
                    ->where('p.publish_type is not null')
                    ->andWhere('p.published_at >= :start')
                    ->andWhere('p.published_at < :end')
                    ->setParameter('start', $processDate)
                    ->setParameter('end', $endDate);
            $publishedGigs = $q->getQuery()->getSingleScalarResult();
            $statsEntry->setPublishedGigs($publishedGigs);

            // determine number of published public gigs for date range
            $q = $this->em->getRepository('VocalizrAppBundle:Project')
                    ->createQueryBuilder('p')
                    ->select('count(p)')
                    ->where('p.publish_type = :public')
                    ->andWhere('p.published_at >= :start')
                    ->andWhere('p.published_at < :end')
                    ->setParameter('public', 'public')
                    ->setParameter('start', $processDate)
                    ->setParameter('end', $endDate);
            $publishedGigsPublic = $q->getQuery()->getSingleScalarResult();
            $statsEntry->setPublicPublishedGigs($publishedGigsPublic);

            // determine number of published private gigs for date range
            $q = $this->em->getRepository('VocalizrAppBundle:Project')
                    ->createQueryBuilder('p')
                    ->select('count(p)')
                    ->where('p.publish_type = :private')
                    ->andWhere('p.published_at >= :start')
                    ->andWhere('p.published_at < :end')
                    ->setParameter('private', 'private')
                    ->setParameter('start', $processDate)
                    ->setParameter('end', $endDate);
            $publishedGigsPrivate = $q->getQuery()->getSingleScalarResult();
            $statsEntry->setPrivatePublishedGigs($publishedGigsPrivate);

            // determine number of awarded gigs for date range
            $q = $this->em->getRepository('VocalizrAppBundle:Project')
                    ->createQueryBuilder('p')
                    ->select('count(p)')
                    ->where('p.awarded_at is not null')
                    ->andWhere('p.awarded_at >= :start')
                    ->andWhere('p.awarded_at < :end')
                    ->setParameter('start', $processDate)
                    ->setParameter('end', $endDate);
            $awardedGigs = $q->getQuery()->getSingleScalarResult();
            $statsEntry->setAwardedGigs($awardedGigs);

            // determine number of awarded public gigs for date range
            $q = $this->em->getRepository('VocalizrAppBundle:Project')
                    ->createQueryBuilder('p')
                    ->select('count(p)')
                    ->where('p.publish_type = :public')
                    ->andWhere('p.awarded_at is not null')
                    ->andWhere('p.awarded_at >= :start')
                    ->andWhere('p.awarded_at < :end')
                    ->setParameter('public', 'public')
                    ->setParameter('start', $processDate)
                    ->setParameter('end', $endDate);
            $awardedGigsPublic = $q->getQuery()->getSingleScalarResult();
            $statsEntry->setPublicAwardedGigs($awardedGigsPublic);

            // determine number of awarded private gigs for date range
            $q = $this->em->getRepository('VocalizrAppBundle:Project')
                    ->createQueryBuilder('p')
                    ->select('count(p)')
                    ->where('p.publish_type = :private')
                    ->andWhere('p.awarded_at is not null')
                    ->andWhere('p.awarded_at >= :start')
                    ->andWhere('p.awarded_at < :end')
                    ->setParameter('private', 'private')
                    ->setParameter('start', $processDate)
                    ->setParameter('end', $endDate);
            $awardedGigsPrivate = $q->getQuery()->getSingleScalarResult();
            $statsEntry->setPrivateAwardedGigs($awardedGigsPrivate);

            // determine number of completed gigs for date range
            $q = $this->em->getRepository('VocalizrAppBundle:Project')
                    ->createQueryBuilder('p')
                    ->select('count(p)')
                    ->where('p.is_complete = true')
                    ->andWhere('p.completed_at >= :start')
                    ->andWhere('p.completed_at < :end')
                    ->setParameter('start', $processDate)
                    ->setParameter('end', $endDate);
            $completedGigs = $q->getQuery()->getSingleScalarResult();
            $statsEntry->setCompletedGigs($completedGigs);

            // determine revenue for date range
            $q = $this->em->getRepository('VocalizrAppBundle:ProjectEscrow')
                    ->createQueryBuilder('pe')
                    ->select('pe, p')
                    ->innerJoin('pe.project', 'p')
                    ->where('pe.released_date is not null')
                    ->andWhere('pe.released_date >= :start')
                    ->andWhere('pe.released_date < :end')
                    ->setParameter('start', $processDate)
                    ->setParameter('end', $endDate);
            $projectEscrow = $q->getQuery()->execute();
            $revenue       = 0;
            foreach ($projectEscrow as $item) {
                $revenue += $item->getFee();
                $revenue += $item->getProject()->getFees();
                if (!$item->getRefunded()) {
                    if ($item->getContractorFee()) {
                        $revenue += $item->getContractorFee();
                    } else {
                        $revenue += $item->getAmount() * 0.1;
                    }
                }
            }

            // Get fee totals
            $q = $this->em->getRepository('VocalizrAppBundle:Project')
                    ->createQueryBuilder('p')
                    ->select('p')
                    ->where('p.fees > 0')
                    ->andWhere('p.created_at >= :start')
                    ->andWhere('p.created_at < :end')
                    ->setParameter('start', $processDate)
                    ->setParameter('end', $endDate);

            $projectFees = $q->getQuery()->execute();
            foreach ($projectFees as $item) {
                $revenue += $item->getFees();
            }

            // Get engine room completed jobs
            $q = $this->em->getRepository('VocalizrAppBundle:EngineOrder')
                    ->createQueryBuilder('e')
                    ->select('e')
                    ->where("e.fee > 0 AND e.status = 'COMPLETED'")
                    ->andWhere('e.created_at >= :start')
                    ->andWhere('e.created_at < :end')
                    ->setParameter('start', $processDate)
                    ->setParameter('end', $endDate);

            $engineOrders = $q->getQuery()->execute();
            foreach ($engineOrders as $item) {
                $revenue += $item->getFee();
            }

            // Loop through paypal subs
            $q = $this->em->getRepository('VocalizrAppBundle:PayPalTransaction')
                    ->createQueryBuilder('pt')
                    ->select('pt')
                    ->where("pt.txn_type = 'subscr_payment'")
                    ->andWhere('pt.created_at >= :start')
                    ->andWhere('pt.created_at < :end')
                    ->setParameter('start', $processDate)
                    ->setParameter('end', $endDate);

            $ptTransactions = $q->getQuery()->execute();
            foreach ($ptTransactions as $item) {
                $revenue += $item->getAmount();
            }

            // Loop through withdraws, and deduct fees from our revenue
            $q = $this->em->getRepository('VocalizrAppBundle:UserWithdraw')
                    ->createQueryBuilder('w')
                    ->select('w')
                    ->where("w.status = 'COMPLETED' and w.fee > 0")
                    ->andWhere('w.created_at >= :start')
                    ->andWhere('w.created_at < :end')
                    ->setParameter('start', $processDate)
                    ->setParameter('end', $endDate);

            $withdrawFees = $q->getQuery()->execute();
            foreach ($withdrawFees as $item) {
                $revenue -= $item->getFee();
            }

            $statsEntry->setRevenue($revenue);

            // determine number of completed public gigs for date range
            $q = $this->em->getRepository('VocalizrAppBundle:Project')
                    ->createQueryBuilder('p')
                    ->select('count(p)')
                    ->where('p.publish_type = :public')
                    ->andWhere('p.is_complete = true')
                    ->andWhere('p.completed_at >= :start')
                    ->andWhere('p.completed_at < :end')
                    ->setParameter('public', 'public')
                    ->setParameter('start', $processDate)
                    ->setParameter('end', $endDate);
            $completedGigsPublic = $q->getQuery()->getSingleScalarResult();
            $statsEntry->setPublicCompletedGigs($completedGigsPublic);

            // determine number of completed private gigs for date range
            $q = $this->em->getRepository('VocalizrAppBundle:Project')
                    ->createQueryBuilder('p')
                    ->select('count(p)')
                    ->where('p.publish_type = :private')
                    ->andWhere('p.is_complete = true')
                    ->andWhere('p.completed_at >= :start')
                    ->andWhere('p.completed_at < :end')
                    ->setParameter('private', 'private')
                    ->setParameter('start', $processDate)
                    ->setParameter('end', $endDate);
            $completedGigsPrivate = $q->getQuery()->getSingleScalarResult();
            $statsEntry->setPrivateCompletedGigs($completedGigsPrivate);

            // determine number of bids submitted for date range
            $q = $this->em->getRepository('VocalizrAppBundle:ProjectBid')
                    ->createQueryBuilder('pb')
                    ->select('count(pb)')
                    ->where('pb.created_at >= :start')
                    ->andWhere('pb.created_at < :end')
                    ->setParameter('start', $processDate)
                    ->setParameter('end', $endDate);
            $bids = $q->getQuery()->getSingleScalarResult();
            $statsEntry->setBids($bids);

            // determine number of messages sent for date range
            $q = $this->em->getRepository('VocalizrAppBundle:Message')
                    ->createQueryBuilder('m')
                    ->select('count(m)')
                    ->where('m.created_at >= :start')
                    ->andWhere('m.created_at < :end')
                    ->setParameter('start', $processDate)
                    ->setParameter('end', $endDate);
            $messages = $q->getQuery()->getSingleScalarResult();
            $statsEntry->setMessages($messages);

            $this->em->persist($statsEntry);
            $this->em->flush();

            $processDate = $endDate;
        }
    }
}