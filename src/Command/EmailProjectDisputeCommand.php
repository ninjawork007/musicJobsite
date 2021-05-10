<?php

namespace App\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Hip\MandrillBundle\Dispatcher;
use Slot\MandrillBundle\Message;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Twig_Error;
use App\Entity\ProjectDispute;
use App\Entity\UserWalletTransaction;
use App\Service\MandrillService;

class EmailProjectDisputeCommand extends Command
{
    /**
     * @var string
     */
    protected $_timeAgo;

    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var Dispatcher|object
     */
    private $dispatcher;

    /**
     * @var MandrillService
     */
    private $mandrill;

    protected function configure()
    {
        // How often do we run this script
        $this->_timeAgo = '1 hour';

        $this
                ->setName('vocalizr:email-project-dispute')
                ->setDescription('Email connection invites that were accepted [Cronjob: Every ' . $this->_timeAgo . ']')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container        = $this->getContainer();
        $doctrine         = $container->get('doctrine');
        $this->em         = $doctrine->getManager();
        $this->dispatcher = $container->get('hip_mandrill.dispatcher');
        $this->mandrill   = $container->get('vocalizr_app.service.mandrill');

        $this->firstReminder();
        $this->secondReminder();
        $this->closeDisputesAndSendEmails();
    }

    /**
     * Remind user after 1 day
     */
    private function firstReminder()
    {
        $disputes = $this->em->getRepository('App:ProjectDispute')
            ->getOpenDisputesOlderDateWithLesserReminders(new \DateTime('-1 day'), 1)
        ;

        echo 'First reminder: ' . count($disputes) . "\n";

        foreach ($disputes as $dispute) {
            $this->sendReminderMessage($dispute, 'Mail:projectDisputeFirstReminder.html.twig');

            $dispute->setRemindersSentCount(1);
            $this->em->persist($dispute);
            $this->em->flush();
        }
    }

    /**
     * Remind user after 2 days
     */
    private function secondReminder()
    {
        $disputes = $this->em->getRepository('App:ProjectDispute')
            ->getOpenDisputesOlderDateWithLesserReminders(new \DateTime('-2 days'), 2)
        ;

        echo 'Second reminder: ' . count($disputes) . "\n";

        foreach ($disputes as $dispute) {
            $this->sendReminderMessage($dispute, 'Mail:projectDisputeSecondReminder.html.twig');

            $dispute->setRemindersSentCount(2);
            $this->em->persist($dispute);
            $this->em->flush();
        }
    }

    /**
     * 3 days, close dispute due to no action from other party.
     */
    private function closeDisputesAndSendEmails()
    {
        $disputes = $this->em->getRepository('App:ProjectDispute')
            ->getOpenDisputesSinceDate(new \DateTime('-3 days'))
        ;

        echo 'Close dispute count: ' . count($disputes) . "\n";

        foreach ($disputes as $dispute) {
            // Dispute was created by project owner to the employee
            $isForEmployee = ($dispute->getUserInfo()->getId() == $dispute->getProject()->getEmployeeUserInfo()->getId());

            // CLOSE THE DISPUTE
            $this->closeDispute($dispute);

            if ($isForEmployee) {
                // Closing dispute from employer to employee

                // Send email to employer
                $this->sendMessage(
                    $dispute->getFromUserInfo()->getEmail(),
                    'Project dispute closed in your favor for ' . $dispute->getProject()->getTitle(),
                    'Mail:projectDisputeClosedAwardEmployee.html.twig',
                    ['result' => $dispute]
                );

                // Send email to employee saying it's closed
                $this->sendMessage(
                    $dispute->getUserInfo()->getEmail(),
                    'Project dispute closed in other parties favor for ' . $dispute->getProject()->getTitle(),
                    'Mail:projectDisputeClosedLostEmployer.html.twig',
                    ['result' => $dispute]
                );
            } else {
                // Closing dispute from employee to employer

                // Send email to employee
                $this->sendMessage(
                    $dispute->getFromUserInfo()->getEmail(),
                    'Project dispute closed in other parties favor for ' . $dispute->getProject()->getTitle(),
                    'Mail:projectDisputeClosedLostEmployee.html.twig',
                    ['result' => $dispute]
                );

                // Send email to employer saying it's closed
                $this->sendMessage(
                    $dispute->getUserInfo()->getEmail(),
                    'Project dispute closed in your favor for ' . $dispute->getProject()->getTitle(),
                    'Mail:projectDisputeClosedAwardEmployer.html.twig',
                    ['result' => $dispute]
                );
            }
        }
    }

    /**
     * @param ProjectDispute $dispute
     * @return bool
     * @throws OptimisticLockException
     */
    private function closeDispute($dispute)
    {
        $em     = $this->em;
        $helper = $this->getContainer()->get('service.helper');
        $dispute->setAccepted(true);

        $project    = $dispute->getProject();
        $escrow     = $project->getProjectEscrow();
        $projectBid = $project->getProjectBid();

        // Work out percent used for fee
        $feePercent = ($escrow->getFee() / $escrow->getAmount()) * 100;

        // Work out new project fee in cents
        $newProjectFee = $helper->getPricePercent($dispute->getAmount(), $feePercent, false);

        // Work out refund amount
        $refundProjectFee    = $escrow->getFee() - $newProjectFee;
        $refundProjectAmount = $escrow->getAmount() - $dispute->getAmount();

        // Update project escrow with new amount
        $escrow->setFee($newProjectFee);
        $escrow->setAmount($dispute->getAmount());
        $em->persist($escrow);

        // Create wallet transactions for refund to project owner
        $uwt = new UserWalletTransaction();
        $uwt->setUserInfo($project->getUserInfo());
        $uwt->setAmount($refundProjectAmount);
        $uwt->setCurrency($this->getContainer()->getParameter('default_currency'));
        $description = 'Refund payment for {project} escrow';
        $uwt->setDescription($description);
        $data = [
            'projectTitle' => $project->getTitle(),
            'projectUuid'  => $project->getUuid(),
        ];
        $uwt->setData(json_encode($data));
        $em->persist($uwt);

        // Refund project fee
        $uwt = new UserWalletTransaction();
        $uwt->setUserInfo($project->getUserInfo());
        $uwt->setAmount($refundProjectFee);
        $uwt->setCurrency($this->getContainer()->getParameter('default_currency'));
        $description = 'Refund gig fee for {project} escrow';
        $uwt->setDescription($description);
        $data = [
            'projectTitle' => $project->getTitle(),
            'projectUuid'  => $project->getUuid(),
        ];
        $uwt->setData(json_encode($data));
        $em->persist($uwt);

        // Make sure payment hasn't already been released
        if ($escrow->getReleasedDate()) {
            return false;
        }

        // Get bidders subscription
        $subscriptionPlan = $this->em->getRepository('App:SubscriptionPlan')
                ->getActiveSubscription($project->getBidderUser()->getId());

        // Create user wallet transaction
        // Add money to project bidders wallet
        $uwt = new UserWalletTransaction();
        $uwt->setUserInfo($projectBid->getUserInfo());
        $uwt->setAmount($escrow->getAmount()); // In cents
        $uwt->setCurrency($this->getContainer()->getParameter('default_currency'));
        $description = 'Payment for gig {project} from {username}';
        $uwt->setDescription($description);
        $data = [
            'username'     => $project->getUserInfo()->getUsername(),
            'projectTitle' => $project->getTitle(),
            'projectUuid'  => $project->getUuid(),
        ];
        $uwt->setData(json_encode($data));
        $this->em->persist($uwt);

        // Admin fee
        $format = false;

        $fee = $helper->getPricePercent($escrow->getAmount(), $subscriptionPlan['payment_percent_taken'], $format);

        // Check if there is an override
        if ($projectBid->getPaymentPercentTaken()) {
            $fee = $helper->getPricePercent($escrow->getAmount(), $projectBid->getPaymentPercentTaken(), $format);
        }

        // Create user wallet transaction
        // Deduct admin fee from wallet
        $uwt = new UserWalletTransaction();
        $uwt->setUserInfo($projectBid->getUserInfo());
        $uwt->setAmount('-' . $fee); // In cents
        $uwt->setCurrency($this->getContainer()->getParameter('default_currency'));
        $description = 'Gig fee taken for {project}';
        $uwt->setDescription($description);
        $data = [
            'projectTitle' => $project->getTitle(),
            'projectUuid'  => $project->getUuid(),
        ];
        $uwt->setData(json_encode($data));
        $this->em->persist($uwt);

        // Set release date and save
        $escrow->setContractorFee($fee);
        $escrow->setReleasedDate(new \DateTime());
        $this->em->persist($escrow);

        // Set project as completed
        $project->setIsComplete(true);
        $this->em->persist($project);

        $em->flush();
    }

    /**
     * @param ProjectDispute $dispute
     * @param string $twigTemplate
     */
    private function sendReminderMessage(ProjectDispute $dispute, $twigTemplate)
    {
        $subject = 'Reminder: Project dispute for ' . $dispute->getProject()->getTitle();
        $isEmployee = ($dispute->getUserInfo()->getId() == $dispute->getProject()->getEmployeeUserInfo()->getId());
        try {
            $this->sendMessage(
                $dispute->getUserInfo()->getEmail(),
                $subject,
                $twigTemplate,
                [
                    'result'     => $dispute,
                    'isEmployee' => $isEmployee,
                ]
            );
        } catch (\Exception $exception) {
            error_log('Could not send reminder ' . $subject);
        }
    }

    /**
     * @param string $to
     * @param string $subject
     * @param string $twigTemplate
     * @param array $twigVars
     * @throws Twig_Error
     */
    private function sendMessage($to, $subject, $twigTemplate, $twigVars)
    {
        $body = $this->getContainer()->get('twig')->render($twigTemplate, $twigVars);

        $message = new Message();
        $message
            ->setFromEmail('noreply@vocalizr.com')
            ->setFromName('Vocalizr')
        ;

        $this->mandrill->sendMessage($to, $subject, 'default', ['BODY' => $body], $message);
    }
}