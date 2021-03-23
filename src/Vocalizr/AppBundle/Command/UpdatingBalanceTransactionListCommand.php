<?php

namespace Vocalizr\AppBundle\Command;

use GuzzleHttp\Exception\GuzzleException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Vocalizr\AppBundle\Entity\StripeCharge;
use Vocalizr\AppBundle\Repository\StripeChargeRepository;

class UpdatingBalanceTransactionListCommand extends ContainerAwareCommand
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
        $stripeChargeRepository = $em->getRepository('VocalizrAppBundle:StripeCharge');

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