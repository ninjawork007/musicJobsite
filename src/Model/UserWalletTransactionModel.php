<?php

namespace App\Model;

use App\Entity\UserInfo;
use App\Entity\UserWalletTransaction;
use App\Entity\UserWithdraw;

/**
 * Class UserWalletTransactionModel
 *
 * @package App\Model
 */
class UserWalletTransactionModel extends Model
{
    /**
     * @param UserInfo $user
     * @param $operationAmountCents
     * @param array $data
     * @param array $types
     * @param array $descriptions
     *
     * @return UserWalletTransaction[]
     */
    public function createAndPersistPair(UserInfo $user, $operationAmountCents, $data = [], $types = [], $descriptions = [])
    {
        $transactions = [];

        if (is_array($operationAmountCents)) {
            $amount1 = array_shift($operationAmountCents);
            $amount2 = array_shift($operationAmountCents);
        } else {
            $amount1 = $amount2 = $operationAmountCents;
        }

        $transactions[] = $this->create($user, $amount1, $types[0], $descriptions[0], $data);
        $transactions[] = $this->create($user, -$amount2, $types[1], $descriptions[1], $data);

        foreach ($transactions as $transaction) {
            $this->em->persist($transaction);
        }

        return $transactions;
    }

    /**
     * @param UserInfo $user
     * @param float $operationAmountCents
     * @param string|null $type
     * @param string|null $description
     * @param array $data
     *
     * @return UserWalletTransaction
     */
    public function create(UserInfo $user, $operationAmountCents, $type = null, $description = null, $data = [])
    {
        $transaction = new UserWalletTransaction();

        $operationAmount = $operationAmountCents;

        $transaction
            ->setAmount($operationAmount)
            ->setType($type)
            ->setCurrency($this->container->getParameter('default_currency'))
            ->setUserInfo($user)
            ->setDescription($description)
            ->setData($data ? json_encode($data) : null)
        ;

        /**
         * Do not change wallet balance.
         *
         * @see \App\EventListener\UserWalletListener
         */
        $user->addUserWalletTransaction($transaction);

        return $transaction;
    }

    /**
     * @param UserWithdraw $withdraw
     *
     * @return UserWalletTransaction|null
     */
    public function createFromWithdraw(UserWithdraw $withdraw)
    {
        if ($withdraw->getStatus() !== UserWithdraw::WITHDRAW_STATUS_PCOMPLETED) {
            return null;
        }
        /** @var UserWalletTransaction $transaction */
        $transaction = $this->em->getRepository(UserWalletTransaction::class)
            ->findOneBy([
                'data' => '{"withdraw_id":' . $withdraw->getId() . '}'
            ]);

        $type = UserWalletTransaction::TYPE_WITHDRAW;

        if (!$transaction) {
            $amountCents = (-1) * $withdraw->getAmount();
            $description = $withdraw->getDescription();
            $transaction = $this->createWalletTransaction($withdraw->getUserInfo(), $amountCents, $type);

            $transaction
                ->setDescription($description)
                ->setEmail($withdraw->getPaypalEmail())
                ->setCustomId($type . '_' . $withdraw->getId())
                ->setData(json_encode(['withdraw_id' => $withdraw->getId()]))
            ;
        } else {
            $transaction->setType($type);
        }

        return $transaction;
    }

    /**
     * @return string
     */
    protected function getEntityName()
    {
        return UserWalletTransaction::class;
    }
}