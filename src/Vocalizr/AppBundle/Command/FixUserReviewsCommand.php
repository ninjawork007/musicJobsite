<?php

namespace Vocalizr\AppBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Vocalizr\AppBundle\Entity\UserInfo;
use Vocalizr\AppBundle\Helper\UserPagerTrait;

/**
 * Class FixUserReviewsCommand
 * @package Vocalizr\AppBundle\Command
 */
class FixUserReviewsCommand extends ContainerAwareCommand
{
    use UserPagerTrait;

    /**
     * @var EntityManager
     */
    private $em;

    protected function configure()
    {
        $this->setName('vocalizr:fix-user-reviews');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $this->em = $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $userModel = $this->getContainer()->get('vocalizr_app.model.user_info');

        $em->beginTransaction();

        $userPageGenerator = $this->getPageGenerator($em, 10);
        foreach ($userPageGenerator as $page) {
            foreach ($page as $user) {
                $stringFormat = "Total: %.2f(%d) Vocalist: %.2f(%d) Producer: %.2f(%d) Employer: %.2f(%d)";
                $oldString = sprintf(
                    $stringFormat,
                    $user->getRating(),
                    $user->getRatedCount(),
                    $user->getVocalistRating(),
                    $user->getVocalistRatedCount(),
                    $user->getProducerRating(),
                    $user->getProducerRatedCount(),
                    $user->getEmployerRating(),
                    $user->getEmployerRatedCount()
                );

                $userModel->recalculateUserRating($user);

                $newString = sprintf(
                    $stringFormat,
                    $user->getRating(),
                    $user->getRatedCount(),
                    $user->getVocalistRating(),
                    $user->getVocalistRatedCount(),
                    $user->getProducerRating(),
                    $user->getProducerRatedCount(),
                    $user->getEmployerRating(),
                    $user->getEmployerRatedCount()
                );

                if ($oldString != $newString) {
                    $output->writeln(sprintf('User "%s"', $user->getUsernameOrDisplayName()));
                    $output->writeln(sprintf("Old. %s\nNew. %s", $oldString, $newString));
                }

                $em->persist($user);
            }

            $em->flush();
        }

        $em->commit();
    }
}