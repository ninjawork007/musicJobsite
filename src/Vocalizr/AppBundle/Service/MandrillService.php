<?php

namespace Vocalizr\AppBundle\Service;

use DateTime;
use Hip\MandrillBundle\Dispatcher;
use Hip\MandrillBundle\Message;
use Vocalizr\AppBundle\Entity\Project;
use Vocalizr\AppBundle\Entity\ProjectDispute;
use Vocalizr\AppBundle\Entity\UserInfo;

/**
 * Class MandrillService
 *
 * @package Vocalizr\AppBundle\Service
 */
class MandrillService
{
    private $dispatcher;

    /**
     * MandrillService constructor.
     *
     * @param Dispatcher $dispatcher
     */
    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param Project $project
     * @param string  $projectUrl
     */
    public function sendProjectEmployerReceipt(Project $project, $projectUrl)
    {
        $projectOwner  = $project->getUserInfo();
        $projectBidder = $project->getBidderUser();
        $projectEscrow = $project->getProjectEscrow();

        if (!$projectEscrow->getAmount()) {
            return;
        }

        $currentDate = new DateTime();

        $taxFeeAmount  = (float) ($projectEscrow->getContractorFee() / 100);
        $projectAmount = (float) ($projectEscrow->getAmount() / 100);

        $total = $projectAmount;
        if (!$projectOwner->isSubscribed()) {
            $total = $projectAmount + $taxFeeAmount;
        }

        $parameters = [
            'username'              => $projectOwner->getUsernameOrDisplayName(),
            'gig_name'              => $project->getTitle(),
            'payment_date'          => $currentDate->format('j F Y'),
            'total_amount'          => number_format($total, 2),
            'project_amount'        => number_format($projectAmount, 2),
            'full_name'             => $projectOwner->getFullName(),
            'email'                 => $projectOwner->getEmail(),
            'employee'              => $projectBidder->getFullName(),
            'address_1'             => $projectOwner->getState() ?: '',
            'address_2'             => $projectOwner->getCity() ?: '',
            'address_3'             => $projectOwner->getCountry() ?: '',
        ];
        if (!$projectOwner->isSubscribed()) {
            $parameters['contractor_fee_amount'] = number_format($taxFeeAmount, 2);
        }

        if ($project->getProjectType() === Project::PROJECT_TYPE_PAID) {
            $parameters['gig_name'] = $project->getTitle();
            $subject                = 'Payment receipt for Vocalizr Gig with ' . $projectBidder->getUsernameOrDisplayName();
            $this->sendMessage($projectOwner->getEmail(), $subject, 'Payment Receipt (Gig Completion)', $parameters);
        } else {
            $parameters['contest_name'] = $project->getTitle();
            $subject                    = 'Payment receipt for Vocalizr Contest with ' . $projectBidder->getUsernameOrDisplayName();
            $this->sendMessage($projectOwner->getEmail(), $subject, 'Payment Receipt (Contest Completion)', $parameters);
        }
    }

    /**
     * @param Project $project
     * @param string $projectUrl
     * @param null $invoicePdfPath
     * @throws \Exception
     */
    public function sendProjectPayedMessages(Project $project, $projectUrl, $invoicePdfPath = null)
    {
        $this->sendProjectEmployerReceipt($project, $projectUrl);

        $projectOwner  = $project->getUserInfo();
        $projectBidder = $project->getBidderUser();
        $projectEscrow = $project->getProjectEscrow();

        if ($projectEscrow->getAmount()) {
            // use default subject.
            $subject = null;
        } else {
            $subject = 'Negotiation accepted';
        }

        $message = new Message();
        $message
            ->addAttachmentFromPath($invoicePdfPath, 'application/pdf', 'Vocalizr Invoice From ' . $project->getEmployerName() . '.pdf');

        // Send a message to employee.
        $this->sendMessage($projectBidder->getEmail(), $subject, 'project-release-escrow', [
            'USER'         => $projectBidder->getUsernameOrFirstName(),
            'AMOUNT'       => number_format(($projectEscrow->getAmount() / 100), 2),
            'PROJECTOWNER' => $projectOwner->getUsernameOrDisplayName(),
            'PROJECTTITLE' => $project->getTitle(),
            'PROJECTURL'   => $projectUrl,
        ], $message);
    }

    /**
     * Notify the person who sent the dispute
     * @param ProjectDispute $dispute
     * @param Project $project
     * @param string $projectUrl
     */
    public function sendProjectDisputeAmountAccepted(ProjectDispute $dispute, Project $project, $projectUrl)
    {
        $fromUser = $dispute->getFromUserInfo();
        $this->sendMessage($fromUser->getEmail(), null, 'project-dispute-amount-accepted',  [
            'USER'        => $fromUser->getUsernameOrFirstName(),
            'DISPUTEUSER' => $dispute->getUserInfo()->getUsernameOrFirstName(),
            'AMOUNT'      => number_format(($dispute->getAmount() / 100), 2),
            'PROJECTTITLE'=> $project->getTitle(),
            'PROJECTURL'  => $projectUrl
        ]);
    }

    /**
     * @param UserInfo $user
     * @param float $amount
     * @param string $orderId
     * @param DateTime|null $nextDate
     * @param bool|null $yearly
     */
    public function sendSubscriptionRenewedMessage($user, $amount, $orderId = null, DateTime $nextDate = null)
    {
        if (!$user) {
            return;
        }

        if (!$amount) {
            $amount = PayPalService::MONTHLY_PAYMENT_GROSS;
        }

        if (is_null($orderId)) {
            $orderId = '';
        }

        try {
            $date         = new DateTime('now', new \DateTimeZone('UTC'));
            $timezoneName = timezone_name_from_abbr('', 12 * 3600, false);
            $date->setTimezone(new \DateTimeZone($timezoneName));
        } catch (\Exception $exception) {
            $date = new DateTime();
        }

        if (!$nextDate) {
            $nextDate = new DateTime();

            if ($amount > 50) {
                $yearly = true;
                $nextDate->modify('+1 year');
                $amount = (int) $amount;
            } elseif ($amount == PayPalService::PROMO_STAYHOME_PAYMENT_GROSS) {
                $yearly = false;
                $nextDate->modify('+3 month');
                $amount = PayPalService::MONTHLY_PAYMENT_GROSS;
            } else {
                $yearly = false;
                $nextDate->modify('+1 month');
            }
        } else {
            $yearly = date_diff($date, $nextDate)->m > 1;
        }

        $vars = [
            'username'  => $user->getUsernameOrDisplayName(),
            'date'      => $date->format('j F Y'),
            'next_date' => $nextDate->format('j F Y'),
            'amount'    => $amount,
            'order_id'  => $orderId,
            'full_name' => $user->getFullName(),
            'email'     => $user->getEmail(),
            'employee'  => $user->getFullName(),
            'address_1' => $user->getState() ?: '',
            'address_2' => $user->getCity() ?: '',
            'address_3' => $user->getCountry() ?: '',
            'time'      => $date->format('H:i:s'),
        ];

        if ($yearly) {
            $subject  = 'Yearly Vocalizr Access Pass plan purchase receipt';
            $template = 'payment-receipt-pro-yearly-new-march';
        } else {
            $subject  = 'Monthly Vocalizr Access Pass plan purchase receipt';
            $template = 'payment-receipt-pro-monthly-new-march';
        }

        $message = new Message();
        //$message->setBccAddress('invoices@3ct36b04ct.referralcandy.com');

        $this->sendMessage($user->getEmail(), $subject, $template, $vars, $message);
    }

    /**
     * @param $body
     * @param $env
     */
    public function sendWithdrawAlert($body, $env)
    {
        $message = new \Hip\MandrillBundle\Message();
        $message->setSubject('PAYPAL ALERT : WITHDRAW : A user with email from blocklist tried to make withdraw');
        $message->setFromEmail('noreply@vocalizr.com');
        $message->setFromName('Vocalizr');

        if ($env === 'dev'){
            $message->addTo('sergey.l@zimalab.com');
        } else {
            $message->addTo('luke@vocalizr.com');
        }
        $message->addGlobalMergeVar('BODY', $body);
        $this->dispatcher->send($message, 'default');
    }


    /**
     * @param string       $to
     * @param string       $subject
     * @param string       $template
     * @param array        $vars
     * @param Message|null $message
     */
    public function sendMessage($to, $subject, $template, $vars = [], $message = null)
    {
        if (!$message) {
            $message = new Message();
        }

        if ($subject !== null) {
            $vars['MC_SUBJECT'] = $subject;
            $message->setSubject($subject);
        }

        $message
            ->addTo($to)
            ->addMergeVars($to, $vars)
            ->setTrackOpens(true)
            ->setTrackClicks(true)
        ;

        $this->dispatcher->send($message, $template);
    }
}
