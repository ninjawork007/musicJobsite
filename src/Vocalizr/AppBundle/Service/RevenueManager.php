<?php


namespace Vocalizr\AppBundle\Service;


use Symfony\Component\DependencyInjection\ContainerInterface;
use Vocalizr\AppBundle\Entity\Counter;
use Vocalizr\AppBundle\Entity\PaymentSessionData;
use Vocalizr\AppBundle\Entity\PayPalTransaction;
use Vocalizr\AppBundle\Entity\Project;
use Vocalizr\AppBundle\Entity\ProjectBid;
use Vocalizr\AppBundle\Entity\Revenue\StripeInvoice;
use Vocalizr\AppBundle\Entity\Revenue\StripeProductInvoice;
use Vocalizr\AppBundle\Entity\StripeCharge;
use Vocalizr\AppBundle\Entity\UserWalletTransaction;
use Vocalizr\AppBundle\Repository\StripeInvoiceRepository;
use Vocalizr\AppBundle\Repository\StripeProductInvoiceRepository;

class RevenueManager
{

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function revenueCurrentMonth()
    {
        $em = $this->container->get('doctrine.orm.entity_manager');
        $userWalletTransactionRepository = $em->getRepository('VocalizrAppBundle:UserWalletTransaction');
        $projectRepository = $em->getRepository('VocalizrAppBundle:Project');
        $paypalTransactionRepository = $em->getRepository('VocalizrAppBundle:PayPalTransaction');
        $stripeChargeRepository = $em->getRepository('VocalizrAppBundle:StripeCharge');
        /** @var StripeProductInvoiceRepository $stripeProductInvoiceRepository */
        $stripeProductInvoiceRepository = $em->getRepository('VocalizrAppBundle:Revenue\StripeProductInvoice');
        $stripeInvoiceRepository = $em->getRepository('VocalizrAppBundle:Revenue\StripeInvoice');

        /** @var UserWalletTransaction[] $commissionsCurrentMonth */
        $commissionsCurrentMonth = $userWalletTransactionRepository->findCommissionsCurrentMonth();
        /** @var UserWalletTransaction[] $jobUpgradesCurrentMonth */
        $jobUpgradesCurrentMonth = $userWalletTransactionRepository->findJobUpgradesCurrentMonth();
        /** @var PayPalTransaction[] $paypalTransactionsCurrentMonth */
        $paypalTransactionsCurrentMonth = $paypalTransactionRepository->findSubsForCurrentMonth();
//        /** @var StripeInvoice[] $bidUpgrades */
//        $bidUpgrades = $stripeInvoiceRepository->findInvoicesForCurrentMonthBidUpgrades();
//        /** @var StripeInvoice[] $bidUpgrades */
//        $bidUpgrades = $stripeInvoiceRepository->findInvoicesForCurrentMonthBidUpgrades();
//        /** @var StripeInvoice[] $extendConnectionsLimit */
//        $extendConnectionsLimit = $stripeInvoiceRepository->findInvoicesForCurrentMonthConnectionLimits();
        /** @var StripeInvoice[] $certs */
        $certs = $stripeInvoiceRepository->findInvoicesForCurrentMonthCerts();
//        /** @var StripeInvoice[] $contestExtensions */
//        $contestExtensions = $stripeInvoiceRepository->findInvoicesForCurrentExtendContest();
        /** @var StripeProductInvoice[] $jobUpgrades */
        $jobUpgrades = $stripeProductInvoiceRepository->findInvoicesForCurrentMonthJobUpgrade();
        /** @var StripeProductInvoice[] $subscriptions */
        $subscriptions = $stripeProductInvoiceRepository->findInvoicesForCurrentMonthSubscriptions();

        unset($userWalletTransactionRepository);
        unset($paypalTransactionRepository);

        $revenueCurrentMonth = [
            'total' => 0,
            'subs_total' => 0,
            'stripe_subs' => 0,
            'paypal_subs' => 0,
            'commissions' => 0,
            'job_upgrade' => 0,
            'bid_upgrade' => 0,
            'cert' => 0,
            'connections' => 0,
            'contest_ext' => 0
        ];

//        foreach ($extendConnectionsLimit as $extendConnectionLimit) {
//            $revenueCurrentMonth['connections'] += ($extendConnectionLimit->getAmount() - $extendConnectionLimit->getFee()) / 100;
//        }
//        foreach ($bidUpgrades as $bidUpgrade) {
//            $revenueCurrentMonth['bid_upgrade'] += ($bidUpgrade->getAmount() - $bidUpgrade->getFee()) / 100;
//        }
        foreach ($certs as $cert) {
            $revenueCurrentMonth['cert'] += ($cert->getAmount() - $cert->getFee()) / 100;
        }
//        foreach ($contestExtensions as $contestExtension) {
//            $revenueCurrentMonth['contest_ext'] += ($contestExtension->getAmount() - $contestExtension->getFee()) / 100;
//        }
        foreach ($subscriptions as $subscription) {
            if($subscription->getAmount() == $subscription->getStripeInvoice()->getAmount()) {
                $revenueCurrentMonth['stripe_subs'] += ($subscription->getAmount() - $subscription->getStripeInvoice()->getFee()) / 100;
            } else {
                $productPercent = $subscription->getAmount() / ($subscription->getStripeInvoice()->getAmount() / 100);
                $fee = ($subscription->getStripeInvoice()->getFee() / 100) * $productPercent;
                $revenueCurrentMonth['stripe_subs'] += ($subscription->getAmount() - $fee) / 100;

            }
        }
//        foreach ($jobUpgrades as $jobUpgrade) {
//            $productPercent = $jobUpgrade->getAmount() / ($jobUpgrade->getStripeInvoice()->getAmount() / 100);
//            $fee = ($jobUpgrade->getStripeInvoice()->getFee() / 100) * $productPercent;
//            $revenueCurrentMonth['job_upgrade'] += ($jobUpgrade->getAmount() - $fee) / 100;
//
//        }
        foreach ($jobUpgradesCurrentMonth as $jobUpgrade) {
            $data = json_decode($jobUpgrade->getData(), true);
            $fee = 0;
            if (isset($data['data']['object']['balance_transaction'])) {
                $stripeChargeBalanceTransaction = $stripeChargeRepository->findOneBy(['balanceTransaction' => $data['data']['object']['balance_transaction']]);
                if ($stripeChargeBalanceTransaction) {
                    $dataBalanceTransaction = json_decode($stripeChargeBalanceTransaction->getData(), true);
                    $fee = $dataBalanceTransaction['fee'];
                }
            }
            $revenueCurrentMonth['job_upgrade'] += ($jobUpgrade->getAmount() - $fee) / -100;
        }

        foreach ($commissionsCurrentMonth as $commission) {
            $data = json_decode($commission->getData(), true);
            $fee = 0;
            if (isset($data['projectUuid']) && strripos($commission->getDescription(), 'contest') !== false) {
                $project = $projectRepository->findOneBy(['uuid' => $data['projectUuid']]);
                if ($project->isFullyRefunded()) {
                    continue;
                }
            }
            if (isset($data['data']['object']['balance_transaction'])) {
                $stripeChargeBalanceTransaction = $stripeChargeRepository->findOneBy(['balanceTransaction' => $data['data']['object']['balance_transaction']]);
                if ($stripeChargeBalanceTransaction) {
                    $dataBalanceTransaction = json_decode($stripeChargeBalanceTransaction->getData(), true);
                    $fee = $dataBalanceTransaction['fee'];
                }
            }
            $revenueCurrentMonth['commissions'] += ($commission->getAmount() - $fee) / -100;
        }
        unset($commissionsCurrentMonth);

        foreach ($paypalTransactionsCurrentMonth as $paypalTransaction) {
            if ($paypalTransaction->getSubscrId() && $paypalTransaction->getTxnType() == 'subscr_payment' && $paypalTransaction->getVerified()) {
                $revenueCurrentMonth['paypal_subs'] += $paypalTransaction->getAmount();
            }
        }
        unset($paypalTransactionsCurrentMonth);
        $em->clear();
        $revenueCurrentMonth['subs_total'] = $revenueCurrentMonth['paypal_subs'] + $revenueCurrentMonth['stripe_subs'];
        $revenueCurrentMonth['total'] =
            $revenueCurrentMonth['commissions']
            + $revenueCurrentMonth['subs_total']
            + $revenueCurrentMonth['job_upgrade']
            + $revenueCurrentMonth['connections']
            + $revenueCurrentMonth['contest_ext']
            + $revenueCurrentMonth['cert']
            + $revenueCurrentMonth['bid_upgrade']
        ;

        return $revenueCurrentMonth;
    }

    public function revenueAllTime()
    {
        $em = $this->container->get('doctrine.orm.entity_manager');

        $stripeChargeRepository = $em->getRepository('VocalizrAppBundle:StripeCharge');
        $projectRepository = $em->getRepository('VocalizrAppBundle:Project');
        $userWalletTransactionRepository = $em->getRepository('VocalizrAppBundle:UserWalletTransaction');
        $paypalTransactionRepository = $em->getRepository('VocalizrAppBundle:PayPalTransaction');
        /** @var StripeInvoiceRepository $stripeInvoiceRepository */
        $stripeInvoiceRepository = $em->getRepository('VocalizrAppBundle:Revenue\StripeInvoice');
        /** @var StripeProductInvoiceRepository $stripeProductInvoiceRepository */
        $stripeProductInvoiceRepository = $em->getRepository('VocalizrAppBundle:Revenue\StripeProductInvoice');

        /** @var PayPalTransaction[] $paypalTransactionsAllTime */
        $paypalTransactionsAllTime = $paypalTransactionRepository->findAll();
        $em->clear();

        $revenue = [
            'total' => 0,
            'subs_total' => 0,
            'commissions' => 0,
            'job_upgrade' => 0,
            'bid_upgrade' => 0,
            'cert' => 0,
            'connections' => 0
        ];
        $revenueAllTime = [];
//        /** @var StripeInvoice[] $bidUpgrades */
//        $bidUpgrades = $stripeInvoiceRepository->findInvoicesForAllTimeBidUpgrades();
//        $revenueAllTime = $this->setDataColumn($bidUpgrades, $revenueAllTime, 'bid_upgrade');
//        unset($bidUpgrades);

//        /** @var StripeProductInvoice[] $jobUpgrades */
//        $jobUpgrades = $stripeProductInvoiceRepository->findInvoicesForAllTimeJobUpgrade();
//        foreach ($jobUpgrades as $jobUpgrade) {
//            $productPercent = $jobUpgrade->getAmount() / ($jobUpgrade->getStripeInvoice()->getAmount() / 100);
//            $fee = ($jobUpgrade->getStripeInvoice()->getFee() / 100) * $productPercent;
//            $amount = ($jobUpgrade->getAmount() - $fee) / 100;
//            $dateYm = $jobUpgrade->getStripeInvoice()->getDateCreateInvoice()->format('Y/m');
//            $dateMY = $jobUpgrade->getStripeInvoice()->getDateCreateInvoice()->format('m/Y');
//            if (isset($revenueAllTime[$dateYm])) {
//                $revenueAllTime[$dateYm]['total'] += $amount;
//                $revenueAllTime[$dateYm]['job_upgrade'] += $amount;
//                $revenueAllTime[$dateYm]['date'] = $dateMY;
//            } else {
//                $revenueAllTime[$dateYm] = $revenue;
//                $revenueAllTime[$dateYm]['total'] += $amount;
//                $revenueAllTime[$dateYm]['job_upgrade'] += $amount;
//                $revenueAllTime[$dateYm]['date'] = $dateMY;
//            }
//        }
//        unset($jobUpgrades);

        /** @var StripeProductInvoice[] $subscriptions */
        $subscriptions = $stripeProductInvoiceRepository->findInvoicesForAllTimeSubscriptions();
        foreach ($subscriptions as $subscription) {
            $dateYm = $subscription->getStripeInvoice()->getDateCreateInvoice()->format('Y/m');
            $dateMY = $subscription->getStripeInvoice()->getDateCreateInvoice()->format('m/Y');
            if($subscription->getAmount() == $subscription->getStripeInvoice()->getAmount()) {
                $amount = ($subscription->getAmount() - $subscription->getStripeInvoice()->getFee()) / 100;
            } else {
                $productPercent = $subscription->getAmount() / ($subscription->getStripeInvoice()->getAmount() / 100);
                $fee = ($subscription->getStripeInvoice()->getFee() / 100) * $productPercent;
                $amount = ($subscription->getAmount() - $fee) / 100;

            }
            if (isset($revenueAllTime[$dateYm])) {
                $revenueAllTime[$dateYm]['total'] += $amount;
                $revenueAllTime[$dateYm]['subs_total'] += $amount;
                $revenueAllTime[$dateYm]['date'] = $dateMY;
            } else {
                $revenueAllTime[$dateYm] = $revenue;
                $revenueAllTime[$dateYm]['total'] += $amount;
                $revenueAllTime[$dateYm]['subs_total'] += $amount;
                $revenueAllTime[$dateYm]['date'] = $dateMY;
            }
        }
        unset($subscriptions);

        /** @var StripeInvoice[] $certs */
        $certs = $stripeInvoiceRepository->findInvoicesForAllTimeCerts();
        $revenueAllTime = $this->setDataColumn($certs, $revenueAllTime, 'cert');
        unset($certs);

//        /** @var StripeInvoice[] $extendConnectionsLimit */
//        $extendConnectionsLimit = $stripeInvoiceRepository->findInvoicesForAllTimeConnectionsLimit();
//        $revenueAllTime = $this->setDataColumn($extendConnectionsLimit, $revenueAllTime, 'connections');
//        unset($extendConnectionsLimit);

//        /** @var StripeInvoice[] $contestExtensions */
//        $contestExtensions = $stripeInvoiceRepository->findInvoicesForCurrentExtendContest();
//        $revenueAllTime = $this->setDataColumn($contestExtensions, $revenueAllTime);
//        unset($contestExtensions);

        /** @var UserWalletTransaction[] $commissionsAllTime */
        $commissionsAllTime = $userWalletTransactionRepository->findCommissionsAllTime();
        foreach ($commissionsAllTime as $commission) {
            unset($data);
            $data = json_decode($commission->getData(), true);
            $fee = 0;
            if (isset($data['data']['object']['balance_transaction'])) {
                $stripeChargeBalanceTransaction = $stripeChargeRepository->findOneBy(['balanceTransaction' => $data['data']['object']['balance_transaction']]);
                if ($stripeChargeBalanceTransaction) {
                    $dataBalanceTransaction = json_decode($stripeChargeBalanceTransaction->getData(), true);
                    $fee = $dataBalanceTransaction['fee'];
                }
            }
            if (isset($data['projectUuid']) && strripos($commission->getDescription(), 'contest') !== false) {
                $project = $projectRepository->findOneBy(['uuid' => $data['projectUuid']]);
                if ($project->isFullyRefunded()) {
                    continue;
                }
            }
            if (isset($revenueAllTime[$commission->getCreatedAt()->format('Y/m')])) {
                $revenueAllTime[$commission->getCreatedAt()->format('Y/m')]['total'] += ($commission->getAmount() - $fee) / -100;
                $revenueAllTime[$commission->getCreatedAt()->format('Y/m')]['commissions'] += ($commission->getAmount() - $fee) / -100;
                $revenueAllTime[$commission->getCreatedAt()->format('Y/m')]['date'] = $commission->getCreatedAt()->format('m/Y');
            } else {
                $revenueAllTime[$commission->getCreatedAt()->format('Y/m')] = $revenue;
                $revenueAllTime[$commission->getCreatedAt()->format('Y/m')]['total'] += ($commission->getAmount() - $fee) / -100;
                $revenueAllTime[$commission->getCreatedAt()->format('Y/m')]['commissions'] += ($commission->getAmount() - $fee) / -100;
                $revenueAllTime[$commission->getCreatedAt()->format('Y/m')]['date'] = $commission->getCreatedAt()->format('m/Y');
            }
        }
        unset($commissionsAllTime);

        /** @var UserWalletTransaction[] $jobUpgradesAllTime */
        $jobUpgradesAllTime = $userWalletTransactionRepository->findJobUpgradesAllTime();
        foreach ($jobUpgradesAllTime as $jobUpgarde) {
            unset($data);
            $dateYm = $jobUpgarde->getCreatedAt()->format('Y/m');
            $dateMY = $jobUpgarde->getCreatedAt()->format('m/Y');
            $data = json_decode($jobUpgarde->getData(), true);
            $fee = 0;
            if (isset($revenueAllTime[$dateYm])) {
                $revenueAllTime[$dateYm]['total'] += ($jobUpgarde->getAmount() - $fee) / -100;
                $revenueAllTime[$dateYm]['job_upgrade'] += ($jobUpgarde->getAmount() - $fee) / -100;
                $revenueAllTime[$dateYm]['date'] = $dateMY;
            } else {
                $revenueAllTime[$dateYm] = $revenue;
                $revenueAllTime[$dateYm]['total'] += ($jobUpgarde->getAmount() - $fee) / -100;
                $revenueAllTime[$dateYm]['job_upgrade'] += ($jobUpgarde->getAmount() - $fee) / -100;
                $revenueAllTime[$dateYm]['date'] = $dateMY;
            }
        }
        unset($jobUpgradesAllTime);
        $em->clear();


        foreach ($paypalTransactionsAllTime as $paypalTransaction) {
            if (isset($revenueAllTime[$paypalTransaction->getCreatedAt()->format('Y/m')])) {
                if ($paypalTransaction->getSubscrId() && $paypalTransaction->getTxnType() == 'subscr_payment' && $paypalTransaction->getVerified()) {
                    $revenueAllTime[$paypalTransaction->getCreatedAt()->format('Y/m')]['total'] += $paypalTransaction->getAmount();
                    $revenueAllTime[$paypalTransaction->getCreatedAt()->format('Y/m')]['subs_total'] += $paypalTransaction->getAmount();
                    $revenueAllTime[$paypalTransaction->getCreatedAt()->format('Y/m')]['date'] = $paypalTransaction->getCreatedAt()->format('m/Y');
                }
            } else {
                if ($paypalTransaction->getSubscrId() && $paypalTransaction->getTxnType() == 'subscr_payment' && $paypalTransaction->getVerified()) {
                    $revenueAllTime[$paypalTransaction->getCreatedAt()->format('Y/m')] = $revenue;
                    $revenueAllTime[$paypalTransaction->getCreatedAt()->format('Y/m')]['total'] += $paypalTransaction->getAmount();
                    $revenueAllTime[$paypalTransaction->getCreatedAt()->format('Y/m')]['subs_total'] += $paypalTransaction->getAmount();
                    $revenueAllTime[$paypalTransaction->getCreatedAt()->format('Y/m')]['date'] = $paypalTransaction->getCreatedAt()->format('m/Y');
                }
            }
        }
        unset($paypalTransactionsAllTime);

        krsort($revenueAllTime);

        return $revenueAllTime;
    }

    private function setDataColumn($stripeInvoices, $revenueAllTime, $column = null)
    {
        $revenue = [
            'total' => 0,
            'subs_total' => 0,
            'commissions' => 0,
            'job_upgrade' => 0,
            'bid_upgrade' => 0,
            'cert' => 0,
            'connections' => 0
        ];
        foreach ($stripeInvoices as $stripeInvoice) {
            $dateYm = $stripeInvoice->getDateCreateInvoice()->format('Y/m');
            $dateMY = $stripeInvoice->getDateCreateInvoice()->format('m/Y');
            $amount = ($stripeInvoice->getAmount() - $stripeInvoice->getFee()) / 100;
            if (isset($revenueAllTime[$dateYm])) {
                $revenueAllTime[$dateYm]['total'] += $amount;
                if ($column) {
                    $revenueAllTime[$dateYm][$column] += $amount;
                }
                $revenueAllTime[$dateYm]['date'] = $dateMY;
            } else {
                $revenueAllTime[$dateYm] = $revenue;
                $revenueAllTime[$dateYm]['total'] += $amount;
                if ($column) {
                    $revenueAllTime[$dateYm][$column] += $amount;
                }
                $revenueAllTime[$dateYm]['date'] = $dateMY;
            }
        }

        return $revenueAllTime;
    }

}