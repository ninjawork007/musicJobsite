<?php

namespace App\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;
use App\Entity\UserReview;
use App\Model\UserInfoModel;

class UserReviewListener
{
    /**
     * @var object|UserInfoModel
     */
    private $userModel;

    /**
     * UserReviewListener constructor.
     * @param ContainerInterface $container
     */
    public function __construct($container)
    {
        $this->container = $container;
    }

    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $em     = $args->getEntityManager();

        // Update avg user rating
        if ($entity instanceof UserReview) {
            $this->userModel = $this->container->get('vocalizr_app.model.user_info');
            // Set avg rating for this user review
            $ratingTotal = $entity->getQualityOfWork() + $entity->getCommunication()
                    + $entity->getProfessionalism() + $entity->getWorkWithAgain();

            $avg = $ratingTotal / 4;
            $entity->setRating(number_format($avg, 1));

            // Now update user info avg rating for all ratings
            $userInfo = $entity->getUserInfo();

            if (!$userInfo->getUserReviews()->contains($entity)) {
                $userInfo->addUserReview($entity);
            }

            $this->userModel->recalculateUserRating($userInfo);

            $em->persist($userInfo);
        }
    }
}