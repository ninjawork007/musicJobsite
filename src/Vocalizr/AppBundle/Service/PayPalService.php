<?php

namespace Vocalizr\AppBundle\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\RegistryInterface as Doctrine;

use Hip\MandrillBundle\Dispatcher;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Bridge\Twig\TwigEngine;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Vocalizr\AppBundle\Entity\EngineOrder;
use Vocalizr\AppBundle\Entity\Notification;
use Vocalizr\AppBundle\Entity\PayPalTransaction;
use Vocalizr\AppBundle\Entity\Project;
use Vocalizr\AppBundle\Entity\ProjectEscrow;
use Vocalizr\AppBundle\Entity\UserInfo;
use Vocalizr\AppBundle\Entity\UserSubscription;
use Vocalizr\AppBundle\Entity\UserWalletTransaction;
use Vocalizr\AppBundle\Entity\UserWithdraw;

class PayPalService
{
    const GIG_PAYMENT = 'Test';

    const PROMO_STAYHOME_PAYMENT_GROSS = 14.97;
    const MONTHLY_STAYHOME_PAYMENT_GROSS = 4.99;
    const MONTHLY_PAYMENT_GROSS = 10.99;
    const YEARLY_PAYMENT_GROSS = 106.0;

    const MONTHLY_PAYMENT_GROSS_OLD = 9.0;
    const YEARLY_PAYMENT_GROSS_OLD = 89.0;

    /** @var ContainerInterface */
    public $container;

    /** @var EntityManager */
    public $em;

    /** @var TwigEngine */
    public $templating;

    /** @var Dispatcher */
    public $dispatcher;

    /** @var MandrillService */
    public $mandrill;

    /** @var bool */
    public $testMode;

    /** @var string|null */
    public $primaryEmail;

    /** @var string|null */
    public $currency;

    /** @var string|null */
    public $notifyUrl;

    /** @var string|null */
    public $url;

    /**
     * A transaction to assign user or other details if needed to
     *
     * @var PayPalTransaction|null
     */
    private $payPalTransaction;

    /**
     * PayPalService constructor.
     *
     * @param RegistryInterface  $doctrine
     * @param ContainerInterface $container
     * @param TwigEngine         $templating
     * @param MandrillService    $mandrill
     */
    public function __construct($doctrine, $container, $templating, $mandrill)
    {
        $this->em         = $doctrine->getEntityManager();
        $this->container  = $container;
        $this->templating = $templating;
        $this->dispatcher = $container->get('hip_mandrill.dispatcher');
        $this->mandrill   = $mandrill;

        // Paypal Variables
        $this->url = $container->getParameter('paypal_url');

        $this->primaryEmail = $this->container->getParameter('paypal_primary_email');
        $this->currency     = $this->container->getParameter('paypal_currency');
        $this->notifyUrl    = $this->container->getParameter('paypal_notify_url');

        // Test mode
        $this->testMode = false;
        if ($container->getParameter('paypal_test_mode')) {
            $this->testMode = true;
            $this->url      = $container->getParameter('paypal_test_url');
        }
    }

    /**
     * Process IPN Request
     *
     * @param array $data
     *
     * @return bool
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function processIpn($data = null)
    {
        $em = $this->em;

        if (is_null($data)) {
            $data = $this->_getRawPostData();
        }

        // Verify and record IPN request
        if (!$payPalTransaction = $this->_verifyAndRecordIpnRequest($data)) {
            $this->log('WARN', 'There was a problem verifying IPN request');
            return false;
        }

        $this->payPalTransaction = $payPalTransaction;

        $subscriptionPlanRepo     = $em->getRepository('VocalizrAppBundle:SubscriptionPlan');
        $userInfoRepo             = $em->getRepository('VocalizrAppBundle:UserInfo');
        $userSubscriptionPlanRepo = $em->getRepository('VocalizrAppBundle:UserSubscription');

        $this->extractCustom($data);

        // Handle transaction type
        $txnType = $payPalTransaction->getTxnType();

        switch ($txnType) {
            case 'web_accept':
                $this->handleWebAccept($data);
                break;

            /**
             * Create Subscription for signup
             */
            case 'subscr_signup':
                $this->handleSubscrSignUp($data);
                break;

            /**
             * Create / Update subscription plan for user
             */
            case 'subscr_payment':
                $this->handleSubscrPayment($data);
                break;

            case 'recurring_payment_suspended':
            case 'recurring_payment_suspended_due_to_max_failed_payment':
                $this->handleSubscrCancel($data, false);
                break;
            case 'subscr_cancel':
                $this->handleSubscrCancel($data, true);
                break;
            case 'masspay':
                $this->handleMassPayment($data);
                break;
            case 'Refunded':
                $this->handleSubscriptionRefund($data);
                break;
            case 'recurring_payment_failed':
                $this->handleRecurringPaymentFailed($data);
                break;
        }

        return true;
    }

    /**
     * Handle web accept transaction
     *
     * @param array $data
     */
    public function handleWebAccept($data)
    {
        $helper = $this->container->get('service.helper');

        $payerEmail = $data['payer_email'];

        // Block emails
        $badEmails = ['gabrielpelic97@hotmail.com'];
        if (in_array($payerEmail, $badEmails)) {
            // Notify admins
            $message = new \Hip\MandrillBundle\Message();
            $message->setSubject('Banned paypal email tried to use system');
            $message->setFromEmail('noreply@vocalizr.com');
            $message->setFromName('Vocalizr');
            $message
                ->setTrackOpens(true)
                ->setTrackClicks(true);

            $message->addTo('team@vocalizr.com');
            $body = 'The banned email: ' . $data['payer_email'] . ' tried to add to their wallet';
            $message->addGlobalMergeVar('BODY', $body);
            $this->dispatcher->send($message, 'default');

            return false;
        }

        // If payment is not completed, ignore
        if (strtoupper($data['payment_status']) != 'COMPLETED') {
            return false;
        }

        // Need custom data to identify payment
        if (!isset($data['custom']) || empty($data['custom'])) {
            return false;
        }
        $transactionDescription = 'Paypal payment from ' . $data['payer_email'] . ' (' . $data['txn_id'] . ')';

        // Calculate values
        $paymentGross   = $data['payment_gross'] - 0.30;
        $depositAmount  = number_format(($paymentGross * 100) / 103.6, 2);
        $transactionFee = $data['payment_gross'] - $helper->getMoneyAsInt($depositAmount);

        $custom = json_decode($data['custom'], true);

        if (isset($custom['user_email'])) {
            $user = $this->em->getRepository('VocalizrAppBundle:UserInfo')->findOneBy(['email' => $custom['user_email']]);
            $projectRepository = $this->em->getRepository('VocalizrAppBundle:Project');
            /** @var Project $project */
            $project = $projectRepository->findOneBy(['uuid' => $custom['uuid']]);
            $this->setUserToPayPalTransaction($user);
            $uwt = new UserWalletTransaction();
            $uwt
                ->setUserInfo($user)
                ->setAmount($data['payment_gross'] * 100) // In cents
                ->setCurrency($data['mc_currency'])
                ->setDescription($transactionDescription)
                ->setType(UserWalletTransaction::PROJECT_PAYMENT)
                ->setEmail($payerEmail)
            ;
            $this->em->persist($uwt);
            if (isset($custom['vocalizr_fee'])) {
                $uwt = new UserWalletTransaction();
                $uwt->setUserInfo($user)
                    ->setAmount('-' . ($custom['vocalizr_fee'] * 100)) // In cents
                    ->setCurrency($data['mc_currency'])
                    ->setDescription('Vocalizr fee')
                    ->setType(UserWalletTransaction::TYPE_TRANSACTION_FEE)
                    ->setEmail($payerEmail)
                ;
                $this->em->persist($uwt);

            }
            $uwt = new UserWalletTransaction();
            $uwt->setUserInfo($user)
                ->setAmount('-' . ($transactionFee * 100)) // In cents
                ->setCurrency($data['mc_currency'])
                ->setDescription('Transaction fee')
                ->setType(UserWalletTransaction::TYPE_TRANSACTION_FEE)
                ->setEmail($payerEmail)
            ;

            $this->em->persist($uwt);

            $uwt = new UserWalletTransaction();
            if (!isset($custom['vocalizr_fee'])) {
                $custom['vocalizr_fee'] = 0;
            }
            if ($project->getProjectType() !== Project::PROJECT_TYPE_CONTEST) {
                $uwt
                    ->setUserInfo($user)
                    ->setAmount('-' . ($data['payment_gross'] * 100) + ($transactionFee * 100) + ($custom['vocalizr_fee'] * 100)) // In cents
                    ->setCurrency($data['mc_currency'])
                    ->setDescription(sprintf(
                        'Upgrade charges for %s ' . $project->getTitle(),
                        $project->getPublishType() === Project::PROJECT_TYPE_CONTEST ? 'contest' : 'gig'
                    ))
                    ->setType(UserWalletTransaction::PROJECT_PAYMENT)
                    ->setEmail($payerEmail)
                ;
            } else {
                $uwt
                    ->setUserInfo($user)
                    ->setAmount((($project->getBudgetFrom() * 100) + ($transactionFee * 100) + ($custom['vocalizr_fee'] * 100)) - ($data['payment_gross'] * 100)) // In cents
                    ->setCurrency($data['mc_currency'])
                    ->setDescription(sprintf(
                        'Upgrade charges for %s ' . $project->getTitle(),
                        $project->getPublishType() === Project::PROJECT_TYPE_CONTEST ? 'contest' : 'gig'
                    ))
                    ->setType(UserWalletTransaction::PROJECT_PAYMENT)
                    ->setEmail($payerEmail)
                ;
                $this->em->persist($uwt);
                $uwt = new UserWalletTransaction();
                $uwt
                    ->setUserInfo($user)
                    ->setAmount('-' . ($project->getBudgetFrom() * 100)) // In cents
                    ->setCurrency($data['mc_currency'])
                    ->setDescription(sprintf(
                        'Paypal payment for project "%s" from %s (%s)',
                        $project->getTitle(),
                        $data['payer_email'],
                        $data['txn_id']
                    ))
                    ->setType(UserWalletTransaction::PROJECT_PAYMENT)
                    ->setEmail($payerEmail)
                ;
            }

            $this->em->persist($uwt);
            if (!$project) {
                return false;
            }
            $project->setPaymentStatus(Project::PAYMENT_STATUS_PAID);
            $project->setPaypalTransaction($this->payPalTransaction);
            $project->setFeatured($custom['featured']);
            $project->setFeaturedAt(new \DateTime());
            $project->setShowInNews(!$custom['publish_type']);
            $project->setHighlight($custom['highlight']);
            $project->setMessaging($custom['messaging']);
            $project->setToFavorites($custom['to_favorites']);
            $project->setProRequired($custom['lock_to_cert']);
            $project->setRestrictToPreferences($custom['restrict_to_preferences']);
            $dt = new \DateTime();
            $dt->modify('+28 days');
            $dt->modify('+23 hours');
            $dt->modify('+59 minutes');
            $project->setBidsDue($dt);
            $project->setIsActive(true);
            $project->setPublishedAt(new \DateTime());
            $amount = $project->getBudgetTo() * 100;

            // Add to project escrow
            $pe = new ProjectEscrow();
            $pe->setFee(0);
            $pe->setAmount($amount);
            $pe->setUserInfo($user);
            $this->em->persist($pe);

            $project->setProjectEscrow($pe);
            $this->em->flush();
        }
        // If custom var starts with DEPOSIT
        if (substr($data['custom'], 0, 7) == 'DEPOSIT') {
            // Get user unique string from custom field
            $userStr = str_replace('DEPOSIT', '', $data['custom']);

            // get user
            /** @var UserInfo|null $user */
            $user = $this->em->getRepository('VocalizrAppBundle:UserInfo')->findOneBy(['unique_str' => $userStr]);
            if (!$user) {
                $this->log('WARN', "IPN: DEPOSIT: User doesn't exist");
                return false;
            }

            $this->setUserToPayPalTransaction($user);

            if ($user->getWithdrawEmail() == null) {
                $user->setWithdrawEmail($payerEmail);
                $this->em->flush();
            }

            if ($user->getWithdrawEmail() != $payerEmail) {
                $this->log('WARN', "IPN: DEPOSIT: User deposited on a different email address");

                $message = new \Hip\MandrillBundle\Message();
                $message->setSubject('PAYPAL ALERT : DEPOSIT : User deposited on a different email address');
                $message->setFromEmail('noreply@vocalizr.com');
                $message->setFromName('Vocalizr');

                if ($this->container->getParameter('kernel.environment') === 'dev') {
                    $message->addTo('timofey.n@zimalab.com');
                } else {
                    $message->addTo('luke@vocalizr.com');
                }

                $this->container->get('vocalizr_app.paypal_api')->refundTransaction(
                    $data['txn_id'],
                    'Deposits into Vocalizr with multiple PayPal accounts is prohibited'
                );

                $notification = new Notification();
                $notification
                    ->setUserInfo($user)
                    ->setActionedUserInfo($user)
                    ->setNotifyType(Notification::NOTIFY_TYPE_WALLET_DEPOSIT_FAILED)
                ;

                $this->container->get('vocalizr_app.model.wallet_transaction')->createAndPersistPair(
                    $user,
                    $data['payment_gross'] * 100,
                    [],
                    [UserWalletTransaction::TYPE_WRONG_DEPOSIT, UserWalletTransaction::TYPE_DEPOSIT_REFUND],
                    [$transactionDescription, 'Deposit refund (deposits into Vocalizr with multiple PayPal accounts is prohibited)']
                );

                $this->em->persist($notification);
                $this->em->flush();

                $body = $this->container->get('templating')->render('VocalizrAppBundle:Mail:paypalAlertDeposit.html.twig', [
                    'user' => $user,
                    'paymentEmail' => $data['payer_email'],
                    'paymentAmount' => $data['payment_gross'],
                    'transactionId' => $data['txn_id']
                ]);
                $message->addGlobalMergeVar('BODY', $body);
                $this->dispatcher->send($message, 'default');

                return false;
            }

            if (!$user->isVerified()) {
                $this->log('WARN', "IPN: DEPOSIT: User deposited being not verified");

                $this->container->get('vocalizr_app.paypal_api')->refundTransaction(
                    $data['txn_id'],
                    'Deposits into Vocalizr account is allowed only for Verified users'
                );

                $this->container->get('vocalizr_app.model.wallet_transaction')->createAndPersistPair(
                    $user,
                    $data['payment_gross'] * 100,
                    [],
                    [UserWalletTransaction::TYPE_WRONG_DEPOSIT, UserWalletTransaction::TYPE_DEPOSIT_REFUND],
                    [$transactionDescription, 'Refund (Deposits into Vocalizr account is allowed only for Verified users)']
                );

                $this->em->flush();

                return false;
            }

            // Create user wallet transaction
            $uwt = new UserWalletTransaction();
            $uwt
                ->setUserInfo($user)
                ->setAmount($data['payment_gross'] * 100) // In cents
                ->setCurrency($data['mc_currency'])
                ->setDescription($transactionDescription)
                ->setType(UserWalletTransaction::TYPE_DEPOSIT)
                ->setEmail($payerEmail)
            ;
            $this->em->persist($uwt);

            $uwt = new UserWalletTransaction();
            $uwt->setUserInfo($user)
                ->setAmount('-' . ($transactionFee * 100)) // In cents
                ->setCurrency($data['mc_currency'])
                ->setDescription('Transaction fee')
                ->setType(UserWalletTransaction::TYPE_TRANSACTION_FEE)
                ->setEmail($payerEmail)
            ;
            $this->em->persist($uwt);

            $this->em->flush();
        }

        // if custom var starts with ENGINE
        if (substr($data['custom'], 0, 6) == 'ENGINE') {
            // Get user unique string from custom field
            $uid = str_replace('ENGINE', '', $data['custom']);

            /** @var EngineOrder|null $engineOrder */
            $engineOrder = $this->em->getRepository('VocalizrAppBundle:EngineOrder')->findOneBy(['uid' => $uid]);

            if (!$engineOrder) {
                $this->log('WARN', "IPN: Engine: Engine order doesn't exist (" . $uid . ')');
                return false;
            }

            $this->setUserToPayPalTransaction($engineOrder->getUserInfo());

            $paymentGross   = $data['payment_gross'] - 0.30;
            $depositAmount  = number_format(($paymentGross * 100) / 103.6, 2);
            $transactionFee = $data['payment_gross'] - $helper->getMoneyAsInt($depositAmount);

            $eoamount = number_format(($engineOrder->getAmount() / 100), 2);

            if ($depositAmount != $eoamount) {
                $this->log('WARN', "IPN: Engine: Engine order amount doesn't match");
                return false;
            }

            $engineOrder->setStatus('PAID');
            $this->em->flush();

            // Notify Engineers
            $message = new \Hip\MandrillBundle\Message();
            $message->setSubject('New Engine Room Order: ' . $engineOrder->getTitle() . '(#' . $engineOrder->getId() . ')');
            $message->setFromEmail('noreply@vocalizr.com');
            $message->setFromName('Vocalizr');
            $message
                ->setTrackOpens(true)
                ->setTrackClicks(true);

            $message->addTo('luke@vocalizr.com');
            $body = $this->container->get('templating')->render('VocalizrAppBundle:Mail:engineNotifyEngineer.html.twig', [
                'order' => $engineOrder,

            ]);
            $message->addGlobalMergeVar('BODY', $body);
            $this->dispatcher->send($message, 'default');

            // Notify User who submitted the order
            $message = new \Hip\MandrillBundle\Message();
            $message->setSubject('Your Engine Room Order has been submitted (' . $engineOrder->getUid() . ')');
            $message->setFromEmail('noreply@vocalizr.com');
            $message->setFromName('Vocalizr');
            $message
                ->setTrackOpens(true)
                ->setTrackClicks(true);

            $message->addTo($engineOrder->getEmail());
            $body = $this->container->get('templating')->render('VocalizrAppBundle:Mail:engineNotifyUser.html.twig', [
                'order' => $engineOrder,

            ]);
            $message->addGlobalMergeVar('BODY', $body);
            $this->dispatcher->send($message, 'default');
        }
    }

    /** @noinspection PhpDocMissingThrowsInspection */

    /**
     * @param array $data
     *
     * @return bool|object|UserSubscription
     */
    public function handleSubscrSignUp($data)
    {
        $em                       = $this->em;
        $subscriptionPlanRepo     = $em->getRepository('VocalizrAppBundle:SubscriptionPlan');
        $userInfoRepo             = $em->getRepository('VocalizrAppBundle:UserInfo');
        $userSubscriptionPlanRepo = $em->getRepository('VocalizrAppBundle:UserSubscription');

//         Make sure subscription plan exists
//        $uniqueKey = $data['item_number'];
        if (!$subscriptionPlan = $subscriptionPlanRepo->findOneBy(['static_key' => 'PRO'])) {
            $this->log('WARN', 'Paypal Transaction IPN: Could not find SubscriptionPlan. Invalid unique key');
            return false;
        }

        // Make sure user exists
        $userUniqueStr = $data['custom'];
        if (!$userInfo = $userInfoRepo->findOneBy(['unique_str' => $userUniqueStr])) {
            $this->log('WARN', 'Paypal Transaction IPN: Could not find UserInfo. Invalid unique str');
            return false;
        }

        $this->setUserToPayPalTransaction($userInfo);

        // Check if subscription exists
        $userSubscription = $userSubscriptionPlanRepo->findOneBy([
            'paypal_subscr_id' => $data['subscr_id'],
        ]);

        // Already exists, ignore
        if ($userSubscription) {
            return false;
        }

        $userSubscription = new UserSubscription();
        $userSubscription->setUserInfo($userInfo);
        $userSubscription->setSubscriptionPlan($subscriptionPlan);
        $userSubscription->setIsActive(true);
        $userSubscription->setPaypalSubscrId($data['subscr_id']);
        $userSubscription->setPaypalAccount($this->container->getParameter('paypal_primary_email_subscriptions'));
        if (array_key_exists('source', $data) && !empty($data['source'])) {
            $userSubscription->setSource((string) $data['source']);
        }

        $userInfo->setSubscriptionPlan($subscriptionPlan);

        /*
        // If mc_amount1 is -- "0.00" - then we are guessing it's a downgrade of membership
        // So they have a free period, seeing as no payment comes through after this, we need to check to make the subscription
        // active straight away
        if (isset($data['mc_amount1']) && $data['mc_amount1'] == "0.00")
        {
            // get current active user subscription
            if (!$activeUserSubscription = $userSubscriptionPlanRepo->getActiveSubscription($userInfo->getId())) {
                $this->log('ERROR', 'Paypal IPN Transaction: Unable to get active subscription for user (' . $userInfo->getId() . ') when signing up');
                return false;
            }
            $activeSubscriptionPlan = $activeUserSubscription['subscription_plan'];


            // Check mc_amount2 as this should equal change price
            $price = $this->calcChangePlanPrice($activeSubscriptionPlan['price'], $subscriptionPlan->getPrice(), $activeUserSubscription['next_payment_date']);
            if ($data['mc_amount2'] != $price['amount']) {
                $this->log('ERROR', 'Paypal IPN Transaction: Downgrade prices do not match: mc_amount2 = ' . $data['mc_amount2'] . ', price calculated: ' . $price['amount']);
                return false;
            }

            // First cancel any active current subscriptions
            $userSubscriptionPlanRepo->cancelActiveSubscriptions();

            $request = array(
                'METHOD' => 'ManageRecurringPaymentsProfileStatus',
                'PROFILEID' => $activeUserSubscription['paypal_subscr_id'],
                'ACTION' => 'Cancel',
                'NOTE' => "Changed Subscription plan",
            );
            $result = $this->_apiRequest($request);

            // Set next payment date
            $userSubscription->setDateCommenced(new \DateTime());
            $userSubscription->setNextPaymentDate($activeUserSubscription['next_payment_date']);
            $userSubscription->setIsActive(true);
        }
         *
         */

        $em->persist($userSubscription);
        $em->flush();

        return $userSubscription;
    }

    public function handleSubscrPayment($data)
    {
        $em                       = $this->em;
        $subscriptionPlanRepo     = $em->getRepository('VocalizrAppBundle:SubscriptionPlan');
        $userInfoRepo             = $em->getRepository('VocalizrAppBundle:UserInfo');
        $userSubscriptionPlanRepo = $em->getRepository('VocalizrAppBundle:UserSubscription');

        if (!$subscriptionPlan = $subscriptionPlanRepo->findOneBy(['static_key' => 'PRO'])) {
            $this->log('WARN', 'Paypal Transaction IPN: Could not find SubscriptionPlan. Invalid unique key');
            return false;
        }

        // Make sure user exists
        $userUniqueStr = $data['custom'];
        if (!$userInfo = $userInfoRepo->findOneBy(['unique_str' => $userUniqueStr])) {
            $this->log('WARN', 'Paypal Transaction IPN: Could not find UserInfo. Invalid unique str');
            return false;
        }

        $this->setUserToPayPalTransaction($userInfo);

        // Check if user subscription exists from paypal_subscr_id
        $userSubscription = $userSubscriptionPlanRepo->findOneBy([
            'paypal_subscr_id' => $data['subscr_id'],
        ]);

        if (!$userSubscription) {
            if (!$userSubscription = $this->handleSubscrSignUp($data)) {
                $this->log('ERROR', 'Paypal IPN Transaction: Unable to find user subscription by paypal_subscr_id');
                return false;
            }
        }

        // Make sure payment status is 'completed' or 'failed'
        if (!isset($data['payment_status']) || !in_array($data['payment_status'], ['Completed', 'Failed'])) {
            $this->log('ERROR', 'Paypal Transaction IPN: Unhandled Paypal Payment Status: ' . $data['payment_status']);
            return false;
        }

        // If payment completed
        if ($data['payment_status'] == 'Completed') {
            // If date commenced is null, set it
            if (!$userSubscription->getDateCommenced()) {
                $userSubscription->setDateCommenced(new \DateTime());
            }

            $lastPaymentDate = new \DateTime();

            if (array_key_exists('payment_date', $data)) {
                $lastPaymentDate = $this->parsePaypalDate($data['payment_date'], $lastPaymentDate);
            }

            $amount = array_key_exists('payment_gross', $data) ? (float) $data['payment_gross'] : 0;

            $this->setLastAndNextPaymentDate($userSubscription, $lastPaymentDate, $amount);

            if (!$amount) {
                $this->log('ERROR', 'Paypal Transaction IPN (messages): No subscription amount was received. Can not determine which message vocalizr should send (fallback to monthly).');
            } elseif (!in_array($amount, $this->getSubscriptionPaymentAmounts())) {
                $this->log('WARN', 'Paypal Transaction IPN (messages): Subscription amount ' . $amount
                    . ' is not equal to any known amount. Can not determine which message vocalizr should send (fallback to monthly).');
            }

            $this->mandrill->sendSubscriptionRenewedMessage($userInfo, $amount, $data['subscr_id']);
            
            if (
                !$userSubscription->getIsActive()
                && isset($data['receiver_email'])
                && $data['receiver_email'] === $this->container->getParameter('paypal_primary_email_subscriptions')
            ) {
                $userSubscription->setPaypalAccount($this->container->getParameter('paypal_primary_email_subscriptions'));
            }
            $userSubscription
                ->setIsActive(true)
                ->setPaypalSubscrId($data['subscr_id'])
            ;

            $em->persist($userSubscription);

            $em->flush();
        } else {
            // if it's failed, dont record amount
            $this->payPalTransaction->setPaymentGross(0);
        }

        $em->flush();

        return true;
    }

    /**
     * @param array $data
     * @param bool  $atPeriodEnd
     *
     * @return bool
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function handleSubscrCancel($data, $atPeriodEnd = true)
    {
        $em                   = $this->em;
        $subscriptionPlanRepo = $em->getRepository('VocalizrAppBundle:SubscriptionPlan');
        $userInfoRepo         = $em->getRepository('VocalizrAppBundle:UserInfo');
        $userSubscriptionRepo = $em->getRepository('VocalizrAppBundle:UserSubscription');

        if (!$subscriptionPlan = $subscriptionPlanRepo->getProPlan()) {
            $this->log('WARN', 'Paypal Transaction IPN: Could not find SubscriptionPlan. Invalid unique key');
            return false;
        }

        // Make sure user exists
        $userUniqueStr = $data['custom'];
        if (!$userInfo = $userInfoRepo->findByUniqueStr($userUniqueStr)) {
            $this->log('WARN', 'Paypal Transaction IPN: Could not find UserInfo. Invalid unique str');
            return false;
        }

        $this->setUserToPayPalTransaction($userInfo);

        if (isset($data['subscr_id'])) {
            $subscriptionId = $data['subscr_id'];
        } elseif (isset($data['recurring_payment_id'])) {
            $subscriptionId = $data['recurring_payment_id'];
        } else {
            throw new \InvalidArgumentException('Subscription id is not specified in IPN');
        }

        // Check if subscription exists
        /** @var UserSubscription $userSubscription */
        $userSubscription = $userSubscriptionRepo->findOneBy([
            'paypal_subscr_id' => $subscriptionId,
        ]);

        if (!$userSubscription) {
            $this->log('WARN', 'Paypal Transaction IPN: Could not cancel subscription as it could not find the paypal subscr_id: ' . $data['subscr_id']);
            return false;
        }

        $amount = self::MONTHLY_PAYMENT_GROSS;

        if (array_key_exists('amount1', $data)) {
            $amount = $data['amount1'];
        } elseif (array_key_exists('amount2', $data)) {
            $amount = $data['amount2'];
        } elseif (array_key_exists('amount3', $data)) {
            $amount = $data['amount3'];
        }

        if ($atPeriodEnd) {
            $lastPaymentDate = $userSubscription->getLastPaymentDate();
            if (!$lastPaymentDate) {
                error_log("Could not correctly cancel subscription at period end as last payment date is not specified. Plan to unsubscribe user after 1 month.");
                $lastPaymentDate = new \DateTime('+1 month');
            }

            // Plan subscription cancel to the end of the billing period.
            $this->setLastAndNextPaymentDate($userSubscription, $lastPaymentDate, $amount);
        } else {
            $userSubscription->setNextPaymentDate(new \DateTime());
        }

        $userSubscription->setDateEnded(new \DateTime());

        $em->flush();

        return true;
    }

    /**
     * @param $data
     *
     * @return bool
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function handleSubscriptionRefund($data)
    {
        $em                       = $this->em;
        $subscriptionPlanRepo     = $em->getRepository('VocalizrAppBundle:SubscriptionPlan');
        $userInfoRepo             = $em->getRepository('VocalizrAppBundle:UserInfo');
        $userSubscriptionPlanRepo = $em->getRepository('VocalizrAppBundle:UserSubscription');

        if (!$subscriptionPlan = $subscriptionPlanRepo->getProPlan()) {
            $this->log('WARN', 'Paypal Transaction IPN: Could not find SubscriptionPlan. Invalid unique key');
            return false;
        }

        // Make sure user exists
        $userUniqueStr = $data['custom'];
        if (!$userInfo = $userInfoRepo->findByUniqueStr($userUniqueStr)) {
            $this->log('WARN', 'Paypal Transaction IPN: Could not find UserInfo. Invalid unique str');
            return false;
        }

        $this->setUserToPayPalTransaction($userInfo);

        // Check if subscription exists
        /** @var UserSubscription $userSubscription */
        $userSubscription = $userSubscriptionPlanRepo->findOneBy([
            'paypal_subscr_id' => $data['subscr_id'],
        ]);

        if (!$userSubscription) {
            $this->log('WARN', 'Paypal Transaction IPN: Could not cancel subscription as it could not find the paypal subscr_id: ' . $data['subscr_id']);
            return false;
        }

        $userSubscription->setIsActive(false);
        $userSubscription->setNextPaymentDate(new \DateTime());
        $userSubscription->setDateEnded(new \DateTime());

        $userInfo->setSubscriptionPlan(null);

        $em->flush();

        return true;
    }

    /**
     * @param array $data
     * @return bool
     */
    private function handleRecurringPaymentFailed($data)
    {
        $userInfoRepo = $this->em->getRepository('VocalizrAppBundle:UserInfo');
        $mailer       = $this->container->get('vocalizr_app.service.mandrill');

        // Make sure user exists
        $userUniqueStr = $data['custom'];

        /** @var UserInfo $userInfo */
        if (!$userInfo = $userInfoRepo->findByUniqueStr($userUniqueStr)) {
            $this->log('WARN', 'Paypal Transaction IPN: Could not find UserInfo. Invalid unique str');
            return false;
        }

        $this->setUserToPayPalTransaction($userInfo);

//        $mailer->sendMessage($userInfo->getEmail(), '');
    }

    /**
     * Get post raw data
     * reading posted data from directly from $_POST causes serialization
     * issues with array data in POST
     * reading raw POST data from input stream instead.
     *
     * @return array
     */
    public function _getRawPostData()
    {
        $raw_post_data  = file_get_contents('php://input');
        $raw_post_array = explode('&', $raw_post_data);
        $myPost         = [];
        foreach ($raw_post_array as $keyval) {
            $keyval = explode('=', $keyval);
            if (count($keyval) == 2) {
                $myPost[$keyval[0]] = urldecode($keyval[1]);
            }
        }

        return $myPost;
    }

    public function log($type, $message)
    {
        if ($this->payPalTransaction) {
            $message = $this->payPalTransaction->getId() . ': ' . $message;
        }
        echo $message;
        error_log($message);
    }

    /**
     * Calculate price difference for changing plan
     *
     * @param int      $currentPrice (in cents)
     * @param int      $newPrice     (in cents)
     * @param DateTime $date         End of subscription / Next payment
     * @param bool     $format
     *
     * @return array
     */
    public function calcChangePlanPrice($currentPrice, $newPrice, $date, $format = true)
    {
        if (is_null($date)) {
            return false;
        }
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, date('m'), date('Y'));

        // ((new plan - current plan) / days in month) * days on new plan
        // Timeleft to next payment
        $d2       = new \DateTime();
        $d1       = $date;
        $datediff = $d1->getTimestamp() - $d2->getTimestamp();
        $days     = floor($datediff / (60 * 60 * 24));

        $daysLeft = $days + 1;

        if ($currentPrice == $newPrice) {
            return false;
        }

        // Upgrade amount
        if ($currentPrice < $newPrice) {
            $totalAmountPerDay = round($currentPrice / $daysInMonth, 2);
            $totalAmountPerDay = (($newPrice - $currentPrice) / $daysInMonth);
            $amount            = number_format($totalAmountPerDay * $daysLeft, 2);
        }
        // Downgrade / credit amount
        else {
            $totalAmountPerDay = round($newPrice / $daysInMonth, 2);
            $totalAmountPerDay = (($currentPrice - $newPrice) / $daysInMonth);
            $amount            = number_format($totalAmountPerDay * $daysLeft, 2);
            $amount            = $newPrice - $amount;
        }

        if ($format) {
            $amount = number_format($amount / 100, 2);
        }

        return ['amount' => $amount, 'days_left' => $daysLeft];
    }

    /**
     * @return float[]
     */
    public static function getSubscriptionPaymentAmounts()
    {
        return [
            self::PROMO_STAYHOME_PAYMENT_GROSS,
            self::MONTHLY_STAYHOME_PAYMENT_GROSS,
            self::YEARLY_PAYMENT_GROSS,
            self::MONTHLY_PAYMENT_GROSS,
            self::MONTHLY_PAYMENT_GROSS_OLD,
            self::YEARLY_PAYMENT_GROSS_OLD,
        ];
    }

    /**
     * @param array $itemData
     * @return mixed|null
     */
    public function getWithdrawIdFromItemData($itemData)
    {
        if (!isset($itemData['unique_id'])) {
            error_log(sprintf(
                'Could not resolve wallet transaction. No unique code provided. There are only these keys: %s',
                join(', ', array_keys($itemData))
            ));
            return false;
        }

        $withdrawAndUserId = explode('_', $itemData['unique_id']);

        return reset($withdrawAndUserId);
    }

    /**
     * @param UserSubscription $userSubscription
     * @param \DateTime        $lastPaymentDate
     * @param float            $amount
     */
    private function setLastAndNextPaymentDate($userSubscription, $lastPaymentDate, $amount)
    {
        $nextPaymentDate = clone $lastPaymentDate;

        switch ($amount) {
            case self::YEARLY_PAYMENT_GROSS:
            case self::YEARLY_PAYMENT_GROSS_OLD:
                $nextPaymentDate->modify('+1 year');
                break;
            case self::PROMO_STAYHOME_PAYMENT_GROSS:
                $nextPaymentDate->modify('+3 month');
                break;
            default:
                $nextPaymentDate->modify('+1 month');
        }

        $userSubscription
            ->setLastPaymentDate($lastPaymentDate)
            ->setNextPaymentDate($nextPaymentDate)
        ;
    }

    /**
     * @param string         $dateString
     * @param \DateTime|null $default
     *
     * @return \DateTime|null
     */
    private function parsePaypalDate($dateString, $default = null)
    {
        $parsedDate = \DateTime::createFromFormat('H:i:s M d, Y T', $dateString);

        if ($parsedDate) {
            return $parsedDate;
        }

        return $default;
    }

    /**
     * @param array $data
     */
    private function extractCustom(&$data)
    {
        if (array_key_exists('custom', $data)) {
            if (($values = json_decode($data['custom'], true)) === null) {
                return;
            }

            foreach ($values as $key => $value) {
                $data[$key] = $value;
            }
        }
    }

    /**
     * @param array $data
     */
    private function handleMassPayment($data)
    {
        $batchData       = [];
        $payoutItemsData = [];

        foreach ($data as $key => $value) {
            $isItemProperty = preg_match('/(.+)_(\d+)$/m', $key, $matches);
            if ($isItemProperty) {
                $itemPropertyKey                               = $matches[1];
                $itemIndex                                     = $matches[2];
                $payoutItemsData[$itemIndex][$itemPropertyKey] = $value;
            } else {
                $batchData[$key] = $value;
            }
        }

        foreach ($payoutItemsData as $payoutItemData) {
            $this->processMassPaymentItem($payoutItemData, $batchData);
        }
    }

    /**
     * @param array $itemData
     * @param array $batchData
     */
    private function processMassPaymentItem($itemData, $batchData)
    {
        $transaction = $this->resolveWalletTransaction($itemData);
        $withdraw    = $this->resolveWithdraw($itemData);

        if (!$transaction || !$withdraw) {
            return;
        }

        $userModel   = $this->container->get('vocalizr_app.model.user_info');

        if ($itemData['status'] == "Failed") {
            if (!isset($itemData['reason_code'])) {
                error_log(sprintf('Payout "%s" has been failed but no reason code was provided!', $itemData['unique_id']));
            }

            $reasonCode = (int)$itemData['reason_code'];

            if (!in_array($reasonCode, [1001, 1003, 3015, 3047, 4001, 4002, 9302, 14765, 14766])) {
                error_log(sprintf(
                    'Failed payout %s has reason code %d which is non-refundable',
                    $itemData['unique_id'],
                    $reasonCode
                ));
            } else {
                error_log(sprintf(
                    'Failed payout %s has reason code %d. Refunding money to user\'s wallet.',
                    $itemData['unique_id'],
                    $reasonCode
                ));
            }

            $refundTransaction = $userModel->createWalletTransaction(
                $transaction->getUserInfo(),
                -$transaction->getAmount(),
                UserWalletTransaction::TYPE_WITHDRAW_REFUND
            );

            $refundTransaction->setDescription('Failed withdrawal funds return - Contact help@vocalizr.com');

            $withdraw->setStatus(UserWithdraw::WITHDRAW_STATUS_CANCELLED);

            $userModel->updateObject($refundTransaction);
            $userModel->updateObject($withdraw);
        }
    }

    /**
     * @param array $itemData
     * @return UserWalletTransaction|null
     */
    private function resolveWalletTransaction($itemData)
    {
        $customId = UserWalletTransaction::TYPE_WITHDRAW . '_' . $this->getWithdrawIdFromItemData($itemData);

        /** @var UserWalletTransaction $transaction */
        $transaction = $this->em->getRepository('VocalizrAppBundle:UserWalletTransaction')
            ->findOneBy(['custom_id' => $customId])
        ;

        if (!$transaction) {
            error_log('Transaction was not found for payout ' . $itemData['unique_id']);
        }

        return $transaction;
    }

    /**
     * @param array $itemData
     * @return UserWithdraw|null
     */
    private function resolveWithdraw($itemData)
    {
        $withdrawId = $this->getWithdrawIdFromItemData($itemData);

        if (!$withdrawId) {
            return null;
        }

        /** @var UserWithdraw|null $withdraw */
        $withdraw = $this->em->getRepository('VocalizrAppBundle:UserWithdraw')
            ->find($withdrawId);

        if (!$withdraw) {
            error_log('Withdraw was not found for payout ' . $itemData['unique_id']);
        }

        return $withdraw;
    }

    /**
     * @param UserInfo|null $userInfo
     */
    private function setUserToPayPalTransaction($userInfo)
    {
        if ($this->payPalTransaction && $userInfo && ($userInfo instanceof UserInfo)) {
            $this->payPalTransaction->setUserInfo($userInfo);
        }
    }

    /**
     * Verify IPN Request and record transaction
     *
     * @param array $data
     *
     * @return PayPalTransaction
     */
    private function _verifyAndRecordIpnRequest($data)
    {
        $em = $this->em;

        // read the post from PayPal system and add 'cmd'
        $req = 'cmd=_notify-validate';
        if (function_exists('get_magic_quotes_gpc')) {
            $get_magic_quotes_exists = true;
        }
        foreach ($data as $key => $value) {
            if ($get_magic_quotes_exists == true && get_magic_quotes_gpc() == 1) {
                $value = urlencode(stripslashes($value));
            } else {
                $value = urlencode($value);
            }
            $req .= "&$key=$value";
        }

        // STEP 2: Post IPN data back to paypal to validate
        $ch = curl_init($this->url);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Connection: Close']);

        // In wamp like environments that do not come bundled with root authority certificates,
        // please download 'cacert.pem' from "http://curl.haxx.se/docs/caextract.html" and set the directory path
        // of the certificate as shown below.
        curl_setopt($ch, CURLOPT_CAINFO, $this->container->get('kernel')->getRootDir() . '/cacert.pem');
        if (!($res = curl_exec($ch))) {
            echo 'Got ' . curl_error($ch) . ' when processing IPN data';
            curl_close($ch);
            exit;
        }
        curl_close($ch);

        // Record IPN request and result
        $ppt = new PayPalTransaction();
        if (isset($data['txn_id'])) {
            $ppt->setTxnId($data['txn_id']);
        }
        if (isset($data['subscr_id'])) {
            $ppt->setSubscrId($data['subscr_id']);
        }
        $ppt->setIpnTrackId($data['ipn_track_id']);
        $ppt->setPayerEmail($data['payer_email']);
        if (isset($data['mc_gross'])) {
            $ppt->setPaymentGross($data['mc_gross']);
        }
        if (isset($data['txn_type'])) {
            $ppt->setTxnType($data['txn_type']);
        } elseif (isset($data['payment_status']) && $data['payment_status'] == 'Refunded') {
            $ppt->setTxnType($data['payment_status']);
        }
        $ppt->setRaw(serialize($data));
        if (isset($data['item_name'])) {
            $ppt->setItemName($data['item_name']);
        }

        // STEP 3: Inspect IPN validation result and act accordingly
        // If not a verified result, ignore
        if (!(strcmp($res, 'VERIFIED') == 0)) {
            $ppt->setVerified(false);
            $em->persist($ppt);
            $em->flush();
            return false;
        }
        $ppt->setVerified(true);
        if (isset($data['mc_gross'])) {
            $amount = $data['mc_gross'] - $data['mc_fee'];
            $ppt->setAmount($amount);
        }
        $em->persist($ppt);
        $em->flush();

        return $ppt;
    }

    /**
     * Send Request to Paypal API
     *
     * @param array $request
     *
     * @return array
     */
    private function _apiRequest(array $request)
    {
        /**
         * CANCEL SUBSCRIPTION
         * METHOD: ManageRecurringPaymentsProfileStatus
         * PROFILEID: 14 single-byte alphanumeric characters
         * ACTION: Cancel
         * NOTE: Optional
         */
        $headers = [
            'Content-Type: application/json',
            'X-PAYPAL-SECURITY-USERID: ' . $this->apiUsername,
            'X-PAYPAL-SECURITY-PASSWORD: ' . $this->apiPass,
            'X-PAYPAL-SECURITY-SIGNATURE: ' . $this->apiSignature,
            'X-PAYPAL-REQUEST-DATA-FORMAT: JSON',
            'X-PAYPAL-RESPONSE-DATA-FORMAT: JSON',
            'Connection: Close',
        ];

        //$ch = curl_init($this->apiUrl);

        $api_request = 'USER=' . urlencode($this->apiUsername)
            . '&PWD=' . urlencode($this->apiPass)
            . '&SIGNATURE=' . urlencode($this->apiSignature)
            . '&VERSION=76.0'
            . '&METHOD=' . urlencode($request['METHOD'])
            . '&PROFILEID=' . urlencode($request['PROFILEID'])
            . '&ACTION=' . urlencode($request['ACTION'])
            . '&NOTE=' . urlencode($request['NOTE']);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://api-3t.sandbox.paypal.com/nvp'); // For live transactions, change to 'https://api-3t.paypal.com/nvp'
        curl_setopt($ch, CURLOPT_VERBOSE, 1);

        // Uncomment these to turn off server and peer verification
        // curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, FALSE );
        // curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, FALSE );
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_CAINFO, $this->container->get('kernel')->getRootDir() . '/cacert.pem');
        //curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Set the API parameters for this transaction
        curl_setopt($ch, CURLOPT_POSTFIELDS, $api_request);

        // Request response from PayPal
        $response = curl_exec($ch);

        // If no response was received from PayPal there is no point parsing the response
        if (!$response) {
            die('Calling PayPal to change_subscription_status failed: ' . curl_error($ch) . '(' . curl_errno($ch) . ')');
        }

        curl_close($ch);

        // An associative array is more usable than a parameter string
        parse_str($response, $parsed_response);

        return $parsed_response;
    }
}