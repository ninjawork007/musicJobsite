<?php

namespace Vocalizr\AppBundle\Command;

use Doctrine\ORM\EntityManager;
use Exception;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Vocalizr\AppBundle\Entity\UserWalletTransaction;
use Vocalizr\AppBundle\Entity\UserWithdraw;
use Vocalizr\AppBundle\Model\UserInfoModel;

/**
 * Class FixMultipleWithdrawTransactionsCommand
 * @package Vocalizr\AppBundle\Command
 */
class FixDoubleBillingCommand extends ContainerAwareCommand
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var UserInfoModel
     */
    private $userModel;

    protected function configure()
    {
        $this->setName('vocalizr:app:fix-double-billing');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em          = $this->getContainer()->get('doctrine.orm.entity_manager');
        $this->userModel   = $this->getContainer()->get('vocalizr_app.model.user_info');
        $transactionGroups = $this->getTransactionsGroupedByWithdrawId();

        $withdraws = $this->getWithdrawsByIds(array_keys($transactionGroups));

        $this->em->beginTransaction();

        try {
            foreach ($transactionGroups as $withdrawId => $transactions) {
                $this->processInvalidTransactions(
                    $transactions[UserWalletTransaction::TYPE_WITHDRAW_REQUEST],
                    $transactions[UserWalletTransaction::TYPE_WITHDRAW],
                    $withdraws[$withdrawId]
                );
            }
            $this->em->flush();
            $this->em->commit();
        } catch (\Exception $exception) {
            $output->writeln('An error occurred. All changes will be rolled back. Error: ' . $exception->getMessage());
            $this->em->rollback();
            throw $exception;
        }
    }

    /**
     * @param UserWalletTransaction $requestTransaction
     * @param UserWalletTransaction $withdrawTransaction
     * @param UserWithdraw $withdraw
     * @throws Exception
     */
    private function processInvalidTransactions(
        UserWalletTransaction $requestTransaction,
        UserWalletTransaction $withdrawTransaction,
        UserWithdraw $withdraw
    ) {
        if ($requestTransaction->getAmount() !== $withdrawTransaction->getAmount()) {
            throw new Exception('Different amount in doubling transactions.');
        }

        $refundAmount = -$requestTransaction->getAmount();

        $refundTransaction = $this->userModel->createWalletTransaction(
            $requestTransaction->getUserInfo(),
            $refundAmount,
            UserWalletTransaction::TYPE_WITHDRAW_REFUND
        );

        $refundTransaction
            ->setDescription('Refund')
            ->setData(json_encode(['withdraw_id' => $withdraw->getId()]))
        ;


        $this->em->persist($refundTransaction);
    }

    /**
     * @param array $ids
     * @return UserWithdraw[]
     */
    private function getWithdrawsByIds($ids)
    {
        $qb = $this->em->getRepository('VocalizrAppBundle:UserWithdraw')->createQueryBuilder('w');
        $qb
            ->where('w.id in (:ids)')
            ->setParameter('ids', $ids)
        ;

        $withdrawsById = [];

        foreach ($qb->getQuery()->getResult() as $withdraw) {
            $withdrawsById[$withdraw->getId()] = $withdraw;
        }

        return $withdrawsById;
    }

    /**
     * @return UserWalletTransaction[][]
     * @throws Exception
     */
    private function getTransactionsGroupedByWithdrawId()
    {
        $qb = $this->em->getRepository('VocalizrAppBundle:UserWalletTransaction')->createQueryBuilder('t');
        $qb
            ->where('t.type in (:types)')
            ->andWhere('t.created_at >= :startDate')
            ->andWhere('t.created_at <= :endDate')
            ->setParameters([
                'types' => [UserWalletTransaction::TYPE_WITHDRAW, UserWalletTransaction::TYPE_WITHDRAW_REQUEST, UserWalletTransaction::TYPE_WITHDRAW_REFUND],
                'startDate' => new \DateTime('2020-09-01'),
                'endDate' => new \DateTime('2020-09-12'),
            ])
        ;

        /** @var UserWalletTransaction[] $withdrawTransactions */
        $withdrawTransactions = $qb->getQuery()->getResult();

        $ignoredWithdrawIds = [];

        /** @var UserWalletTransaction[][] $transactionsByWithdraw */
        $transactionsByWithdraw = [];

        /** @var UserWalletTransaction[][] $transactionsToFix */
        $transactionsToFix = [];

        foreach ($withdrawTransactions as $transaction) {
            $withdrawId = $this->withdrawIdByTransaction($transaction);
            if (isset($transactionsByWithdraw[$withdrawId][$transaction->getType()])) {
                throw new Exception('Could not handle this case: Multiple transactions with same type found for withdraw ' . $withdrawId);
            }

            if ($transaction->getType() === UserWalletTransaction::TYPE_WITHDRAW_REFUND) {
                $ignoredWithdrawIds[] = $withdrawId;
            } else {
                $transactionsByWithdraw[$withdrawId][$transaction->getType()] = $transaction;
            }
        }

        foreach ($transactionsByWithdraw as $withdrawId => $transactions) {
            if (in_array($withdrawId, $ignoredWithdrawIds)) {
                continue;
            }

            if (count($transactions) == 2) {
                $transactionsToFix[$withdrawId] = $transactions;
            }
        }

        return $transactionsToFix;
    }

    /**
     * @param UserWalletTransaction $transaction
     *
     * @return int
     */
    private function withdrawIdByTransaction(UserWalletTransaction $transaction)
    {
        if ($transaction->getData()) {
            $data = json_decode($transaction->getData(), true);
            if (isset($data['withdraw_id']) && $data['withdraw_id']) {
                return $data['withdraw_id'];
            }
        }

        if ($transaction->getCustomId()) {
            $idParts = explode('_', $transaction->getCustomId());
            $id = end($idParts);

            if (is_numeric($id)) {
                return (int) $id;
            }
        }

        throw new \InvalidArgumentException('Transaction ' . $transaction->getId() . ' is not related to any withdraw');
    }
}