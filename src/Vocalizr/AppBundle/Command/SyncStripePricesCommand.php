<?php

namespace Vocalizr\AppBundle\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Vocalizr\AppBundle\Entity\SubscriptionPlan;

/**
 * Class SyncStripePricesCommand
 * @package Vocalizr\AppBundle\Command
 */
class SyncStripePricesCommand extends ContainerAwareCommand
{
    public static $featurePlanAccessorMap = [
        'restrict_to_preferences' => 'ProjectRestrictFee',
        'to_favorites'            => 'ProjectFavoritesFee',
        'highlight'               => 'ProjectHighlightFee',
        'messaging'               => 'ProjectMessagingFee',
        'featured'                => 'ProjectFeatureFee',
        'lock_to_cert'            => 'ProjectLockToCertFee',
    ];

    protected function configure()
    {
        $this->setName('vocalizr:stripe:sync-prices');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();

        /** @var EntityManager $em */
        $em     = $container->get('doctrine.orm.entity_manager');
        $stripe = $container->get('vocalizr_app.stripe_manager');
        $config = $container->get('vocalizr_app.stripe_configuration_provider');

        $plans = [];
        foreach ([SubscriptionPlan::PLAN_FREE, SubscriptionPlan::PLAN_PRO] as $planStaticKey) {
            $plans[$planStaticKey] = $em->getRepository('VocalizrAppBundle:SubscriptionPlan')->getByStaticKey(
                $planStaticKey,
                Query::HYDRATE_OBJECT
            );
        }

        $changed = 0;

        foreach (self::$featurePlanAccessorMap as $feature => $accessor) {
            $output->writeln('Feature "' . $feature . '". ');
            foreach ($plans as $key => $plan) {
                $priceId = $config->getProductPriceId($feature, $key);
                if ($priceId) {
                    $remote = $stripe->call('/prices/' . $priceId, [], [], 'GET')['unit_amount'];
                } else {
                    $remote = null;
                }

                $local = $plan->{'get' . $accessor}();

                $output->writeln(sprintf(
                    "\t%s (%s): remote: %d, local: %d.",
                    $key,
                    $priceId ? $priceId : '-',
                    $remote ? $remote : 0,
                    $local ? $local : 0
                ));

                if ($remote != $local) {
                    $changed++;
                }

                $plan->{'set' . $accessor}($remote);
            }

        }

        $em->flush();

        if ($changed) {
            $output->writeln('Changed ' . $changed . ' feature prices.');
        } else {
            $output->writeln('Feature prices are already in sync.');
        }

    }
}