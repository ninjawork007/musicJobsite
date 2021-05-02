<?php

namespace App\Command;

use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\StripeCharge;
use App\Repository\StripeChargeRepository;

class UpdatingBalanceTransactionListCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('vocalizr:updating-balance-transaction-list')
            ->setDescription('Updating the balance transaction List')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getEntityManager();
        $stripeManager = $this->getContainer()->get('vocalizr_app.stripe_manager');
        /** @var StripeChargeRepository $stripeChargeRepository */
        $stripeChargeRepository = $em->getRepository('App:StripeCharge');

        /** @var StripeCharge[] $charges */
        $charges = $stripeChargeRepository->findBy(['balanceTransaction' => '']);
        foreach ($charges as $charge) {
            $data = json_decode($charge->getData(), true);
            if (isset($data['data']['object']['balance_transaction'])) {
                $stripeChargeCurrentBalanceTransaction = $stripeChargeRepository->findBy(['balanceTransaction' => $data['data']['object']['balance_transaction']]);
                if (!$stripeChargeCurrentBalanceTransaction) {
                    $balanceTransaction = $stripeManager->call('/balance_transactions/' . $data['data']['object']['balance_transaction'], [], [], 'GET');
                    if (isset($balanceTransaction['amount'])) {
                        $stripeChargeBalanceTransaction = new StripeCharge();
                        $stripeChargeBalanceTransaction->setAmount($balanceTransaction['amount']);
                        $stripeChargeBalanceTransaction->setData(json_encode($balanceTransaction));
                        $stripeChargeBalanceTransaction->setBalanceTransaction($balanceTransaction['id']);
                        $em->persist($stripeChargeBalanceTransaction);
                    }
                }

            }

        }
        $em->flush();

    }
}