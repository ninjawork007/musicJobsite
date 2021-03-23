<?php

namespace Vocalizr\AppBundle\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
// for doctrine 2.4: Doctrine\Common\Persistence\Event\LifecycleEventArgs;

use Vocalizr\AppBundle\Entity\Project;
use Vocalizr\AppBundle\Entity\ProjectActivity;
use Vocalizr\AppBundle\Entity\ProjectBid;
use Vocalizr\AppBundle\Entity\ProjectComment;
use Vocalizr\AppBundle\Entity\ProjectLyrics;

class ProjectActivitySubscriber implements EventSubscriber
{
    public function getSubscribedEvents()
    {
        return [
            'postPersist',
            'postUpdate',
            'preUpdate',
        ];
    }

    public function preUpdate(LifeCycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $em     = $args->getEntityManager();
    }

    public function postUpdate(LifeCycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $em     = $args->getEntityManager();

        // handle project activity
        if ($entity instanceof ProjectBid) {
            $changeSet = $args->getEntityManager()->getUnitOfWork()->getEntityChangeSet($entity);

            // If the flag has been changed to A then add activity
            if (isset($changeSet['flag']) && $changeSet['flag'][1] == 'A') {
                $activityMessage = new ProjectActivity();
                $activityMessage->setActivityType(ProjectActivity::ACTIVITY_TYPE_OFFER);
                $activityMessage->setUserInfo($entity->getUserInfo());
                $jsonData              = [];
                $jsonData['user_info'] = ['id' => $entity->getProject()->getUserInfo()->getId(),
                    'username'                 => $entity->getProject()->getUserInfo()->getUsername(), ];
                $jsonData['project'] = ['id' => $entity->getProject()->getId(),
                    'uuid'                   => $entity->getProject()->getUuid(),
                    'title'                  => $entity->getProject()->getTitle(), ];
                $jsonData['offer'] = $entity->getAmount();
                $activityMessage->setData(json_encode($jsonData));
                $em->persist($activityMessage);
            }

            if (isset($changeSet['flag']) && $changeSet['flag'][1] == 'D') {
                $activityMessage = new ProjectActivity();
                $activityMessage->setActivityType(ProjectActivity::ACTIVITY_TYPE_OFFER_DECLINE);
                $activityMessage->setUserInfo($entity->getProject()->getUserInfo());
                $activityMessage->setActionedUserInfo($entity->getUserInfo());

                $jsonData              = [];
                $jsonData['user_info'] = ['id' => $entity->getUserInfo()->getId(),
                    'username'                 => $entity->getUserInfo()->getUsername(), ];
                $jsonData['project'] = ['id' => $entity->getProject()->getId(),
                    'uuid'                   => $entity->getProject()->getUuid(),
                    'title'                  => $entity->getProject()->getTitle(), ];
                $jsonData['offer']   = $entity->getAmount();
                $jsonData['message'] = $entity->getMessage();
                $activityMessage->setData(json_encode($jsonData));
                $em->persist($activityMessage);
            }
        }

        if ($entity instanceof Project) {
            $changeSet = $args->getEntityManager()->getUnitOfWork()->getEntityChangeSet($entity);

            // accepted offer for gig? notify the producer of offer accept, notify the vocalist of gig start
            if (isset($changeSet['project_bid']) && $changeSet['project_bid'][1] !== null) {
                $acceptMessage = new ProjectActivity();
                $acceptMessage->setActivityType(ProjectActivity::ACTIVITY_TYPE_OFFER_ACCEPT);
                $acceptMessage->setUserInfo($entity->getUserInfo());
                $acceptMessage->setActionedUserInfo($entity->getProjectBid()->getUserInfo());
                $jsonData              = [];
                $jsonData['user_info'] = ['id' => $entity->getProjectBid()->getUserInfo()->getId(),
                    'username'                 => $entity->getProjectBid()->getUserInfo()->getUsername(), ];
                $jsonData['project'] = ['id' => $entity->getId(),
                    'uuid'                   => $entity->getUuid(),
                    'title'                  => $entity->getTitle(), ];
                $jsonData['amount'] = $entity->getProjectBid()->getAmount();
                $acceptMessage->setData(json_encode($jsonData));
                $em->persist($acceptMessage);

                $startMessage = new ProjectActivity();
                $startMessage->setActivityType(ProjectActivity::ACTIVITY_TYPE_OFFER_ACCEPT);
                $startMessage->setUserInfo($entity->getProjectBid()->getUserInfo());
                $startMessage->setActionedUserInfo($entity->getProjectBid()->getUserInfo());
                $startMessage->setProject($entity);
                $jsonData              = [];
                $jsonData['user_info'] = ['id' => $entity->getUserInfo()->getId(),
                    'username'                 => $entity->getUserInfo()->getUsername(), ];
                $jsonData['project'] = ['id' => $entity->getId(),
                    'uuid'                   => $entity->getUuid(),
                    'title'                  => $entity->getTitle(), ];
                $jsonData['amount'] = $entity->getProjectBid()->getAmount();
                $startMessage->setData(json_encode($jsonData));
                $em->persist($startMessage);
            }

            // prompted for assets? notify the vocalist
            if (isset($changeSet['prompt_assets']) && $changeSet['prompt_assets'][1]) {
                $promptMessage = new ProjectActivity();
                $promptMessage->setActivityType(ProjectActivity::ACTIVITY_TYPE_PROMPT_ASSET);
                $promptMessage->setUserInfo($entity->getProjectBid()->getUserInfo());
                $promptMessage->setActionedUserInfo($entity->getProjectBid()->getUserInfo());
                $promptMessage->setProject($entity);
                $jsonData              = [];
                $jsonData['user_info'] = ['id' => $entity->getUserInfo()->getId(),
                    'username'                 => $entity->getUserInfo()->getUsername(), ];
                $jsonData['project'] = ['id' => $entity->getId(),
                    'uuid'                   => $entity->getUuid(),
                    'title'                  => $entity->getTitle(), ];
                $promptMessage->setData(json_encode($jsonData));
                $em->persist($promptMessage);
            }
        }
    }

    public function postPersist(LifeCycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $em     = $args->getEntityManager();

        // new lyrics? notify the other person the lyrics changed
        if ($entity instanceof ProjectLyrics) {
            $acceptMessage = new ProjectActivity();
            $acceptMessage->setActivityType(ProjectActivity::ACTIVITY_TYPE_LYRICS);
            $notifyUser = ($entity->getUserInfo()->getId() != $entity->getProject()->getUserInfo()->getId() ? $entity->getProject()->getUserInfo() : $entity->getProject()->getProjectBid()->getUserInfo());
            $acceptMessage->setUserInfo($notifyUser);
            $acceptMessage->setActionedUserInfo($entity->getUserInfo());
            $jsonData              = [];
            $jsonData['user_info'] = ['id' => $entity->getUserInfo()->getId(),
                'username'                 => $entity->getUserInfo()->getUsername(), ];
            $jsonData['project'] = ['id' => $entity->getProject()->getId(),
                'uuid'                   => $entity->getProject()->getUuid(),
                'title'                  => $entity->getProject()->getTitle(), ];
            $acceptMessage->setData(json_encode($jsonData));
            $em->persist($acceptMessage);
        }

        // new message? notify the other person a message has been added
        if ($entity instanceof ProjectComment) {
            $commentActivity = new ProjectActivity();
            $commentActivity->setActivityType(ProjectActivity::ACTIVITY_TYPE_MESSAGE);
            $notifyUser = ($entity->getFrom()->getId() != $entity->getProject()->getUserInfo()->getId() ? $entity->getProject()->getUserInfo() : $entity->getProject()->getProjectBid()->getUserInfo());
            $commentActivity->setUserInfo($notifyUser);
            $commentActivity->setActionedUserInfo($entity->getFrom());
            $jsonData              = [];
            $jsonData['user_info'] = ['id' => $entity->getFrom()->getId(),
                'username'                 => $entity->getFrom()->getUsername(), ];
            $jsonData['project'] = ['id' => $entity->getProject()->getId(),
                'uuid'                   => $entity->getProject()->getUuid(),
                'title'                  => $entity->getProject()->getTitle(), ];
            $jsonData['content']  = $entity->getContent();
            $jsonData['hasAudio'] = $entity->getUserAudio() ? true : false;
            $commentActivity->setData(json_encode($jsonData));
            $em->persist($commentActivity);
        }

        // new asset? notify the producer @TODO - this is hard. can upload multiple assets at once (multiple entries ina ctivity?)
    }
}