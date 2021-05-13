<?php

namespace App\Command;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\UserInfo;
use App\Entity\UserWalletTransaction;
use App\Entity\UserWithdraw;
use App\Model\UserInfoModel;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class FixUserWalletTransactions
 *
 * @package App\Command
 */
class FixUserWalletTransactionsCommand extends Command
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var UserInfoModel
     */
    private $userInfoModel;

    /**
     * @var OutputInterface
     */
    private $output;

    private $container;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        $this->container = $container;
    }

    protected function configure()
    {
        $this->setName('vocalizr:fix:wallet');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->em            = $this->container->get('doctrine.orm.entity_manager');
        $this->userInfoModel = $this->container->get('vocalizr_app.model.user_info');
        $this->output        = $output;

        // Disable auto-update for actual wallet balance.
        $this->container->get('event.listener.user_wallet')->setEnabled(false);

        $userRepo = $this->em->getRepository('App:UserInfo');

        $usersCount = $userRepo->getUserCount();
        $perPage    = 20;
        $pageCount  = ($usersCount / $perPage) + 1;

        $this->em->beginTransaction();

        try {
            for ($page = 1; $page <= $pageCount; $page++) {
                /** @var UserInfo[] $users */
                $users = $userRepo->findByPage($page, $perPage);

                foreach ($users as $user) {
                    if ($user->getUserWalletTransactions()->isEmpty() && $user->getUserWithdraws()->isEmpty()) {
                        continue;
                    }
                    $output->writeln('Validating wallet data for user ' . $user->getUsername());
                    $this->fixTransactionsForUser($user);
                }

                $this->em->clear();
            }

            $this->em->commit();
        } catch (\Exception $exception) {
            $this->em->rollback();
            $output->writeln('An exception occurred during updating user wallet transactions. The MySQL transaction has been rolled back. ' . $exception->getMessage());
        }
    }

    /**
     * @param UserInfo $user
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function fixTransactionsForUser(UserInfo $user)
    {
        $persistedWithdrawIds = $this->getAlreadyPersistedWithdrawIds($user);

        $completedTransactionsCount = array_reduce($user->getUserWithdraws()->toArray(), function ($carry, $withdraw) {
            /** @var UserWithdraw $withdraw */
            if ($withdraw->getStatus() === UserWithdraw::WITHDRAW_STATUS_PCOMPLETED) {
                return $carry + 1;
            } else {
                return $carry;
            }
        }, 0);

        if (count($persistedWithdrawIds) < $completedTransactionsCount) {
            $this->fixWithdrawTransactions($user, $persistedWithdrawIds);
        }

        $reverseTransactions = $this->em->getRepository('App:UserWalletTransaction')
            ->findTransactionsByUser($user)
        ;

        $actualBalanceCents = $user->getWallet();
        $this->output->writeln('Current balance ' . $actualBalanceCents / 100);

        foreach ($reverseTransactions as $reverseTransaction) {
            $this->output->writeln(
                "Found transaction \t" .
                $reverseTransaction->getCreatedAt()->format('Y-m-d H:i:s') .
                "\tAmount: " . ($reverseTransaction->getAmount() / 100) .
                "\tBalance: " . ($actualBalanceCents / 100)
            );
            if ($actualBalanceCents < 0) {
                $this->output->writeln("User {$user->getUsernameOrDisplayName()}: Actual balance dropped under the 0. Set it back to 0.");
                $actualBalanceCents = 0;
            }
            if (!is_null($reverseTransaction->getActualBalance()) && ($reverseTransaction->getActualBalance() !== $actualBalanceCents)) {
                $this->output->writeln(
                   'Transaction already have actual balance ' .
                   $reverseTransaction->getActualBalance() .
                    ' which is not equal to new calculated balance ' . $actualBalanceCents
               );
            }

            $reverseTransaction->setActualBalance($actualBalanceCents);

            $actualBalanceCents -= $reverseTransaction->getAmount();
            $this->output->writeln("Balance: \t" . $actualBalanceCents / 100);
            $this->em->persist($reverseTransaction);
        }

        $this->em->flush();
    }

    /**
     * @param UserInfo $user
     * @param int[]    $persistedWithdrawIds
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function fixWithdrawTransactions(UserInfo $user, $persistedWithdrawIds)
    {
        $qb = $this->em->getRepository('App:UserWithdraw')->createQueryBuilder('uw');
        $qb
            ->where('uw.user_info = :user')
            ->andWhere('uw.status = :completed_status')

            ->setParameter('user', $user)
            ->setParameter('completed_status', UserWithdraw::WITHDRAW_STATUS_PCOMPLETED)
        ;

        if (!empty($persistedWithdrawIds)) {
            $qb->andWhere($qb->expr()->notIn('uw.id', $persistedWithdrawIds));
        }

        /** @var UserWithdraw[] $notPersistedWithdraws */
        $notPersistedWithdraws = $qb->getQuery()->getResult();

        foreach ($notPersistedWithdraws as $withdraw) {
            $transaction = $this->userInfoModel->createWalletTransactionFromWithdraw($withdraw);
            $transaction
                ->setActualBalance(null)
                ->setCreatedAt($withdraw->getCreatedAt())
            ;
            $this->em->persist($transaction);
        }

        $this->em->flush();
    }

    /**
     * @param UserInfo $user
     *
     * @return int[]
     */
    private function getAlreadyPersistedWithdrawIds(UserInfo $user)
    {
        $newIds = [];
        foreach ($user->getUserWalletTransactions() as $transaction) {
            if ($transaction->getType() !== UserWalletTransaction::TYPE_WITHDRAW) {
                continue;
            }

            if ($transaction->getData()) {
                $data = json_decode($transaction->getData(), true);
                if (isset($data['withdraw_id'])) {
                    $newIds[] = $data['withdraw_id'];
                }
            }
        }

        $transactionRepo = $this->em->getRepository('App:UserWalletTransaction');

        $qb = $transactionRepo->createQueryBuilder('uwt');
        $qb
            ->select('uwt.id')
            ->leftJoin('App:UserWithdraw', 'uw', Join::WITH, 'uw.created_at = uwt.created_at')

            ->where('uw is null')
            ->andWhere('uwt.user_info = :user_id')
            ->andWhere('uwt.description = :default_withdraw_description')

            ->setParameter('user_id', $user)
            ->setParameter('default_withdraw_description', 'User Wallet Withdraw')
        ;

        $transactionsWithAlreadyDeletedWithdraws = array_column(
            $qb->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY),
            'id'
        );

        if (!empty($transactionsWithAlreadyDeletedWithdraws)) {
            $qb = $transactionRepo->createQueryBuilder('uwt');
            $qb
                ->delete()
                ->where($qb->expr()->in('uwt.id', $transactionsWithAlreadyDeletedWithdraws))
            ;
            $qb->getQuery()->execute();
        }

        $qb = $transactionRepo->createQueryBuilder('uwt');
        $qb
            ->select('uw.id')
            ->join('App:UserWithdraw', 'uw', Join::WITH, 'uw.created_at = uwt.created_at')

            ->where('uw.user_info = :user_id')
            ->andWhere('uwt.user_info = :user_id')
            ->andWhere('uwt.description = :default_withdraw_description')

            ->setParameter('user_id', $user)
            ->setParameter('default_withdraw_description', 'User Wallet Withdraw')
        ;

        $oldIds = array_column($qb->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY), 'id');

        $ids = array_filter(array_merge($newIds, $oldIds));

        return $ids;
    }
}