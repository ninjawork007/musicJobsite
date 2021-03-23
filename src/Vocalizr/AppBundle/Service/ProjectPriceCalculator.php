<?php

namespace Vocalizr\AppBundle\Service;

use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Yaml\Parser;
use Vocalizr\AppBundle\Entity\Project;

/**
 * Class ProjectPriceCalculator
 * @package Vocalizr\AppBundle\Service
 */
class ProjectPriceCalculator
{
    public static $featureProjectAccessorMap = [
        'restrict_to_preferences',
        'to_favorites',
        'highlight',
        'messaging',
        'featured',
        'lock_to_cert' => 'getProRequired',
    ];

    /**
     * @var ObjectManager
     */
    private $em;

    /**
     * @var array
     */
    private $featurePrices;

    /**
     * ProjectPriceCalculator constructor.
     * @param ObjectManager $em
     */
    public function __construct(ObjectManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param Project $project
     * @param bool $onlyChecked
     * @return string[]
     */
    public function getFeaturesList(Project $project, $onlyChecked = false)
    {
        $boolFeatures = self::$featureProjectAccessorMap;
        $featuresList = [];

        foreach ($boolFeatures as $feature => $getter) {
            if (is_numeric($feature)) {
                $feature = $getter;
                $getter  = 'get' . join(array_map('ucfirst', explode('_', $feature)));
            }

            $featuresList[$feature] = $project->{$getter}();
        }

        $featuresList['publish_type'] = ($project->getPublishType() === Project::PUBLISH_PRIVATE);

        if ($onlyChecked) {
            return array_filter($featuresList);
        }

        return $featuresList;
    }

    /**
     * @param string $planKey
     * @param Project $project
     * @param bool $onlyNotEmpty
     * @return array
     */
    public function getCalculatedPrices($planKey, Project $project, $onlyNotEmpty = false)
    {
        $user   = $project->getUserInfo();
        $wallet = $user->getWallet() / 100;

        $vocalizrFee = $transactionFee = $featuresPrice = $projectBudget = 0;

        if ($project->getProjectType() === Project::PROJECT_TYPE_CONTEST) {
            $projectBudget     = max($project->getBudgetFrom(), $project->getBudgetTo());
            $commissionPercent = $this->getFeaturePrices($planKey)['project_percent_added'];
            $vocalizrFee       = $projectBudget * ($commissionPercent / 100);
        }

        $featuresPrice = array_sum($this->getFeaturePricesForProject($project, $planKey));

        $transactionAmount = max($projectBudget + $vocalizrFee + $featuresPrice - $wallet, 0);

        if ($transactionAmount) {
            $transactionFee = ($transactionAmount) * 0.036 + 0.3;
        }

        $prices = [
            'project_budget'  => $projectBudget,
            'features_price'  => $featuresPrice,
            'transaction_fee' => $transactionFee,
            'vocalizr_fee'    => $vocalizrFee,
        ];

        foreach ($prices as $item => $price) {
            $prices[$item] = round($price, 2, PHP_ROUND_HALF_DOWN);
        }

        if ($onlyNotEmpty) {
            $prices = array_filter($prices);
        }

        return $prices;
    }

    /**
     * @param string $planKey FREE|PRO
     * @param Project $project
     * @return array[]
     */
    public function getPaymentSplitData($planKey, Project $project)
    {
        $stripeData = $walletData = [
            'product_prices' => [],
            'total'          => 0,
        ];
        $partialData = [
            'product_key'   => '',
            'product_price' => 0,
            'wallet_amount' => 0,
            'stripe_amount' => 0,
        ];

        $user = $project->getUserInfo();

        $calculatedPrices = $this->getCalculatedPrices($planKey, $project);

        $walletRemaining = $user->getWallet() / 100;
        // Features, sorted in price ascending order + budget and fee.
        $pricesByFeature = array_merge($this->getFeaturePricesForProject($project, $planKey), [
            'contest_budget' => $calculatedPrices['project_budget'],
            'vocalizr_fee'   => $calculatedPrices['vocalizr_fee'],
        ]);
        $hasPartialFeature  = false;
        foreach ($pricesByFeature as $feature => $price) {
            // Adds features to wallet's bag while wallet is not empty, then to stripe's bag.
            // Once there is money in wallet, but not enough for a feature,
            // spread feature's price between wallet and stripe.
            if ($walletRemaining - $price >= 0) {
                $walletData['product_prices'][$feature] = $price;
                $walletData['total'] += $price;
                $walletRemaining     -= $price;
            } elseif (!$hasPartialFeature && $walletRemaining > 0) {
                // Spread zero or one feature between wallet and stripe
                $hasPartialFeature = true;
                $partialData['product_key']   = $feature;
                $partialData['product_price'] = $price;
                $partialData['wallet_amount'] = $walletRemaining;
                $partialData['stripe_amount'] = $price - $walletRemaining;
                $walletRemaining = 0;
            } else {
                $stripeData['product_prices'][$feature] = $price;
                $stripeData['total'] += $price;
            }
        }

        if ($calculatedPrices['transaction_fee']) {
            $stripeData['product_prices']['transaction_fee'] = $calculatedPrices['transaction_fee'];
            $stripeData['total'] += $calculatedPrices['transaction_fee'];
        }

        return [
            'wallet'  => $walletData,
            'stripe'  => $stripeData,
            'partial' => $partialData,
        ];
    }

    /**
     * @param Project $project
     * @param string $planKey - FREE|PRO
     * @return array
     */
    public function getFeaturePricesForProject(Project $project, $planKey)
    {
        $featuresPrices  = [];
        $featurePriceMap = $this->getFeaturePriceMap($planKey);

        foreach (array_keys($this->getFeaturesList($project, true)) as $feature) {
            $featuresPrices[$feature] = $featurePriceMap[$feature];
        }

        asort($featuresPrices, SORT_ASC);

        return $featuresPrices;
    }

    /**
     * @param string $planKey
     * @param Project $project
     * @return float|int
     */
    public function getProjectTotalPrice($planKey, Project $project)
    {
        return array_sum($this->getCalculatedPrices($planKey, $project));
    }

    /**
     * @param string $planKey - FREE|PRO
     * @return array
     */
    public function getFeaturePriceMap($planKey)
    {
        $prices = $this->getFeaturePrices($planKey);

        $map = [
            'restrict_to_preferences' => $prices['restrict'],
            'to_favorites'            => $prices['favorites'],
            'publish_type'            => $prices['private'],
            'highlight'               => $prices['highlight'],
            'messaging'               => $prices['messaging'],
            'featured'                => $prices['feature'],
            'lock_to_cert'            => $prices['lock_to_cert'],
        ];

        foreach ($map as $feature => $cents) {
            $map[$feature] = $cents / 100;
        }

        return $map;
    }

    /**
     * @param string $planKey
     * @return array
     */
    private function getFeaturePrices($planKey)
    {
        if (!$this->featurePrices) {
            $this->featurePrices = $this->em->getRepository('VocalizrAppBundle:SubscriptionPlan')->getFeaturePrices();
        }

        return $this->featurePrices[$planKey];
    }
}