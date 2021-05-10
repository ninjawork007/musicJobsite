<?php

namespace App\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Repository\UserSubscriptionRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DeferredSubscriptionCancelCommand
 *
 * @package App\Command
 */
class DeferredSubscriptionCancelCommand extends Command
{
    private $container;

    /**
     * DeferredSubscriptionCancelCommand constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        $this->container = $container;
    }

    protected function configure()
    {
        $this->setName('vocalizr:cancel-expired-subscriptions');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->container->get('doctrine.orm.entity_manager');

        /** @var UserSubscriptionRepository $subscriptionRepo */
        $subscriptionRepo = $em->getRepository('App:UserSubscription');

        $subscriptions = $subscriptionRepo->getExpiredCancelledSubscriptions();

        $output->writeln('Script is about to cancel ' . count($subscriptions) . ' subscriptions.');

        foreach ($subscriptions as $subscription) {
            $user = $subscription->getUserInfo();

            $user->setSubscriptionPlan(null);
            $subscription->setIsActive(false);

            $em->persist($user);
            $em->persist($subscription);

            $output->writeln('The subscription for user ' . $user->getEmail() . ' has been cancelled.');
        }

        $em->flush();

        $output->writeln('All done.');

        return 1;
    }
}