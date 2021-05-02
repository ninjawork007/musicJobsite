<?php


namespace App\Service;


use Symfony\Component\DependencyInjection\ContainerInterface;
use App\Entity\Counter;
use App\Entity\PaymentSessionData;
use App\Entity\PayPalTransaction;
use App\Entity\Project;
use App\Entity\ProjectBid;
use App\Entity\StripeCharge;
use App\Entity\UserWalletTransaction;

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
        $stripeChargeRepository = $em->getRepository('App:StripeCharge');
        $userWalletTransactionRepository = $em->getRepository('App:UserWalletTransaction');
        $paymentSessionDataRepository = $em->getRepository('App:PaymentSessionData');
        $paypalTransactionRepository = $em->getRepository('App:PayPalTransaction');

        /** @var StripeCharge[] $stripeChargeCurrentMonth */
        $stripeChargeCurrentMonth = $stripeChargeRepository->findStripeChargeForCurrentMonth();
        /** @var UserWalletTransaction[] $commissionsCurrentMonth */
        $commissionsCurrentMonth = $userWalletTransactionRepository->findCommissionsCurrentMonth();
        /** @var PayPalTransaction[] $paypalTransactionsCurrentMonth */
        $paypalTransactionsCurrentMonth = $paypalTransactionRepository->findSubsForCurrentMonth();
        /** @var PaymentSessionData[] $paymentSessionData */
        $paymentSessionData = $paymentSessionDataRepository->findForCurrentMonth();

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

        foreach ($paymentSessionData as $psd) {
            $fee = 0;
            $data = json_decode($psd->getStripeCharge()->getData(), true);
            if (isset($data['data']['object']['balance_transaction'])) {
                $stripeChargeBalanceTransaction = $stripeChargeRepository->findOneBy(['balanceTransaction' => $data['data']['object']['balance_transaction']]);
                if ($stripeChargeBalanceTransaction) {
                    $dataBalanceTransaction = json_decode($stripeChargeBalanceTransaction->getData(), true);
                    $fee = $dataBalanceTransaction['fee'];
                }
            }
            if ($psd->getBid() && $psd->getStripeCharge()) {
                $revenueCurrentMonth['total'] += ($psd->getStripeCharge()->getAmount() - $fee) / 100;
                $revenueCurrentMonth['bid_upgrade'] += ($psd->getStripeCharge()->getAmount() - $fee) / 100;
            }
            if ($psd->getUserCertification() && $psd->getUserCertification()->getValidatedAt() && $psd->getStripeCharge() && $data['data']['object']['calculated_statement_descriptor'] != 'PRO MONTHLY') {
                $revenueCurrentMonth['total'] += ($psd->getStripeCharge()->getAmount() - $fee) / 100;
                $revenueCurrentMonth['cert'] += ($psd->getStripeCharge()->getAmount() - $fee) / 100;
            }
            if ($psd->getProject() && $psd->isContestExtension() && $psd->getStripeCharge()) {
                $revenueCurrentMonth['total'] += ($psd->getStripeCharge()->getAmount() - $fee) / 100;
                $revenueCurrentMonth['contest_ext'] += ($psd->getStripeCharge()->getAmount() - $fee) / 100;
            }
            if ($psd->isConnectionsExtend() && $psd->getStripeCharge()) {
                $revenueCurrentMonth['total'] += ($psd->getStripeCharge()->getAmount() - $fee) / 100;
                $revenueCurrentMonth['connections'] += ($psd->getStripeCharge()->getAmount() - $fee) / 100;
            }
            if ($psd->getProject() && !$psd->isContestExtension() && $psd->getStripeCharge()) {
                $revenueCurrentMonth['total'] += ($psd->getStripeCharge()->getAmount() - $fee) / 100;
                $revenueCurrentMonth['job_upgrade'] += ($psd->getStripeCharge()->getAmount() - $fee) / 100;
            }
        }
        unset($paymentSessionData);

        foreach ($stripeChargeCurrentMonth as $stripeCharge) {
            $data = json_decode($stripeCharge->getData(), true);
            $fee = 0;
            if (isset($data['data']['object']['balance_transaction'])) {
                $stripeChargeBalanceTransaction = $stripeChargeRepository->findOneBy(['balanceTransaction' => $data['data']['object']['balance_transaction']]);
                if ($stripeChargeBalanceTransaction) {
                    $dataBalanceTransaction = json_decode($stripeChargeBalanceTransaction->getData(), true);
                    $fee = $dataBalanceTransaction['fee'];
                }
            }
            if (isset($data['data']['object']['description'])) {
                if ($data['data']['object']['description'] == 'Subscription update') {
                    $psd = $paymentSessionDataRepository->findOneBy(['charge' => $stripeCharge]);
                    if (!$psd) {
                        $revenueCurrentMonth['total'] += ($stripeCharge->getAmount() - $fee) / 100;
                        $revenueCurrentMonth['subs_total'] += ($stripeCharge->getAmount() - $fee) / 100;
                        $revenueCurrentMonth['stripe_subs'] += ($stripeCharge->getAmount() - $fee) / 100;
                    }
                }
            }
        }
        unset($stripeChargeCurrentMonth);

        foreach ($commissionsCurrentMonth as $commission) {
            $data = json_decode($commission->getData(), true);
            $fee = 0;
            if (isset($data['data']['object']['balance_transaction'])) {
                $stripeChargeBalanceTransaction = $stripeChargeRepository->findOneBy(['balanceTransaction' => $data['data']['object']['balance_transaction']]);
                if ($stripeChargeBalanceTransaction) {
                    $dataBalanceTransaction = json_decode($stripeChargeBalanceTransaction->getData(), true);
                    $fee = $dataBalanceTransaction['fee'];
                }
            }
            $revenueCurrentMonth['total'] += ($commission->getAmount() - $fee) / -100;
            $revenueCurrentMonth['commissions'] += ($commission->getAmount() - $fee) / -100;
        }
        unset($commissionsCurrentMonth);

        foreach ($paypalTransactionsCurrentMonth as $paypalTransaction) {
            if ($paypalTransaction->getSubscrId() && $paypalTransaction->getTxnType() == 'subscr_payment') {
                $revenueCurrentMonth['total'] += $paypalTransaction->getAmount();
                $revenueCurrentMonth['subs_total'] += $paypalTransaction->getAmount();
                $revenueCurrentMonth['paypal_subs'] += $paypalTransaction->getAmount();
            }
        }
        unset($paypalTransactionsCurrentMonth);
        $em->clear();

        return $revenueCurrentMonth;
    }

    public function revenueAllTime()
    {
        $em = $this->container->get('doctrine.orm.entity_manager');

        $stripeChargeRepository = $em->getRepository('App:StripeCharge');
        $userWalletTransactionRepository = $em->getRepository('App:UserWalletTransaction');
        $paypalTransactionRepository = $em->getRepository('App:PayPalTransaction');
        $paymentSessionDataRepository = $em->getRepository('App:PaymentSessionData');

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

        /** @var PaymentSessionData[] $paymentSessionData */
        $paymentSessionData = $paymentSessionDataRepository->findAll();
        foreach ($paymentSessionData as $psd) {
            if ($psd->getStripeCharge()) {
                unset($data);
                $data = json_decode($psd->getStripeCharge()->getData(), true);
                $fee = 0;
                if (isset($data['data']['object']['balance_transaction'])) {
                    $stripeChargeBalanceTransaction = $stripeChargeRepository->findOneBy(['balanceTransaction' => $data['data']['object']['balance_transaction']]);
                    if ($stripeChargeBalanceTransaction) {
                        $dataBalanceTransaction = json_decode($stripeChargeBalanceTransaction->getData(), true);
                        $fee = $dataBalanceTransaction['fee'];
                    }
                }
            }
            if ($psd->getUserCertification() && $psd->getUserCertification()->getValidatedAt() && $psd->getStripeCharge() && $data['data']['object']['calculated_statement_descriptor'] != 'PRO MONTHLY') {
                if (isset($revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')])) {
                    $revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')]['total'] += ($psd->getStripeCharge()->getAmount() - $fee) / 100;
                    $revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')]['cert'] += ($psd->getStripeCharge()->getAmount() - $fee) / 100;
                    $revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')]['date'] = $psd->getStripeCharge()->getCreatedAt()->format('m/Y');
                } else {
                    $revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')] = $revenue;
                    $revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')]['total'] += ($psd->getStripeCharge()->getAmount() - $fee) / 100;
                    $revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')]['cert'] += ($psd->getStripeCharge()->getAmount() - $fee) / 100;
                    $revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')]['date'] = $psd->getStripeCharge()->getCreatedAt()->format('m/Y');
                }
            }
            if ($psd->getBid() && $psd->getStripeCharge()) {
                if (isset($revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')])) {
                    $revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')]['total'] += ($psd->getStripeCharge()->getAmount() - $fee) / 100;
                    $revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')]['bid_upgrade'] += ($psd->getStripeCharge()->getAmount() - $fee) / 100;
                    $revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')]['date'] = $psd->getStripeCharge()->getCreatedAt()->format('m/Y');
                } else {
                    $revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')] = $revenue;
                    $revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')]['total'] += ($psd->getStripeCharge()->getAmount() - $fee) / 100;
                    $revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')]['bid_upgrade'] += ($psd->getStripeCharge()->getAmount() - $fee) / 100;
                    $revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')]['date'] = $psd->getStripeCharge()->getCreatedAt()->format('m/Y');
                }
            }
            if ($psd->getProject() && $psd->isContestExtension() && $psd->getStripeCharge()) {
                if (isset($revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')])) {
                    $revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')]['total'] += ($psd->getStripeCharge()->getAmount() - $fee) / 100;
                    $revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')]['date'] = $psd->getStripeCharge()->getCreatedAt()->format('m/Y');
                } else {
                    $revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')] = $revenue;
                    $revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')]['total'] += ($psd->getStripeCharge()->getAmount() - $fee) / 100;
                    $revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')]['date'] = $psd->getStripeCharge()->getCreatedAt()->format('m/Y');
                }
            }
            if ($psd->getProject() && !$psd->isContestExtension() && $psd->getStripeCharge()) {
                if (isset($revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')])) {
                    $revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')]['total'] += ($psd->getStripeCharge()->getAmount() - $fee) / 100;
                    $revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')]['job_upgrade'] += ($psd->getStripeCharge()->getAmount() - $fee) / 100;
                    $revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')]['date'] = $psd->getStripeCharge()->getCreatedAt()->format('m/Y');
                } else {
                    $revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')] = $revenue;
                    $revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')]['total'] += ($psd->getStripeCharge()->getAmount() - $fee) / 100;
                    $revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')]['job_upgrade'] += ($psd->getStripeCharge()->getAmount() - $fee) / 100;
                    $revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')]['date'] = $psd->getStripeCharge()->getCreatedAt()->format('m/Y');
                }
            }
            if ($psd->isConnectionsExtend() && $psd->getStripeCharge()) {
                if (isset($revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')])) {
                    $revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')]['total'] += ($psd->getStripeCharge()->getAmount() - $fee) / 100;
                    $revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')]['connections'] += ($psd->getStripeCharge()->getAmount() - $fee) / 100;
                    $revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')]['date'] = $psd->getStripeCharge()->getCreatedAt()->format('m/Y');
                } else {
                    $revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')] = $revenue;
                    $revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')]['total'] += ($psd->getStripeCharge()->getAmount() - $fee) / 100;
                    $revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')]['connections'] += ($psd->getStripeCharge()->getAmount() - $fee) / 100;
                    $revenueAllTime[$psd->getStripeCharge()->getCreatedAt()->format('Y/m')]['date'] = $psd->getStripeCharge()->getCreatedAt()->format('m/Y');
                }
            }
        }
        unset($paymentSessionData);
        $em->clear();

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
        $em->clear();

        /** @var StripeCharge[] $stripeChargeAllTime */
        $stripeChargeAllTime = $stripeChargeRepository->findStripeChargeForAllTime();
        foreach ($stripeChargeAllTime as $stripeCharge) {
            $em->clear();
            unset($data);
            unset($stripeChargeBalanceTransaction);
            unset($dataBalanceTransaction);
            unset($psd);
            $data = json_decode($stripeCharge->getData(), true);
            $fee = 0;
            if (isset($data['data']['object']['balance_transaction'])) {
                $stripeChargeBalanceTransaction = $stripeChargeRepository->findOneBy(['balanceTransaction' => $data['data']['object']['balance_transaction']]);
                if ($stripeChargeBalanceTransaction) {
                    $dataBalanceTransaction = json_decode($stripeChargeBalanceTransaction->getData(), true);
                    $fee = $dataBalanceTransaction['fee'];
                }
            }
            if (isset($revenueAllTime[$stripeCharge->getCreatedAt()->format('Y/m')])) {
                if (isset($data['data']['object']['description'])) {
                    if ($data['data']['object']['description'] == 'Subscription update') {
                        /** @var PaymentSessionData $psd */
                        $psd = $paymentSessionDataRepository->findOneBy(['charge' => $stripeCharge]);
                        if (!$psd) {
                            $revenueAllTime[$stripeCharge->getCreatedAt()->format('Y/m')]['total'] += ($stripeCharge->getAmount() - $fee) / 100;
                            $revenueAllTime[$stripeCharge->getCreatedAt()->format('Y/m')]['subs_total'] += ($stripeCharge->getAmount() - $fee) / 100;
                            $revenueAllTime[$stripeCharge->getCreatedAt()->format('Y/m')]['date'] = $stripeCharge->getCreatedAt()->format('m/Y');
                        }
                    }
                }
            } else {
                $revenueAllTime[$stripeCharge->getCreatedAt()->format('Y/m')] = $revenue;
                $revenueAllTime[$stripeCharge->getCreatedAt()->format('Y/m')]['total'] += ($stripeCharge->getAmount() - $fee) / 100;
                $revenueAllTime[$stripeCharge->getCreatedAt()->format('Y/m')]['subs_total'] += ($stripeCharge->getAmount() - $fee) / 100;
                $revenueAllTime[$stripeCharge->getCreatedAt()->format('Y/m')]['date'] = $stripeCharge->getCreatedAt()->format('m/Y');
            }

        }
        unset($stripeChargeAllTime);
        $em->clear();
        foreach ($paypalTransactionsAllTime as $paypalTransaction) {
            if (isset($revenueAllTime[$paypalTransaction->getCreatedAt()->format('Y/m')])) {
                if ($paypalTransaction->getSubscrId() && $paypalTransaction->getTxnType() == 'subscr_payment') {
                    $revenueAllTime[$paypalTransaction->getCreatedAt()->format('Y/m')]['total'] += $paypalTransaction->getAmount();
                    $revenueAllTime[$paypalTransaction->getCreatedAt()->format('Y/m')]['subs_total'] += $paypalTransaction->getAmount();
                    $revenueAllTime[$paypalTransaction->getCreatedAt()->format('Y/m')]['date'] = $paypalTransaction->getCreatedAt()->format('m/Y');
                }
            } else {
                $revenueAllTime[$paypalTransaction->getCreatedAt()->format('Y/m')] = $revenue;
                $revenueAllTime[$paypalTransaction->getCreatedAt()->format('Y/m')]['total'] += $paypalTransaction->getAmount();
                $revenueAllTime[$paypalTransaction->getCreatedAt()->format('Y/m')]['subs_total'] += $paypalTransaction->getAmount();
                $revenueAllTime[$paypalTransaction->getCreatedAt()->format('Y/m')]['date'] = $paypalTransaction->getCreatedAt()->format('m/Y');
            }
        }
        unset($paypalTransactionsAllTime);

        krsort($revenueAllTime);

        return $revenueAllTime;
    }

}