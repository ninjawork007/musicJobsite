<?php

namespace App\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\UserWithdraw;
use App\Repository\UserWithdrawRepository;
use App\Service\PayPalApiService;

/**
 * Class PaypalMakePayout
 *
 * @package App\Command
 */
class PaypalMakePayoutCommand extends Command
{
    /** @var PayPalApiService */
    private $apiClient;

    /** @var EntityManager */
    private $em;

    protected function configure()
    {
        $this
            ->setName('vocalizr:make-payouts');
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
        $this->apiClient = $this->getContainer()->get('vocalizr_app.paypal_api');
        $this->em        = $this->getContainer()->get('doctrine.orm.entity_manager');

        $this->apiClient->setOutput($output);

        /** @var UserWithdrawRepository $withdrawRepo */
        $withdrawRepo = $this->em->getRepository('App:UserWithdraw');

        $cancelledWithdraws = $withdrawRepo->getByStatus(UserWithdraw::WITHDRAW_STATUS_CANCEL_REQUESTED);

        if (!empty($cancelledWithdraws)) {
            foreach ($cancelledWithdraws as $withdraw) {
                $this->apiClient->cancelPayout($withdraw);
                $this->em->persist($withdraw);
            }
            $this->em->flush();
        }

        $inProgressWithdraws = $withdrawRepo->getByStatus(UserWithdraw::WITHDRAW_STATUS_IN_PROGRESS);

        if (!empty($inProgressWithdraws)) {
            $this->apiClient->updatePayoutStatuses($inProgressWithdraws);

            foreach ($inProgressWithdraws as $withdraw) {
                $this->em->persist($withdraw);
            }
            $this->em->flush();
        }

        $unclaimedWithdraws = $withdrawRepo->getByStatus(UserWithdraw::WITHDRAW_STATUS_UNCLAIMED);

        if (!empty($unclaimedWithdraws)) {
            $this->apiClient->updatePayoutStatuses($unclaimedWithdraws);

            foreach ($unclaimedWithdraws as $withdraw) {
                $this->em->persist($withdraw);
            }
            $this->em->flush();
        }

        $pendingWithdraws = $withdrawRepo->getByStatus(UserWithdraw::WITHDRAW_STATUS_PENDING);

        if (!empty($pendingWithdraws)) {
            try {

                // Loop through withdraws, and remove any users that are not subscribed and blocked emails
                foreach ($pendingWithdraws as $k => $pw) {
                    if (in_array($pw->getPaypalEmail(), $this->getContainer()->getParameter('withdraw_emails'))
                        || in_array($pw->getUserInfo()->getWithdrawEmail(), $this->getContainer()->getParameter('withdraw_emails'))) {
                        unset($pendingWithdraws[$k]);
                        continue;
                    }
                    if (!$pw->getUserInfo()->isSubscribed()) {
                        $datetime1 = new \DateTime();
                        $datetime2 = $pw->getCreatedAt();
                        $interval  = $datetime1->diff($datetime2)->days;
                        if ($interval < 3) {
                            unset($pendingWithdraws[$k]);
                        }
                    }
                }

                if (!empty($pendingWithdraws)) {
                    $payout = $this->apiClient->withdraw($pendingWithdraws);

                    $batchId = $payout->getBatchHeader()->getPayoutBatchId();

                    foreach ($pendingWithdraws as $pendingWithdraw) {
                        $pendingWithdraw->setPaypalBatchId($batchId);
                    }

                    $this->apiClient->updatePayoutStatuses($pendingWithdraws);
                }
            } catch (\Exception $e) {
                $output->writeln('An error occurred while sending payout request! Error message:' . $e->getMessage());

                foreach ($pendingWithdraws as $failedWithdraw) {
                    $failedWithdraw->setStatus(UserWithdraw::WITHDRAW_STATUS_ERROR);
                    $failedWithdraw->setStatusReason($e->getMessage());
                }
            }

            foreach ($pendingWithdraws as $withdraw) {
                $this->em->persist($withdraw);
            }
            $this->em->flush();
        }
    }
}