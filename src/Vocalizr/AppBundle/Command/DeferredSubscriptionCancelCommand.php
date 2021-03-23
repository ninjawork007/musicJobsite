<?php

namespace Vocalizr\AppBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Vocalizr\AppBundle\Repository\UserSubscriptionRepository;

/**
 * Class DeferredSubscriptionCancelCommand
 *
 * @package Vocalizr\AppBundle\Command
 */
class DeferredSubscriptionCancelCommand extends ContainerAwareCommand
{
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
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        /** @var UserSubscriptionRepository $subscriptionRepo */
        $subscriptionRepo = $em->getRepository('VocalizrAppBundle:UserSubscription');

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
    }
}