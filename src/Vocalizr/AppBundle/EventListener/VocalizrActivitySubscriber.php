<?php

namespace Vocalizr\AppBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
// for doctrine 2.4: Doctrine\Common\Persistence\Event\LifecycleEventArgs;

use Vocalizr\AppBundle\Entity\Project;
use Vocalizr\AppBundle\Entity\UserInfo;
use Vocalizr\AppBundle\Entity\VocalizrActivity;

class VocalizrActivitySubscriber implements EventSubscriber
{
    public function getSubscribedEvents()
    {
        return [
            'postPersist',
            'postUpdate',
            'preUpdate',
        ];
    }

    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getEntity();
        $em     = $args->getEntityManager();
    }

    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $em     = $args->getEntityManager();

        // handle user activity
        if ($entity instanceof UserInfo) {
            $changeSet = $args->getEntityManager()->getUnitOfWork()->getEntityChangeSet($entity);

            // If the user becomes active
            // Add activity
            if (isset($changeSet['is_active']) && $changeSet['is_active'][1]) {
                $activityMessage = new VocalizrActivity();
                $activityMessage->setActivityType(VocalizrActivity::ACTIVITY_TYPE_NEW_MEMBER);
                $jsonData              = [];
                $jsonData['user_info'] = ['id' => $userInfo->getId(),
                    'username'                 => $entity->getUsername(),
                    'gender'                   => $entity->getGender(),
                    'is_vocalist'              => $entity->getIsVocalist(),
                    'is_producer'              => $entity->getIsProducer(), ];
                $activityMessage->setData(json_encode($jsonData));
                $em->persist($activityMessage);
            }
        }

        // If project has been updated
        if ($entity instanceof Project) {
            $changeSet = $args->getEntityManager()->getUnitOfWork()->getEntityChangeSet($entity);

            // If the show in news value has been changed and new value is 1
            // Add activity
            if (isset($changeSet['show_in_news']) && $changeSet['show_in_news'][1]) {
                $this->showProjectCreated($em, $entity);
            }
        }
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $em     = $args->getEntityManager();
        $entity = $args->getEntity();

        // handle project activity
        if ($entity instanceof Project) {
            // If the show in news value has been changed and new value is 1
            // Add activity
            if ($entity->getShowInNews() == 1) {
                $this->showProjectCreated($em, $entity);
            }
        }
    }

    /**
     * Adds vocalizr activity for a new project
     */
    private function showProjectCreated($em, $project)
    {
        $activityMessage = new VocalizrActivity();
        $activityMessage->setActivityType(VocalizrActivity::ACTIVITY_TYPE_NEW_PROJECT);
        $jsonData              = [];
        $jsonData['user_info'] = ['id' => $project->getUserInfo()->getId(),
            'username'                 => $project->getUserInfo()->getUsername(),
            'gender'                   => $project->getUserInfo()->getGender(), ];
        $jsonData['project_info'] = ['uuid' => $project->getUuid(),
            'title'                         => $project->getTitle(),
            'budget_from'                   => $project->getBudgetFrom(),
            'budget_to'                     => $project->getBudgetTo(),
            'gender'                        => $project->getGender(), ];
        $activityMessage->setData(json_encode($jsonData));
        $em->persist($activityMessage);
    }
}