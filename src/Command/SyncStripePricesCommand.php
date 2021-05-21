<?php

namespace App\Command;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Entity\SubscriptionPlan;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class SyncStripePricesCommand
 * @package App\Command
 */
class SyncStripePricesCommand extends Command
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        $this->container = $container;
    }

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
        $container = $this->container;

        /** @var EntityManager $em */
        $em     = $container->get('doctrine.orm.entity_manager');
        $stripe = $container->get('vocalizr_app.stripe_manager');
        $config = $container->get('vocalizr_app.stripe_configuration_provider');

        $plans = [];
        foreach ([SubscriptionPlan::PLAN_FREE, SubscriptionPlan::PLAN_PRO] as $planStaticKey) {
            $plans[$planStaticKey] = $em->getRepository('App:SubscriptionPlan')->getByStaticKey(
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

        return 1;
    }
}