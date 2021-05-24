<?php

namespace Vocalizr\AppBundle\Service;

use Exception;
use PayPal\Api\Currency;
use PayPal\Api\Payout;
use PayPal\Api\PayoutBatch;
use PayPal\Api\PayoutItem;
use PayPal\Api\PayoutSenderBatchHeader;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Vocalizr\AppBundle\Entity\UserSubscription;
use Vocalizr\AppBundle\Entity\UserWithdraw;

/**
 * Class PayPalApiService
 *
 * @package Vocalizr\PayPalBundle\Service
 */
class PayPalApiService
{
    const PAYOUT_STATUS_PROCESSING = 'PROCESSING';

    const PAYOUT_STATUS_REQUESTED = 'REQUESTED';

    const PAYOUT_STATUS_UNCLAIMED = 'UNCLAIMED';

    const PAYOUT_STATUS_CANCELED = 'CANCELLED';

    const PAYOUT_STATUS_REFUNDED = 'REFUNDED';

    const PAYOUT_STATUS_RETURNED = 'RETURNED';

    const PAYOUT_STATUS_PENDING = 'PENDING';

    const PAYOUT_STATUS_SUCCESS = 'SUCCESS';

    const PAYOUT_STATUS_BLOCKED = 'BLOCKED';

    const PAYOUT_STATUS_DENIED = 'DENIED';

    const PAYOUT_STATUS_FAILED = 'FAILED';

    const PAYOUT_STATUS_ONHOLD = 'ONHOLD';

    const PAYOUT_STATUS_NEW = 'NEW';

    private $apiContext;

    /** @var OutputInterface|null */
    private $output;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var string
     */
    private $env;

    /**
     * PayPalApiService constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->env = $container->getParameter('paypal_env');
        $this->createApiContext($container->getParameter('paypal_client_id'), $container->getParameter('paypal_client_secret'));
        $this->container = $container;
    }

    /**
     * @param string $clientId
     * @param string $clientSecret
     */
    public function createApiContext($clientId, $clientSecret)
    {
        $this->apiContext = new ApiContext(
            new OAuthTokenCredential(
                $clientId,
                $clientSecret
            )
        );

        if ($this->env == 'live') {
            $this->apiContext->setConfig(
                [
                    'mode' => 'live',
                ]
            );
        }
    }

    /**
     * @param UserSubscription $subscription
     */
    public function cancelSubscription(UserSubscription $subscription)
    {
        $parametersPostfix = '_subscriptions';
        if ($subscription->getPaypalAccount() !== $this->container->getParameter('paypal_primary_email_subscriptions')) {
            $parametersPostfix = '';
        }

        return $this->legacyApiCall('ManageRecurringPaymentsProfileStatus', [
            'ACTION'    => 'Cancel',
            'PROFILEID' => $subscription->getPaypalSubscrId(),
        ], $parametersPostfix);
    }

    /**
     * @param string $transactionId
     * @param string $note
     */
    public function refundTransaction($transactionId, $note = '')
    {
        $this->legacyApiCall('RefundTransaction', [
            'TRANSACTIONID' => $transactionId,
            'REFUNDTYPE'    => 'Full',
            'NOTE'          => $note,
        ]);
    }

    /**
     * @param string $paypalEmail
     * @param int $amount
     */
    public function refundForContest($transactionId, $amount)
    {
        $this->legacyApiCall('RefundTransaction', [
            'TRANSACTIONID' => $transactionId,
            'REFUNDTYPE'    => 'Partial',
            'AMT'           => $amount,
        ]);
    }

    /**
     * @param UserWithdraw[] $withdraws
     *
     * @return PayoutBatch
     *
     * @throws Exception
     */
    public function withdraw($withdraws)
    {
        $payout      = new Payout();
        $batchHeader = new PayoutSenderBatchHeader();

        $batchHeader
            ->setSenderBatchId(uniqid())
            ->setEmailSubject('Vocalizr payout')
        ;

        $payout->setSenderBatchHeader($batchHeader);

        foreach ($withdraws as $withdraw) {
            if ($withdraw->getAmount() > (10000 * 100)) {
                $withdraw
                    ->setStatus(UserWithdraw::WITHDRAW_STATUS_ERROR)
                    ->setStatusReason('Single transaction amount is limited to $10000')
                ;
                continue;
            }
            $payout->addItem($this->payoutItemFromWithdraw($withdraw));
        }

        $result = $payout->create(null, $this->apiContext);

        return $result;
    }

    /**
     * @param UserWithdraw $withdraw
     *
     * @return PayoutItem
     */
    public function payoutItemFromWithdraw($withdraw)
    {
        $amount = new Currency();
        $item   = new PayoutItem();

        $amount
            ->setValue((float) $withdraw->getAmount() / (float) 100)
            ->setCurrency('USD')
        ;

        $item
            ->setRecipientType('Email')
            ->setNote('You have successfully withdrawn money from your Vocalizr wallet!')
            ->setReceiver($withdraw->getPaypalEmail())
            ->setAmount($amount)
            ->setSenderItemId($this->getSenderItemId($withdraw));

        return $item;
    }

    /**
     * @param UserWithdraw $withdraw
     *
     * @return bool
     */
    public function cancelPayout(UserWithdraw $withdraw)
    {
        try {
            // If api call was not triggered yet just set withdraw status cancelled.
            if ($withdraw->getStatus() === UserWithdraw::WITHDRAW_STATUS_PENDING) {
                $withdraw->setStatus(UserWithdraw::WITHDRAW_STATUS_CANCELLED);
                return true;
            }

            $testStatusWithdraw = clone $withdraw;

            $this->updatePayoutStatuses([$testStatusWithdraw], false);

            if ($testStatusWithdraw->getPaypalStatus() === PayPalApiService::PAYOUT_STATUS_UNCLAIMED) {
                PayoutItem::cancel($withdraw->getPaypalItemId(), $this->apiContext);
                $this->updatePayoutStatuses([$withdraw]);
                return true;
            } elseif ($testStatusWithdraw->getPaypalStatus() === PayPalApiService::PAYOUT_STATUS_SUCCESS) {
                // We cannot cancel finished transactions.
                $this->updatePayoutStatuses([$withdraw]);
                return false;
            } else {
                $this->updatePayoutStatuses([$withdraw]);
            }
        } catch (Exception $exception) {
            $message = 'Exception occurred while trying to cancel payout. Message: ' . $exception->getMessage();
            error_log($message);
            $withdraw->setStatusReason($message);
            $withdraw->setStatus(UserWithdraw::WITHDRAW_STATUS_ERROR);
        }

        $withdraw->setStatusReason('Only payouts with status UNCLAMED can be cancelled in PayPal. Current status is ' . $withdraw->getPaypalStatus());
        return false;
    }

    /**
     * @param UserWithdraw[] $withdrawsInBatch
     * @param bool $createTransaction
     */
    public function updatePayoutStatuses($withdrawsInBatch, $createTransaction = true)
    {
        /** @var UserWithdraw[][] $batchSortedWithdraws */
        $batchSortedWithdraws = [];

        foreach ($withdrawsInBatch as $withdraw) {
            $batchSortedWithdraws[$withdraw->getPaypalBatchId()][] = $withdraw;
        }

        foreach ($batchSortedWithdraws as $batchId => $withdrawsInBatch) {
            if (!$batchId) {
                $this->out('Batch id not found. Couldn\'t update status for withdraw.');
                continue;
            }
            $payoutBatch = Payout::get($batchId, $this->apiContext);

            $this->payoutToWithdraws($withdrawsInBatch, $payoutBatch, $createTransaction);
        }
    }

    /**
     * @param UserWithdraw[] $withdraws
     * @param PayoutBatch $payout
     * @param bool $createTransaction
     */
    public function payoutToWithdraws($withdraws, PayoutBatch $payout, $createTransaction = true)
    {
        /** @var UserWithdraw[] $withdrawsByItemId */
        $withdrawsByItemId = [];

        foreach ($withdraws as $withdraw) {
            $withdrawsByItemId[$this->getSenderItemId($withdraw)] = $withdraw;
        }

        foreach ($payout->getItems() as $userPayout) {
            $item = $userPayout->getPayoutItem();

            if (!array_key_exists($item->getSenderItemId(), $withdrawsByItemId)) {
                $this->out('Couldn\'t find withdraw for payout. Payout sender item id: ' . $item->getSenderItemId() . '.');
                continue;
            }

            $withdraw = $withdrawsByItemId[$item->getSenderItemId()];

            $withdraw
                ->setPaypalBatchId($payout->getBatchHeader()->getPayoutBatchId())
                ->setPaypalItemId($userPayout->getPayoutItemId())
                ->setPaypalStatus($userPayout->getTransactionStatus())
            ;

            switch ($transactionStatus = $userPayout->getTransactionStatus()) {
                case self::PAYOUT_STATUS_CANCELED:
                case self::PAYOUT_STATUS_RETURNED:
                    $status = UserWithdraw::WITHDRAW_STATUS_CANCELLED;
                    break;

                case self::PAYOUT_STATUS_SUCCESS:
                    $status = UserWithdraw::WITHDRAW_STATUS_PCOMPLETED;
                    $withdraw->setFee($userPayout->getPayoutItemFee()->getValue() * 100);

                    break;

                case self::PAYOUT_STATUS_UNCLAIMED:
                    $status = UserWithdraw::WITHDRAW_STATUS_UNCLAIMED;
                    break;

                case self::PAYOUT_STATUS_PENDING:
                case self::PAYOUT_STATUS_PROCESSING:
                    $status = UserWithdraw::WITHDRAW_STATUS_IN_PROGRESS;
                    break;

                default:
                    $status = $transactionStatus;
            }

            $withdraw->setStatus($status);

            $this->out(
                'Withdraw ' . $withdraw->getId() . ' for user ' . $withdraw->getUserInfo() . ' with amount ' .
                $withdraw->getAmountDollars() . ' was successfully updated. New status: ' . $withdraw->getStatusString() . ' (PayPal: ' . $transactionStatus . ').'
            );
        }
    }

    public function setOutput(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @param UserWithdraw $withdraw
     *
     * @return string
     */
    private function getSenderItemId(UserWithdraw $withdraw)
    {
        return $withdraw->getId() . '_' . $withdraw->getUserInfo()->getId();
    }

    /**
     * @param string $message
     */
    private function out($message)
    {
        if ($this->output) {
            $this->output->writeln($message);
        }
    }

    /**
     * @param $method
     * @param $fields
     * @param string $parametersPostfix
     *
     * @return bool
     */
    private function legacyApiCall($method, $fields, $parametersPostfix = '')
    {
        try {
            $user      = $this->container->getParameter('paypal_legacy_api_username' . $parametersPostfix);
            $pwd       = $this->container->getParameter('paypal_legacy_api_password' . $parametersPostfix);
            $signature = $this->container->getParameter('paypal_legacy_signature' . $parametersPostfix);
        } catch (InvalidArgumentException $exception) {
            return false;
        }

        $parameters = array_merge($fields, [
            'USER'      => $user,
            'PWD'       => $pwd,
            'SIGNATURE' => $signature,
            'VERSION'   => '108',
            'METHOD'    => $method,
        ]);

        $isDev = ($this->container->getParameter('kernel.environment') == 'dev');

        if ($isDev) {
            $url = 'https://api-3t.sandbox.paypal.com/nvp';
        } else {
            $url = 'https://api-3t.paypal.com/nvp';
        }

        $curl = curl_init($url);

        $query = http_build_query($parameters);

        error_log($query);

        curl_setopt_array($curl, [
            CURLOPT_POST           => 1,
            CURLOPT_POSTFIELDS     => $query,
            CURLOPT_RETURNTRANSFER => true,
        ]);

        $responseBody = curl_exec($curl);

        curl_close($curl);

        error_log($responseBody);

        return true;
    }
}