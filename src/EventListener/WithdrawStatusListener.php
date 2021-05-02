<?php

namespace App\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;
use App\Entity\UserActionAudit;
use App\Entity\UserWalletTransaction;
use App\Entity\UserWithdraw;
use App\Service\MandrillService;

/**
 * Class WithdrawStatusListener
 *
 * @package App\EventListener
 */
class WithdrawStatusListener
{
    const MESSAGE_TEMPLATE = 'Blank Payout Notification Template';

    const SUCCESS_TEMPLATE = 'Payout SUCCESS Email';

    const ERROR_TEMPLATE = 'Payout Error Email';

    /** @var EntityManager */
    private $em;

    /** @var MandrillService */
    private $mandrill;

    /**
     * @var array<string, int>|int[]
     */
    private $handledWithdraws = [];

    /**
     * @var ContainerInterface
     */
    private $container;

    private $recomputeChangesets = false;

    /**
     * WithdrawStatusListener constructor.
     *
     * @param MandrillService    $mandrill
     * @param ContainerInterface $container
     */
    public function __construct(MandrillService $mandrill, ContainerInterface $container)
    {
        $this->mandrill  = $mandrill;
        $this->container = $container;
    }

    /**
     * @param UserWithdraw $withdraw
     * @param string       $oldStatus
     * @param string       $newStatus
     */
    public function onStatusChange(UserWithdraw $withdraw, $oldStatus, $newStatus)
    {
        $user          = $withdraw->getUserInfo();
        $userEmail     = $user->getEmail();
        $amount        = '$' . $withdraw->getAmountDollars();
        $withdrawEmail = $withdraw->getPaypalEmail();

        /** @var UserWalletTransaction $relatedTransaction */
        $relatedTransaction = $this->em->getRepository(UserWalletTransaction::class)->findByCustomId([
            'WITHDRAW_REQUEST_' . $withdraw->getId(),
            'WITHDRAW_' . $withdraw->getId(),
        ]);

        if ($relatedTransaction) {
            if ($relatedTransaction->getData()) {
                $data = json_decode($relatedTransaction->getData(), true);
            } else {
                $data = [];
            }

            if ($newStatus === UserWithdraw::WITHDRAW_STATUS_PENDING || $newStatus === UserWithdraw::WITHDRAW_STATUS_IN_PROGRESS) {
                $data['status_string'] = 'Processing';
            } else {
                $data['status_string'] = $withdraw->getStatusString();
            }
            $data['status'] = $withdraw->getStatus();
            $relatedTransaction->setData(json_encode($data));

            if ($newStatus !== UserWithdraw::WITHDRAW_STATUS_WAITING_APPROVE) {
                $relatedTransaction
                    ->setCustomId(UserWalletTransaction::TYPE_WITHDRAW . '_' . $withdraw->getId())
                    ->setType(UserWalletTransaction::TYPE_WITHDRAW)
                ;
            }

            if (
                ($newStatus === UserWithdraw::WITHDRAW_STATUS_CANCELLED || $newStatus === UserWithdraw::WITHDRAW_STATUS_ERROR)
                && $withdraw->getId()
            ) {
                $this->recalcWalletAfterErrorWithdraw($relatedTransaction);
            }
            $this->scheduleRecomputeChangesets();
        } else {
            error_log('Related transaction not found for withdraw ' . $withdraw->getId());
        }

        if (
            $newStatus === UserWithdraw::WITHDRAW_STATUS_WAITING_APPROVE ||
            $newStatus === UserWithdraw::WITHDRAW_STATUS_CANCELLED ||
            $newStatus === UserWithdraw::WITHDRAW_STATUS_CANCEL_REQUESTED ||
            $newStatus === UserWithdraw::WITHDRAW_STATUS_PENDING
        ) {
            $audit = $this->container->get('vocalizr_app.model.user_audit')->createWithdrawAudit($withdraw);

            $this->em->persist($audit);
            $this->scheduleRecomputeChangesets();
        }

        if ($oldStatus === UserWithdraw::WITHDRAW_STATUS_IN_PROGRESS && $newStatus === UserWithdraw::WITHDRAW_STATUS_PCOMPLETED) {
            $this->sendEmail($userEmail, 'Wallet withdrawal completed!', self::SUCCESS_TEMPLATE, [
                'username'             => $user->getUsernameOrDisplayName(),
                'withdrawal_amount'    => $amount,
                'paypal_email_address' => $withdrawEmail,
            ]);
        }

        if ($newStatus === UserWithdraw::WITHDRAW_STATUS_ERROR) {
            $this->sendEmail($userEmail, 'Error while processing withdraw', self::ERROR_TEMPLATE, [
                'username'             => $user->getUsernameOrDisplayName(),
                'withdrawal_amount'    => $amount,
                'paypal_email_address' => $withdrawEmail,
            ]);

            $this->sendEmail('luke@vocalizr.com', 'Error while processing withdraw', self::MESSAGE_TEMPLATE, [
                'MERGE_BODY' => "The error has occurred while processing $amount withdraw for user {$user->getUsername()} with $withdrawEmail email.",
            ]);
        }
    }

    /**
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        $this->recomputeChangesets = false;

        $this->em        = $args->getEntityManager();
        $unitOfWork      = $this->em->getUnitOfWork();
        $updatedEntities = array_merge($unitOfWork->getScheduledEntityUpdates(), $unitOfWork->getScheduledEntityInsertions());

        foreach ($updatedEntities as $updatedEntity) {
            if (!$updatedEntity instanceof UserWithdraw) {
                continue;
            }

            $changeSet = $unitOfWork->getEntityChangeSet($updatedEntity);

            if (!is_array($changeSet)) {
                continue;
            }

            if (array_key_exists('status', $changeSet)) {
                $changes = $changeSet['status'];

                $oldStatus = array_key_exists(0, $changes) ? $changes[0] : null;
                $newStatus = array_key_exists(1, $changes) ? $changes[1] : null;

                if ($oldStatus !== $newStatus && !$this->isAlreadyChanged($updatedEntity, $newStatus)) {
                    $this->onStatusChange($updatedEntity, $oldStatus, $newStatus);
                }
            }
        }

        if ($this->recomputeChangesets) {
            $this->em->getUnitOfWork()->computeChangeSets();
        }
    }

    /**
     * @param UserWithdraw $withdraw
     * @param string       $status
     *
     * @return bool
     */
    private function isAlreadyChanged(UserWithdraw $withdraw, $status)
    {
        if (array_key_exists($withdraw->getId(), $this->handledWithdraws)) {
            if ($this->handledWithdraws[$withdraw->getId()] === $status) {
                return true;
            }
        }

        $this->handledWithdraws[$withdraw->getId()] = $status;

        return false;
    }

    /**
     * @param string   $to
     * @param string   $subject
     * @param string   $template
     * @param string[] $vars
     */
    private function sendEmail($to, $subject, $template, $vars = [])
    {
        $this->mandrill->sendMessage($to, $subject, $template, $vars);
    }

    /**
     * @param UserWalletTransaction $uwTxn
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function recalcWalletAfterErrorWithdraw(UserWalletTransaction $uwTxn)
    {
        $txns = $this->em->getRepository(UserWalletTransaction::class)->findTransactionsAfterId($uwTxn->getId(), $uwTxn->getUserInfo());

        foreach ($txns as $txn) {
            $txn->setActualBalance($txn->getActualBalance() + abs($uwTxn->getAmount()));
        }

        $uwTxn->getUserInfo()->setWallet($uwTxn->getUserInfo()->getWallet() + abs($uwTxn->getAmount()));

        $this->em->remove($uwTxn);
    }

    private function scheduleRecomputeChangesets()
    {
        $this->recomputeChangesets = true;
    }
}