<?php

namespace Vocalizr\AppBundle\Model;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Vocalizr\AppBundle\Entity\Project;
use Vocalizr\AppBundle\Entity\ProjectAsset;
use Vocalizr\AppBundle\Entity\ProjectEscrow;
use Vocalizr\AppBundle\Entity\ProjectFeed;
use Vocalizr\AppBundle\Entity\UserInfo;
use Vocalizr\AppBundle\Entity\UserWalletTransaction;
use Vocalizr\AppBundle\Exception\NotEnoughMoneyException;
use Vocalizr\AppBundle\Service\MandrillService;
use Vocalizr\AppBundle\Service\PayPalApiService;
use Vocalizr\AppBundle\Service\ProjectPriceCalculator;
use Vocalizr\AppBundle\Service\StripeManager;

/**
 * Class ProjectModel
 * @package Vocalizr\AppBundle\Model
 */
class ProjectModel extends Model
{
    /**
     * @var MandrillService
     */
    private $mandrillService;
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var ProjectPriceCalculator
     */
    private $priceCalculator;
    /**
     * @var UserWalletTransactionModel
     */
    private $walletTransactionModel;
    /**
     * @var StripeManager
     */
    private $stripeManager;
    /**
     * @var PayPalApiService
     */
    private $paypalApiService;

    /**
     * ProjectModel constructor.
     *
     * @param MandrillService $mandrillService
     * @param RouterInterface $router
     * @param ProjectPriceCalculator $priceCalculator
     * @param StripeManager $stripeManager
     * @param UserWalletTransactionModel $walletTransactionModel
     */
    public function __construct(
        MandrillService $mandrillService,
        RouterInterface $router,
        ProjectPriceCalculator $priceCalculator,
        UserWalletTransactionModel $walletTransactionModel,
        StripeManager $stripeManager,
        PayPalApiService $payPalApiService
    ) {
        $this->router          = $router;
        $this->mandrillService = $mandrillService;
        $this->priceCalculator = $priceCalculator;
        $this->walletTransactionModel = $walletTransactionModel;
        $this->stripeManager = $stripeManager;
        $this->paypalApiService = $payPalApiService;
    }

    /**
     * @param Project $project
     * @param string $upgradeKey
     * @param string|int $priceKey
     * @throws \Exception
     */
    public function applyProjectUpgradeAfterPayment(Project $project, $upgradeKey, $priceKey)
    {
        if ($upgradeKey === 'extend_contest') {
            $currentlyExtendedDays = 0;

            if ($project->getDaysExtended()) {
                $currentlyExtendedDays += $project->getDaysExtended();
            }

            if ($currentlyExtendedDays + $priceKey > 15) {
                throw new \Exception('Project already extended to the limit.');
            }

            if ($project->getProjectType() !== Project::PROJECT_TYPE_CONTEST) {
                throw new \InvalidArgumentException('Project is not a contest.');
            }

            if (!in_array($priceKey, [5, 10, 15])) {
                throw new \InvalidArgumentException('Invalid contest extension length (in days): ' . $priceKey);
            }

            $project->setDaysExtended($priceKey + $currentlyExtendedDays);
            $newBidsDue = clone $project->getBidsDue();
            $project->setBidsDue($newBidsDue->modify('+ ' . $priceKey . ' days'));
        }

        $this->updateObject($project);
    }

    /**
     * @param Project $project
     * @param ProjectAsset[] $projectAssets
     */
    public function notifyOwnerAssetsUploaded(Project $project, $projectAssets)
    {
        $projectAssetsFeedData = [];

        foreach ($projectAssets as $projectAsset) {
            $projectAssetsFeedData[] = [
                'slug'  => $projectAsset->getSlug(),
                'title' => $projectAsset->getTitle(),
            ];
        }

        $toUser = $project->getUserInfo();

        $this->mandrillService->sendMessage($toUser->getEmail(), null, 'project-assets-uploaded', [
            'USER'         => $toUser->getUsernameOrFirstName(),
            'FROMUSER'     => $project->getEmployeeUserInfo()->getUsernameOrDisplayName(),
            'PROJECTTITLE' => $project->getTitle(),
            'PROJECTURL'   => $this->router->generate('project_studio', [
                'uuid' => $project->getUuid(),
            ], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        $feedObject = $this->getProjectFeed($project, 'ProjectAsset', [
            'count'  => count($projectAssets),
            'assets' => $projectAssetsFeedData,
        ]);

        $this->updateObject($feedObject);
    }

    /**
     * @param Project $project
     * @param int $paymentAmountCents
     * @throws NotEnoughMoneyException
     */
    public function processPublicationPayment(Project $project, $paymentAmountCents, $upgradesAmountCents = 0)
    {
        if ($project->getPaymentStatus() !== Project::PAYMENT_STATUS_PENDING) {
            return;
        }

        $user = $project->getUserInfo();

        $calculatedPrices = $this->priceCalculator->getCalculatedPrices(
            $project->getUserInfo()->isSubscribed() ? 'PRO' : 'FREE',
            $project
        );

        if ($user->getWallet() < $paymentAmountCents + $upgradesAmountCents) {
            throw new NotEnoughMoneyException();
        }

        $fees = ($upgradesAmountCents + $calculatedPrices['features_price']) * 100;

        $project->setFees($fees);

        $subscriptionPlan = $this->em->getRepository('VocalizrAppBundle:SubscriptionPlan')
            ->getActiveSubscription($project->getUserInfo()->getId());

        $amount = max($project->getBudgetFrom(), $project->getBudgetTo()) * 100;

        if ($project->getProjectType() === Project::PROJECT_TYPE_CONTEST) {
            $escrow = (new ProjectEscrow())
                ->setFee($calculatedPrices['vocalizr_fee'] * 100)
                ->setAmount($amount)
                ->setUserInfo($user)
            ;

            $project->setProjectEscrow($escrow);
            $this->em->persist($escrow);

            $this->em->persist(
                $this->walletTransactionModel->create($user, -$amount, null, 'Escrow payment to contest {project}', [
                    'projectTitle' => $project->getTitle(),
                    'projectUuid'  => $project->getUuid(),
                    'projectType'  => Project::PROJECT_TYPE_CONTEST,
            ]));
        }

        if ($calculatedPrices['vocalizr_fee']) {
            $this->em->persist($this->walletTransactionModel->create(
                $user,
                -$calculatedPrices['vocalizr_fee'] * 100,
                null,
                'Platform commission fee for {project}',
                [
                    'projectTitle' => $project->getTitle(),
                    'projectUuid'  => $project->getUuid(),
                ]
            ));
        }

        if ($fees > 0) {
            $this->em->getRepository('VocalizrAppBundle:ProjectUpgrade')
                ->recordUpgrades($project, $subscriptionPlan);

            $this->em->persist(
                $this->walletTransactionModel->create($user, -$fees, null, 'Upgrade charges for contest {project}', [
                    'projectTitle' => $project->getTitle(),
                    'projectUuid'  => $project->getUuid(),
                    'projectType'  => Project::PROJECT_TYPE_CONTEST,
                ])
            );
        }

        $project->setPaymentStatus(Project::PAYMENT_STATUS_PAID);
    }

    /**
     * Check if logged in user meets project preferences
     * return false if they don't
     *
     * @TODO Need to recode this to be more efficent
     *
     * @param UserInfo $user
     * @param Project $project
     *
     * @return array - array of false, indexed by failed constraint keys if any.
     */
    public function getUserMeetProjectPreferencesArray(UserInfo $user, Project $project)
    {
        $matching = [];

        // Check gender
        if (!is_null($project->getGender())) {
            // If gender doesn't match logged in user
            $gender = $user->getGender() == 'm' ? 'male' : 'female';
            if ($project->getGender() != $gender) {
                $matching['gender'] = false;
            }
        }

        // Check looking for
        if (!is_null($project->getLookingFor())) {
            if ($project->getLookingFor() == 'producer' && !$user->getIsProducer()) {
                $matching['lookingFor'] = false;
            } elseif ($project->getLookingFor() == 'vocalist' && !$user->getIsVocalist()) {
                $matching['lookingFor'] = false;
            }
        }

        if ($project->getProRequired() && !$user->getIsCertified()) {
            $matching['certified'] = false;
        }

        if ($project->getRestrictToPreferences()) {
            // Check studio access
            if ($project->getStudioAccess() && !$user->getStudioAccess()) {
                $matching['studioAccess'] = false;
            }

            // Check if user has at least one vocal style
            $projectVocalStyles = $project->getVocalStyles();

            if (count($projectVocalStyles) > 0) {
                $found = false;
                foreach ($projectVocalStyles as $vocalStyle) {
                    // Now check against user vocal styles
                    $userVocalStyles = $user->getUserVocalStyles();
                    foreach ($userVocalStyles as $userVocalStyle) {
                        if ($userVocalStyle->getVocalStyle() == $vocalStyle) {
                            $found = true;
                            break;
                        }
                    }
                    if ($found) {
                        break;
                    }
                }

                if (!$found) {
                    $matching['vocalStyle'] = false;
                }
            }
        }

        return $matching;
    }

    /**
     * @param Project $project
     */
    public function deactivateProject(Project $project)
    {
        $escrow = $project->getProjectEscrow();
        $project
            ->setIsActive(false)
            ->setFullyRefunded(true)
        ;

        if (Project::PROJECT_TYPE_CONTEST === $project->getProjectType()) {
            /** @var UserWalletTransaction $walletTransaction */
            $paymentSessionData = $this->em->getRepository('VocalizrAppBundle:PaymentSessionData')
                ->findPaymentSessionDataByProjectAndCharge($project);

            if (!$paymentSessionData && !$project->getPaypalTransaction()) {
                $uwt = $this->walletTransactionModel->create(
                    $project->getUserInfo(),
                    $project->getBudgetFrom() * 100,
                    null,
                    'Refund for cancelled Contest {project}',
                    [
                        'projectTitle' => $project->getTitle(),
                        'projectUuid'  => $project->getUuid(),
                        'projectType'  => Project::PROJECT_TYPE_CONTEST,
                    ]
                );
                $this->em->persist($uwt);
            } else {
                if (isset($paymentSessionData) && $paymentSessionData->getStripeCharge() && $paymentSessionData->getStripeCharge()->getData()) {
                    $chargeData = json_decode($paymentSessionData->getStripeCharge()->getData(), true);
                    $refundResponseData = $this->stripeManager->getRefundContest($chargeData['data']['object']['id'], $project->getBudgetFrom() * 100);
                    if (!$refundResponseData || $refundResponseData['status'] != 'succeeded') {
                        return false;
                    }
                } elseif ($project->getPaypalTransaction()) {
                    $this->paypalApiService->refundForContest($project->getPaypalTransaction()->getTxnId(), $project->getBudgetFrom());
                }
            }


            $escrow
                ->setRefunded(true)
                ->setReleasedDate(new \DateTime())
            ;

        }

        $this->em->flush();

        // delete from activity
        $this->em->getRepository('VocalizrAppBundle:VocalizrActivity')->deleteForProject($project);

        // Delete any messages related to the gig.
        $this->em->getRepository('VocalizrAppBundle:MessageThread')->deleteThreadsForGig($project);

        return true;
    }

    /**
     * @param Project $project
     * @param string $objectType
     * @param array $data
     * @param bool $fromEmployee
     * @param null $objectId
     * @return ProjectFeed
     */
    private function getProjectFeed(Project $project, $objectType, $data = [], $fromEmployee = true, $objectId = null)
    {
        $pf = new ProjectFeed();

        $pf
            ->setProject($project)
            ->setData(json_encode($data))
            ->setObjectId($objectId)
            ->setObjectType($objectType)
        ;

        if ($fromEmployee) {
            $pf
                ->setFromUserInfo($project->getEmployeeUserInfo())
                ->setUserInfo($project->getUserInfo())
            ;
        } else {
            $pf
                ->setFromUserInfo($project->getUserInfo())
                ->setUserInfo($project->getEmployeeUserInfo())
            ;
        }

        return $pf;
    }

    protected function getEntityName()
    {
        return 'VocalizrAppBundle:Project';
    }
}