<?php

namespace Vocalizr\AppBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
// for doctrine 2.4: Doctrine\Common\Persistence\Event\LifecycleEventArgs;

use Vocalizr\AppBundle\Entity\Project;
use Vocalizr\AppBundle\Entity\ProjectComment;
use Vocalizr\AppBundle\Entity\ProjectEscrow;
use Vocalizr\AppBundle\Entity\ProjectFeed;
use Vocalizr\AppBundle\Entity\ProjectLyrics;

class ProjectFeedSubscriber implements EventSubscriber
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

        // If project has been updated
        if ($entity instanceof Project) {
            $changeSet = $args->getEntityManager()->getUnitOfWork()->getEntityChangeSet($entity);

            // If the prompt_assets value has been changed and new value is 1
            // Notify feed
            if (isset($changeSet['prompt_assets']) && $changeSet['prompt_assets'][1]) {
                $pf = new ProjectFeed();
                $pf->setProject($entity);
                $pf->setUserInfo($entity->getUserInfo());
                $pf->setObjectId($entity->getId());
                $pf->setObjectType($em->getClassMetadata(get_class($entity))->getReflectionClass()->getShortName());
                $data = [
                    'action' => 'prompt_assets',
                ];
                $pf->setData(json_encode($data));
                $em->persist($pf);
            }
        }

        // If ProjectEscrow is updated
        if ($entity instanceof ProjectEscrow) {
            // If released, update feed
            $changeSet = $args->getEntityManager()->getUnitOfWork()->getEntityChangeSet($entity);
            if (isset($changeSet['released_date']) && $entity->getReleasedDate()) {
                $pf = new ProjectFeed();
                $pf->setProject($entity->getProject());
                $pf->setUserInfo($entity->getUserInfo());
                $pf->setObjectId($entity->getId());
                $pf->setObjectType($em->getClassMetadata(get_class($entity))->getReflectionClass()->getShortName());
                $data = [
                    'amount' => $entity->getAmount(),
                ];
                $pf->setData(json_encode($data));
                $em->persist($pf);
            }
        }
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $em     = $args->getEntityManager();

        // If project ocmment insert has been made
        if ($entity instanceof ProjectComment) {
            $pf = new ProjectFeed();
            $pf->setProject($entity->getProject());
            $pf->setUserInfo($entity->getFrom());
            $pf->setObjectId($entity->getId());
            $pf->setObjectType($em->getClassMetadata(get_class($entity))->getReflectionClass()->getShortName());

            // Data used for displaying in feed
            $data['comment'] = $entity->getContent();
            if ($userAudio = $entity->getUserAudio()) {
                $data['audio_slug'] = $userAudio->getSlug();
                $data['path']       = $userAudio->getPath();
                $data['sc_id']      = $userAudio->getScId();
            }
            $pf->setData(json_encode($data));
            $em->persist($pf);
        }

        // If lyrics insert has been made
        if ($entity instanceof ProjectLyrics) {
            // Only start updating project feed once project has been awarded
            $project = $entity->getProject();
            if (!$project->getProjectBid()) {
                return false;
            }
            $pf = new ProjectFeed();
            $pf->setProject($entity->getProject());
            $pf->setUserInfo($entity->getUserInfo());
            $pf->setObjectId($entity->getId());
            $pf->setObjectType($em->getClassMetadata(get_class($entity))->getReflectionClass()->getShortName());
            $pf->setData(null);
            $em->persist($pf);
        }
    }
}