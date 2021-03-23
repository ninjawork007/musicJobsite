<?php

namespace Vocalizr\AppBundle\EventListener\Payment;

use Exception;
use Vocalizr\AppBundle\Entity\Project;
use Vocalizr\AppBundle\Entity\UserWalletTransaction;
use Vocalizr\AppBundle\Event\PaymentSessionCompletedEvent;
use Vocalizr\AppBundle\Exception\NotEnoughMoneyException;
use Vocalizr\AppBundle\Model\ProjectModel;
use Vocalizr\AppBundle\Model\UserWalletTransactionModel;
use Vocalizr\AppBundle\Service\ProjectPriceCalculator;
use Vocalizr\AppBundle\Service\StripeConfigurationProvider;

/**
 * Class ProjectPaymentListener
 * @package Vocalizr\AppBundle\EventListener\Payment
 */
class ProjectPaymentListener
{
    private static $afterPaymentUpgrades = ['extend_contest'];

    /**
     * @var ProjectModel
     */
    private $projectModel;
    /**
     * @var UserWalletTransactionModel
     */
    private $walletTransactionModel;

    /**
     * @var StripeConfigurationProvider
     */
    private $config;
    /**
     * @var ProjectPriceCalculator
     */
    private $priceCalculator;

    public function __construct(
        ProjectModel $projectModel,
        UserWalletTransactionModel $walletTransactionModel,
        StripeConfigurationProvider $config,
        ProjectPriceCalculator $priceCalculator
    ) {
        $this->projectModel           = $projectModel;
        $this->walletTransactionModel = $walletTransactionModel;
        $this->config                 = $config;
        $this->priceCalculator        = $priceCalculator;
    }

    /**
     * @param PaymentSessionCompletedEvent $event
     * @throws Exception
     */
    public function onPaymentSessionCompleted(PaymentSessionCompletedEvent $event)
    {
        if (!$event->getProject()) {
            $event->addResponseMessage('ok no project');

            // This is not a project payment, do nothing.
            return;
        }

        if ($event->getProject()->getPaymentStatus() === Project::PAYMENT_STATUS_PENDING) {
            // Project is not published yet and waiting for a payment.
            $this->handlePaymentOnPublication($event);
            $event->addResponseMessage('ok project payment');
        } else {
            // Process further project upgrades after project publication.
            $this->handleUpgradeAfterPublicationPayment($event);
            $event->addResponseMessage('ok upgrade after publication payment');
        }
    }

    /**
     * @param PaymentSessionCompletedEvent $event
     * @throws NotEnoughMoneyException
     */
    private function handlePaymentOnPublication(PaymentSessionCompletedEvent $event)
    {
        $user          = $event->getUser();
        $project       = $event->getProject();
        $paymentAmountCents = $event->getTotalCentsWithoutSubscription() / 100;

        $projectPrices = $this->priceCalculator->getCalculatedPrices(
            $event->getUser()->isSubscribed() ? 'PRO' : 'FREE',
            $project
        );

        $this->walletTransactionModel->createAndPersistPair(
            $user,
            [
                $paymentAmountCents * 100,
                $projectPrices['transaction_fee'] * 100,
            ],
            [],
            [UserWalletTransaction::PROJECT_PAYMENT, UserWalletTransaction::TYPE_TRANSACTION_FEE],
            [
                sprintf(
                    'Stripe payment for project "%s" from %s (%s)',
                    $project->getTitle(),
                    $event->getMethodDataField('customer_email'),
                    $event->getMethodDataField('payment_intent')
                ),
                'Stripe transaction fee',
            ]
        );

        try {
            $this->projectModel->processPublicationPayment($project, $paymentAmountCents * 100);
        } catch (NotEnoughMoneyException $exception) {
            // Not enough money for project.
            error_log("Stripe webhooks: not enough money in wallet on project payment. Project: "
                . $project->getUuid() . ', User: ' . $user->getUsername());
        }
    }

    /**
     * @param PaymentSessionCompletedEvent $event
     * @throws Exception
     */
    private function handleUpgradeAfterPublicationPayment(PaymentSessionCompletedEvent $event)
    {
        $project = $event->getProject();

        $this->walletTransactionModel->createAndPersistPair(
            $event->getUser(),
            $event->getTotalCents(),
            [
                'projectTitle' => $project->getTitle(),
                'projectUuid'  => $project->getUuid(),
                'projectType'  => $project->getPublishType(),
            ],
            [UserWalletTransaction::PROJECT_PAYMENT, null],
            [
                sprintf(
                    'Stripe payment for project "%s" from %s (%s)',
                    $project->getTitle(),
                    $event->getMethodDataField('customer_email'),
                    $event->getMethodDataField('payment_intent')
                ),
                sprintf(
                    'Upgrade charges for %s {project}',
                    $project->getPublishType() === Project::PROJECT_TYPE_CONTEST ? 'contest' : 'gig'
                ),
            ]
        );

        foreach (self::$afterPaymentUpgrades as $upgrade) {
            if (!$event->hasItem($upgrade)) {
                continue;
            }

            $priceKey = $this->config->searchProductPriceKey($upgrade, $event->getItem($upgrade)['price']['id']);
            $this->projectModel->applyProjectUpgradeAfterPayment($project, $upgrade, $priceKey);
        }
    }
}