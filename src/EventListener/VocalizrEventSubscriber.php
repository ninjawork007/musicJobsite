<?php

namespace App\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Symfony\Component\DependencyInjection\ContainerInterface;
// for doctrine 2.4: Doctrine\Common\Persistence\Event\LifecycleEventArgs;

use App\Entity\Notification;
use App\Entity\Project;
use App\Entity\ProjectActivity;
use App\Entity\ProjectAsset;
use App\Entity\ProjectAudio;
use App\Entity\ProjectBid;
use App\Entity\ProjectComment;
use App\Entity\ProjectEscrow;
use App\Entity\ProjectFeed;
use App\Entity\ProjectInvite;
use App\Entity\ProjectLyrics;
use App\Entity\UserActionAudit;
use App\Entity\UserAudio;
use App\Entity\UserConnect;
use App\Entity\UserConnectInvite;
use App\Entity\UserInfo;
use App\Entity\UserVocalCharacteristicVote;
use App\Entity\UserVocalStyleVote;
use App\Entity\UserVoiceTagVote;
use App\Entity\VocalizrActivity;

class VocalizrEventSubscriber implements EventSubscriber
{
    /** @var ContainerInterface */
    private $container;

    /**
     * VocalizrEventSubscriber constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct($container)
    {
        $this->container = $container;
    }

    public function getSubscribedEvents()
    {
        return [
            'postPersist',
            'postUpdate',
            //'preUpdate',
            'postRemove',
        ];
    }

    //
    // HANDLE PRE UPDATE EVENTS
    //
    public function preUpdate(LifeCycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $em     = $args->getEntityManager();
    }

    //
    // HANDLE POST UPDATE EVENTS
    //
    public function postUpdate(LifeCycleEventArgs $args)
    {
        $entity    = $args->getEntity();
        $em        = $args->getEntityManager();
        $changeSet = $args->getEntityManager()->getUnitOfWork()->getEntityChangeSet($entity);

        $entity instanceof Project ? $this->ProjectPostUpdate($em, $entity, $changeSet) : null;
        $entity instanceof ProjectBid ? $this->ProjectBidPostUpdate($em, $entity, $changeSet) : null;
        $entity instanceof ProjectEscrow ? $this->ProjectEscrowPostUpdate($em, $entity, $changeSet) : null;
        $entity instanceof UserInfo ? $this->UserInfoPostUpdate($em, $entity, $changeSet) : null;
        $entity instanceof UserConnectInvite ? $this->UserConnectInvitePostUpdate($em, $entity, $changeSet) : null;
    }

    /**
     * Actions to be performed post update on the Project entity
     */
    private function ProjectPostUpdate($em, $entity, $changeSet)
    {
        $updated = false;

        // accepted offer for gig? notify the producer of offer accept, notify the vocalist of gig start
        if (isset($changeSet['project_bid']) && $changeSet['project_bid'][1] !== null) {

            // add other activity
            $pa = new ProjectActivity();
            $pa->setActivityType(ProjectActivity::ACTIVITY_TYPE_START);
            $pa->setUserInfo($entity->getUserInfo());
            $pa->setActionedUserInfo($entity->getProjectBid()->getUserInfo());
            $pa->setProject($entity);
            $jsonData              = [];
            $jsonData['user_info'] = ['id' => $entity->getEmployeeUserInfo()->getId(),
                'username'                 => $entity->getEmployeeUserInfo()->getUsername(), ];
            $jsonData['project'] = ['id' => $entity->getId(),
                'uuid'                   => $entity->getUuid(),
                'title'                  => $entity->getTitle(), ];
            $jsonData['amount']    = $entity->getProjectBid()->getAmount();
            $jsonData['messageTo'] = 'producer';
            $pa->setData(json_encode($jsonData));
            $pa->setActivityRead(true);
            $em->persist($pa);

            $pa = new ProjectActivity();
            $pa->setActivityType(ProjectActivity::ACTIVITY_TYPE_START);
            $pa->setUserInfo($entity->getEmployeeUserInfo());
            $pa->setActionedUserInfo($entity->getUserInfo());
            $pa->setProject($entity);
            $jsonData              = [];
            $jsonData['user_info'] = ['id' => $entity->getUserInfo()->getId(),
                'username'                 => $entity->getUserInfo()->getUsername(), ];
            $jsonData['project'] = ['id' => $entity->getId(),
                'uuid'                   => $entity->getUuid(),
                'title'                  => $entity->getTitle(), ];
            $jsonData['amount']    = $entity->getProjectBid()->getAmount();
            $jsonData['messageTo'] = 'vocalist';
            $pa->setData(json_encode($jsonData));
            $em->persist($pa);

            // update the last activity for the project
            $lastActivity         = [];
            $lastActivity['name'] = 'awarded';
            $lastActivity['date'] = new \DateTime();
            $entity->setLastActivity(json_encode($lastActivity));
            $entity->setEmployerReadAt(new \DateTime());
            $entity->setEmployeeReadAt(null);

            $entity->getEmployeeUserInfo()->setUnreadProjectActivity(true);
            $em->persist($entity->getEmployeeUserInfo());

            // add to the feed that its been awarded
            if ($entity->getShowInNews() && !$entity->getHireUser()) {
                $this->showProjectAwarded($em, $entity);
            }
            $updated = true;
        }

        // prompted for assets? notify the vocalist and update the feed
        if (isset($changeSet['prompt_assets']) && $changeSet['prompt_assets'][1]) {
            // update the project feed
            $pf = new ProjectFeed();
            $pf->setProject($entity);
            $pf->setUserInfo($entity->getEmployeeUserInfo());
            $pf->setFromUserInfo($entity->getUserInfo());
            $pf->setObjectId($entity->getId());
            $pf->setObjectType($em->getClassMetadata(get_class($entity))->getReflectionClass()->getShortName());
            $data = [
                'action' => 'prompt_assets',
            ];
            $pf->setData(json_encode($data));
            $em->persist($pf);

            // add to project activity
            $pa = new ProjectActivity();
            $pa->setActivityType(ProjectActivity::ACTIVITY_TYPE_PROMPT_ASSET);
            $pa->setUserInfo($entity->getEmployeeUserInfo());
            $pa->setActionedUserInfo($entity->getUserInfo());
            $pa->setProject($entity);
            $jsonData              = [];
            $jsonData['user_info'] = ['id' => $entity->getUserInfo()->getId(),
                'username'                 => $entity->getUserInfo()->getUsername(), ];
            $jsonData['project'] = ['id' => $entity->getId(),
                'uuid'                   => $entity->getUuid(),
                'title'                  => $entity->getTitle(), ];
            $pa->setData(json_encode($jsonData));
            $em->persist($pa);

            // update the project last activity
            $lastActivity         = [];
            $lastActivity['name'] = 'assets requested';
            $lastActivity['date'] = new \DateTime();
            $entity->setLastActivity(json_encode($lastActivity));
            $entity->setEmployerReadAt(new \DateTime());
            $entity->setEmployeeReadAt(null);

            $entity->getEmployeeUserInfo()->setUnreadProjectActivity(true);
            $em->persist($entity->getEmployeeUserInfo());
            $updated = true;
        }

        // Add activity
        if ($entity->getShowInNews() && isset($changeSet['published_at']) && $changeSet['published_at'][1]) {
            $this->showProjectCreated($em, $entity);
            $updated = true;
        }

        if (isset($changeSet['is_complete']) && $changeSet['is_complete'][1] && $entity->getPublishType() == Project::PUBLISH_PUBLIC) {
            // add other activity
            $pa = new ProjectActivity();
            $pa->setActivityType(ProjectActivity::ACTIVITY_TYPE_COMPLETED);
            $pa->setUserInfo($entity->getUserInfo());
            $pa->setActionedUserInfo($entity->getEmployeeUserInfo());
            $pa->setProject($entity);
            $jsonData              = [];
            $jsonData['user_info'] = ['id' => $entity->getEmployeeUserInfo()->getId(),
                'username'                 => $entity->getEmployeeUserInfo()->getUsername(), ];
            $jsonData['project'] = ['id' => $entity->getId(),
                'uuid'                   => $entity->getUuid(),
                'title'                  => $entity->getTitle(), ];
            $jsonData['amount']    = $entity->getProjectBid()->getAmount();
            $jsonData['messageTo'] = 'producer';
            $pa->setData(json_encode($jsonData));
            $pa->setActivityRead(true);
            $em->persist($pa);

            $pa = new ProjectActivity();
            $pa->setActivityType(ProjectActivity::ACTIVITY_TYPE_COMPLETED);
            $pa->setUserInfo($entity->getEmployeeUserInfo());
            $pa->setActionedUserInfo($entity->getUserInfo());
            $pa->setProject($entity);
            $pa->setActivityRead(true);
            $jsonData              = [];
            $jsonData['user_info'] = ['id' => $entity->getUserInfo()->getId(),
                'username'                 => $entity->getUserInfo()->getUsername(), ];
            $jsonData['project'] = ['id' => $entity->getId(),
                'uuid'                   => $entity->getUuid(),
                'title'                  => $entity->getTitle(), ];
            $jsonData['amount']    = $entity->getProjectBid()->getAmount();
            $jsonData['messageTo'] = 'vocalist';
            $pa->setData(json_encode($jsonData));
            $em->persist($pa);

            // update the last activity for the project
            $lastActivity         = [];
            $lastActivity['name'] = 'completed';
            $lastActivity['date'] = new \DateTime();
            $entity->setLastActivity(json_encode($lastActivity));
            $entity->setEmployerReadAt(new \DateTime());
            $entity->setEmployeeReadAt(new \DateTime());

//            $entity->getEmployeeUserInfo()->setUnreadProjectActivity(true);
//            $em->persist($entity->getEmployeeUserInfo());

            $this->showProjectCompleted($em, $entity);
            $updated = true;
        }

        if ($updated) {
            $em->flush();
        }
    }

    /**
     * Actions to be performed post update on the ProjectBid entity
     */
    private function ProjectBidPostUpdate($em, $entity, $changeSet)
    {
    }

    /**
     * Actions to be performed post update on the ProjectEscrow entity
     *
     * @param EntityManager $em
     * @param ProjectEscrow $entity
     * @param array         $changeSet
     */
    private function ProjectEscrowPostUpdate($em, $entity, $changeSet)
    {
        if (isset($changeSet['released_date']) && $entity->getReleasedDate() && $entity->getAmount() > 0) {
            $pf = new ProjectFeed();
            $pf->setProject($entity->getProject());
            $pf->setUserInfo($entity->getProject()->getEmployeeUserInfo());
            $pf->setFromUserInfo($entity->getUserInfo());
            $pf->setObjectId($entity->getId());
            $pf->setObjectType($em->getClassMetadata(get_class($entity))->getReflectionClass()->getShortName());
            $data = [
                'amount' => $entity->getAmount(),
            ];
            $pf->setData(json_encode($data));
            $em->persist($pf);
            $em->flush();
        }
    }

    /**
     * Actions to be performed post update on the UserInfo entity
     *
     * @param EntityManager $em
     * @param UserInfo      $entity
     * @param array         $changeSet
     *
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function UserInfoPostUpdate($em, $entity, $changeSet)
    {
        // if (isset($changeSet['is_active']) && $changeSet['is_active'][1] && !$entity->isActivationEventSuppressed()) {
        // } elseif ($entity->isActivationEventSuppressed()) {
        //     $modify = 0;
        //     if (isset($changeSet['is_active']) && $changeSet['is_active'][1] == true) {
        //         $modify = 1;
        //     } elseif (isset($changeSet['is_active']) && $changeSet['is_active'][1] == false) {
        //         $modify = -1;
        //     }
        //     if ($modify) {
        //         foreach ($entity->getConnections() as $connection) {
        //             $fromUser = $connection->getFrom();
        //             $toUser = $connection->getTo();
        //             $fromUser->setConnectCount($fromUser->getConnectCount() + $modify);
        //             $toUser->setConnectCount($toUser->getConnectCount() + $modify);
        //             $em->persist($fromUser);
        //             $em->persist($toUser);
        //         }
        //         $em->flush();
        //     }
        // }

        // If the user finishes registration -
        // Add activity
        if (isset($changeSet['registration_finished']) && ($changeSet['registration_finished'][1])) {
            $va = new VocalizrActivity();
            $va->setActivityType(VocalizrActivity::ACTIVITY_TYPE_NEW_MEMBER);
            $va->setActionedUserInfo($entity);
            $jsonData              = [];
            $jsonData['user_info'] = ['id' => $entity->getId(),
                'username'                 => $entity->getUsername(),
                'gender'                   => $entity->getGender(),
                'is_vocalist'              => $entity->getIsVocalist(),
                'is_producer'              => $entity->getIsProducer(), ];
            $va->setData(json_encode($jsonData));
            $em->persist($va);
            $em->flush();
        }
    }

    /**
     * Actions to be performed post update on the UserConnectInvite entity
     */
    private function UserConnectInvitePostUpdate($em, $entity, $changeSet)
    {
        // If the user becomes active
        // Add activity
        if (isset($changeSet['connected_at']) && $changeSet['connected_at'][1]) {
            // Remove notification for invite
            $qb = $em->createQueryBuilder();
            $qb->delete('App:Notification', 'n');
            $qb->andWhere('n.notify_type = :notifyType');
            $qb->andWhere('n.actioned_user_info = :actionedUser');
            $qb->andWhere('n.user_info = :user');
            $qb->setParameter(':notifyType', Notification::NOTIFY_TYPE_CONNNECT_INVITE);
            $qb->setParameter(':actionedUser', $entity->getFrom());
            $qb->setParameter(':user', $entity->getTo());
            $qb->getQuery()->execute();

            // We removed notification from user, update unread count
            $em->getRepository('App:Notification')
                ->updateUnreadCount($entity->getTo());

            // Add notification telling the user their connection was accepted
            $n = new Notification();
            $n->setUserInfo($entity->getFrom());
            $n->setActionedUserInfo($entity->getTo());
            $n->setNotifyType(Notification::NOTIFY_TYPE_CONNNECT_ACCEPT);

            $notifyUser = $entity->getFrom();

            // Update connections counts for both users
            $entity->getTo()->setConnectCount($entity->getTo()->getConnectCount() + 1);
            $notifyUser->setConnectCount($notifyUser->getConnectCount() + 1);

            $uc = new UserConnect();
            $uc->setTo($entity->getFrom());
            $uc->setFrom($entity->getTo());
            $uc->setEngaged(true);
            $em->persist($uc);

            $uc = new UserConnect();
            $uc->setTo($entity->getTo());
            $uc->setFrom($entity->getFrom());
            $uc->setEngaged(false);
            $em->persist($uc);

            $em->persist($n);
            $em->persist($notifyUser);
            $em->persist($entity->getTo());
            $em->flush();
        }

        // User connect invite was ignored
        if (isset($changeSet['status']) && !$changeSet['status'][1]) {
            $qb = $em->createQueryBuilder();
            $qb->delete('App:Notification', 'n');
            $qb->andWhere('n.notify_type = :notifyType');
            $qb->andWhere('n.actioned_user_info = :actionedUser');
            $qb->andWhere('n.user_info = :user');
            $qb->setParameter(':notifyType', Notification::NOTIFY_TYPE_CONNNECT_INVITE);
            $qb->setParameter(':actionedUser', $entity->getFrom());
            $qb->setParameter(':user', $entity->getTo());
            $qb->getQuery()->execute();

            $em->getRepository('App:Notification')
                    ->updateUnreadCount($entity->getTo());
        }
    }

    //
    // HANDLE POST PERSIST EVENTS
    //
    public function postPersist(LifeCycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $em     = $args->getEntityManager();

        $entity instanceof Project ? $this->ProjectPostPersist($em, $entity) : null;
        $entity instanceof ProjectAsset ? $this->ProjectAssetPostPersist($em, $entity) : null;
        $entity instanceof ProjectBid ? $this->ProjectBidPostPersist($em, $entity) : null;
        $entity instanceof ProjectComment ? $this->ProjectCommentPostPersist($em, $entity) : null;
        $entity instanceof ProjectLyrics ? $this->ProjectLyricsPostPersist($em, $entity) : null;
        $entity instanceof ProjectInvite ? $this->ProjectInvitePostPersist($em, $entity) : null;
        $entity instanceof UserConnectInvite ? $this->UserConnectInvitePostPersist($em, $entity) : null;
        $entity instanceof UserVocalCharacteristicVote || $entity instanceof UserVocalStyleVote
                || $entity instanceof UserVoiceTagVote ? $this->TagVotePostPersist($em, $entity) : null;

        $entity instanceof ProjectAudio ? $this->ProjectAudioPostPersist($em, $entity) : null;
        $entity instanceof Message ? $this->MessagePostPersist($em, $entity) : null;

        $em->flush();

        $entity instanceof ProjectAudio ? $this->ProjectAudioWaveForm($em, $entity, $entity->getId()) : null;
        $entity instanceof UserAudio ? $this->UserAudioWaveForm($em, $entity, $entity->getId()) : null;
    }

    /**
     * Actions to be performed post persist on the ProjectComment entity
     */
    private function ProjectPostPersist($em, $entity)
    {
        // If the show in news value has been changed and new value is 1 then add activity
        if ($entity->getShowInNews() == 1) {
            //$this->showProjectCreated($em, $entity);
        }
    }

    /**
     * Actions to be performed post persist on the ProjectAsset entity
     */
    private function ProjectAssetPostPersist($em, $entity)
    {

        // see if there are any similar actions recently
        $activity = $em->getRepository('App:ProjectActivity')
                ->findRecentActivityByType(ProjectActivity::ACTIVITY_TYPE_ASSET, $entity->getProject()->getUserInfo(), $entity->getProject());

        if ($activity) {
            // we have similar activity for this type so just update the information for that activity
            $jsonData            = json_decode($activity->getData());
            $jsonData->numAssets = $jsonData->numAssets + 1;
            $assets              = (array) $jsonData->assets;
            $assets[]            = ['title' => $entity->getTitle(),
                'path'                      => $entity->getPath(),
                'duration'                  => $entity->getDurationString(), ];
            $jsonData->assets = $assets;
            $activity->setData(json_encode($jsonData));
        } else {
            $pa = new ProjectActivity();
            $pa->setActivityType(ProjectActivity::ACTIVITY_TYPE_ASSET);
            $pa->setUserInfo($entity->getProject()->getUserInfo());
            $pa->setActionedUserInfo($entity->getUserInfo());
            $pa->setProject($entity->getProject());
            $jsonData              = [];
            $jsonData['user_info'] = ['id' => $entity->getUserInfo()->getId(),
                'username'                 => $entity->getUserInfo()->getUsername(),
                'displayName'              => $entity->getUserInfo()->getDisplayName(), ];
            $jsonData['numAssets'] = 1;
            $jsonData['assets'][]  = ['title' => $entity->getTitle(),
                'path'                        => $entity->getPath(),
                'duration'                    => $entity->getDurationString(), ];
            $pa->setData(json_encode($jsonData));
            $em->persist($pa);
        }

        // update the project last activity
        $lastActivity         = [];
        $lastActivity['name'] = 'new asset';
        $lastActivity['date'] = new \DateTime();
        $entity->getProject()->setLastActivity(json_encode($lastActivity));
        $entity->getProject()->setEmployerReadAt(null);
        $entity->getProject()->setEmployeeReadAt(new \DateTime());

        $entity->getProject()->getUserInfo()->setUnreadProjectActivity(true);
        $em->persist($entity->getProject()->getUserInfo());
    }

    /**
     * Actions to be performed post persist on the ProjectBid entity
     *
     * @param EntityManager $em
     * @param ProjectBid $entity
     *
     * @throws \Doctrine\ORM\Query\QueryException
     */
    private function ProjectBidPostPersist($em, $entity)
    {
        // update project activity with new bid
        $pa = new ProjectActivity();
        $pa->setActivityType(ProjectActivity::ACTIVITY_TYPE_BID);
        $pa->setUserInfo($entity->getProject()->getUserInfo());
        $pa->setActionedUserInfo($entity->getUserInfo());
        $pa->setProject($entity->getProject());
        $jsonData              = [];
        $jsonData['user_info'] = ['id' => $entity->getUserInfo()->getId(),
            'username'                 => $entity->getUserInfo()->getUsername(), ];
        $jsonData['project'] = ['id' => $entity->getProject()->getId(),
            'uuid'                   => $entity->getProject()->getUuid(),
            'title'                  => $entity->getProject()->getTitle(), ];
        if ($entity->getPath()) {
            $jsonData['bid'] = $entity->getPath();
        }
        $jsonData['amount'] = $entity->getAmount();
        $pa->setData(json_encode($jsonData));
        $em->persist($pa);

        // increment the number of bids received for this project and set last bid date
        $qb = $em->getRepository('App:ProjectBid')
           ->createQueryBuilder('pb');
        $qb->select('count(pb)');
        $qb->where('pb.project = :project');
        $qb->setParameter('project', $entity->getProject());
        $numBids = $qb->getQuery()->getSingleScalarResult();

        $entity->getProject()->setNumBids($numBids);
        $entity->getProject()->setLastBidAt(new \DateTime());
        $entity->getProject()->setBidTotal($entity->getProject()->getBidTotal() + $entity->getAmount());

        // Check if that user had an invite, if so remove it
        $projectInvite = $em->getRepository('App:ProjectInvite')
                ->findOneBy([
                    'user_info' => $entity->getUserInfo()->getId(),
                    'project'   => $entity->getProject()->getId(),
                ]);
        if ($projectInvite) {
            $em->remove($projectInvite);
        }
        // update the project last activity
        $numNewBids      = 1;
        $oldLastActivity = $entity->getProject()->getLastActivity();
        if (isset($oldLastActivity['name']) && $oldLastActivity['name'] == 'new bid') {
            $numNewBids = $oldLastActivity['count'] + 1;
        }

        $lastActivity          = [];
        $lastActivity['name']  = 'new bid';
        $lastActivity['date']  = new \DateTime();
        $lastActivity['count'] = $numNewBids;

        $entity->getProject()->setLastActivity(json_encode($lastActivity));
        $entity->getProject()->setEmployerReadAt(null);
        $entity->getProject()->setEmployeeReadAt(null);

        $entity->getProject()->getUserInfo()->setUnreadProjectActivity(true);
        $em->persist($entity->getProject()->getUserInfo());
        $auditModel = $this->container->get('vocalizr_app.model.user_audit');
        $audit = $auditModel->createAudit(UserActionAudit::ACTION_ADD_BID, null, [
            'bid_uuid' => $entity->getUuid(),
            'project_id' => $entity->getProject()->getId(),
            'affectedUser' => $entity->getUserInfo()->getId(),
        ]);
        $em->persist($audit);
    }

    /**
     * Actions to be performed post persist on the ProjectComment entity
     */
    private function ProjectCommentPostPersist($em, $entity)
    {
        $notifyUser = ($entity->getFrom()->getId() != $entity->getProject()->getUserInfo()->getId()
                ? $entity->getProject()->getUserInfo() : $entity->getProject()->getEmployeeUserInfo());

        // update project feed with the new comment
        $pf = new ProjectFeed();
        $pf->setProject($entity->getProject());
        $pf->setUserInfo($notifyUser);
        $pf->setFromUserInfo($entity->getFrom());
        $pf->setObjectId($entity->getId());
        $pf->setObjectType($em->getClassMetadata(get_class($entity))->getReflectionClass()->getShortName());

        // Data used for displaying in feed
        $data['comment'] = $entity->getContent();
        if ($audio = $entity->getProjectAudio()) {
            $data['audio_slug'] = $audio->getSlug();
            $data['path']       = $audio->getPath();
        }
        // If there are project files
        $projectFiles = $entity->getProjectFiles();

        if ($projectFiles) {
            $files = [];
            foreach ($projectFiles as $pfile) {
                $files[] = [
                    'slug'         => $pfile->getSlug(),
                    'title'        => $pfile->getTitle(),
                    'dropbox_link' => $pfile->getDropboxLink(),
                    'path'         => $pfile->getPath(),
                ];
            }
            $data['files'] = $files;
        }

        $pf->setData(json_encode($data));
        $em->persist($pf);

        // update project activity with new comment
        $pa = new ProjectActivity();
        $pa->setActivityType(ProjectActivity::ACTIVITY_TYPE_MESSAGE);
        $pa->setUserInfo($notifyUser);
        $pa->setActionedUserInfo($entity->getFrom());
        $pa->setProject($entity->getProject());
        $jsonData              = [];
        $jsonData['user_info'] = ['id' => $entity->getFrom()->getId(),
            'username'                 => $entity->getFrom()->getUsername(),
        ];
        $jsonData['project'] = ['id' => $entity->getProject()->getId(),
            'uuid'                   => $entity->getProject()->getUuid(),
            'title'                  => $entity->getProject()->getTitle(), ];
        $jsonData['content']  = $entity->getContent();
        $jsonData['hasAudio'] = $entity->getProjectAudio() ? true : false;
        if (isset($files)) {
            $jsonData['files'] = $files;
        }
        $pa->setData(json_encode($jsonData));
        $em->persist($pa);

        // update the project last activity
        $lastActivity             = [];
        $lastActivity['name']     = 'new message';
        $lastActivity['date']     = new \DateTime();
        $lastActivity['hasAudio'] = $entity->getProjectAudio() ? true : false;
        $entity->getProject()->setLastActivity(json_encode($lastActivity));
        if ($notifyUser == $entity->getProject()->getUserInfo()) {
            $entity->getProject()->setEmployerReadAt(null);
            $entity->getProject()->setEmployeeReadAt(new \DateTime());
        } else {
            $entity->getProject()->setEmployerReadAt(new \DateTime());
            $entity->getProject()->setEmployeeReadAt(null);
        }

        $em->persist($entity->getProject());

        $notifyUser->setUnreadProjectActivity(true);
        $em->persist($notifyUser);
    }

    /**
     * Actions to be performed post persist on the ProjectAudio entity
     */
    private function ProjectAudioPostPersist($em, $entity)
    {
        $notifyUser = ($entity->getUserInfo()->getId() != $entity->getProject()->getUserInfo()->getId()
                ? $entity->getProject()->getUserInfo() : $entity->getProject()->getEmployeeUserInfo());

        if (is_null($notifyUser)) {
            return;
        }

        // If audio is a comment, don't add to feed
        if ($entity->getFlag() == ProjectAudio::FLAG_COMMENT) {
            return;
        }

        // update project feed with the new audio
        $pf = new ProjectFeed();
        $pf->setProject($entity->getProject());
        $pf->setUserInfo($notifyUser);
        $pf->setFromUserInfo($entity->getUserInfo());
        $pf->setObjectId($entity->getId());
        $pf->setObjectType($em->getClassMetadata(get_class($entity))->getReflectionClass()->getShortName());

        // Data used for displaying in feed
        $data['audio_slug'] = $entity->getSlug();
        $data['path']       = $entity->getPath();
        $data['flag']       = $entity->getFlag();

        $pf->setData(json_encode($data));
        $em->persist($pf);

        // update project activity with new comment
        $pa = new ProjectActivity();
        $pa->setActivityType(ProjectActivity::ACTIVITY_TYPE_MASTER_AUDIO);
        $pa->setUserInfo($notifyUser);
        $pa->setActionedUserInfo($entity->getUserInfo());
        $pa->setProject($entity->getProject());

        $jsonData              = [];
        $jsonData['user_info'] = ['id' => $entity->getUserInfo()->getId(),
            'path'                     => $entity->getUserInfo()->getPath(),
            'username'                 => $entity->getUserInfo()->getUsername(), ];
        $jsonData['project'] = ['id' => $entity->getProject()->getId(),
            'uuid'                   => $entity->getProject()->getUuid(),
            'title'                  => $entity->getProject()->getTitle(), ];
        $jsonData['audio'] = [
            'slug' => $entity->getSlug(),
            'path' => $entity->getPath(),
            'flag' => $entity->getFlag(),
        ];
        $pa->setData(json_encode($jsonData));
        $em->persist($pa);

        // update the project last activity
        $lastActivity         = [];
        $lastActivity['name'] = 'new audio';
        $lastActivity['date'] = new \DateTime();
        $entity->getProject()->setLastActivity(json_encode($lastActivity));
        if ($notifyUser == $entity->getProject()->getUserInfo()) {
            $entity->getProject()->setEmployerReadAt(null);
            $entity->getProject()->setEmployeeReadAt(new \DateTime());
        } else {
            $entity->getProject()->setEmployerReadAt(new \DateTime());
            $entity->getProject()->setEmployeeReadAt(null);
        }
        $em->persist($entity->getProject());

        $notifyUser->setUnreadProjectActivity(true);
        $em->persist($entity->getUserInfo());
    }

    /**
     * Actions to be performed post persist on the ProjectComment entity
     */
    private function ProjectLyricsPostPersist($em, $entity)
    {
        $project = $entity->getProject();
        if (!$project->getProjectBid()) {
            return false;
        }

        $notifyUser = ($entity->getUserInfo()->getId() != $project->getUserInfo()->getId()
                        ? $project->getUserInfo() : $project->getEmployeeUserInfo());

        $pf = new ProjectFeed();
        $pf->setProject($project);
        $pf->setUserInfo($notifyUser);
        $pf->setFromUserInfo($entity->getUserInfo());
        $pf->setObjectId($entity->getId());
        $pf->setObjectType($em->getClassMetadata(get_class($entity))->getReflectionClass()->getShortName());
        $pf->setData(null);
        $em->persist($pf);

        // new lyrics? notify the other person the lyrics changed
        $pa = new ProjectActivity();
        $pa->setActivityType(ProjectActivity::ACTIVITY_TYPE_LYRICS);
        $pa->setUserInfo($notifyUser);
        $pa->setActionedUserInfo($entity->getUserInfo());
        $pa->setProject($project);
        $jsonData              = [];
        $jsonData['user_info'] = ['id' => $entity->getUserInfo()->getId(),
            'username'                 => $entity->getUserInfo()->getUsername(), ];
        $jsonData['project'] = ['id' => $project->getId(),
            'uuid'                   => $project->getUuid(),
            'title'                  => $project->getTitle(), ];
        $pa->setData(json_encode($jsonData));
        $em->persist($pa);

        // update the project last activity
        $lastActivity         = [];
        $lastActivity['name'] = 'new lyrics';
        $lastActivity['date'] = new \DateTime();
        $project->setLastActivity(json_encode($lastActivity));
        if ($notifyUser == $project->getUserInfo()) {
            $project->setEmployerReadAt(null);
            $project->setEmployeeReadAt(new \DateTime());
        } else {
            $project->setEmployerReadAt(new \DateTime());
            $project->setEmployeeReadAt(null);
        }
        $em->persist($project);

        $notifyUser->setUnreadProjectActivity(true);
        $em->persist($entity->getUserInfo());
    }

    /**
     * Actions to be performed post persist on the ProjectInvite entity
     */
    private function ProjectInvitePostPersist($em, $entity)
    {
        $pa = new ProjectActivity();
        $pa->setActivityType(ProjectActivity::ACTIVITY_TYPE_INVITE);
        $pa->setProject($entity->getProject());
        $pa->setUserInfo($entity->getUserInfo());
        $pa->setActionedUserInfo($entity->getProject()->getUserInfo());

        // Get owner of project
        $owner = $entity->getProject()->getUserInfo();
        $data  = [
            'user_info' => [
                'id'       => $owner->getId(),
                'username' => $owner->getUsername(),
            ],
        ];
        $pa->setData(json_encode($data));
        $em->persist($pa);

        // Add notification
        $n = new \App\Entity\Notification();
        $n->setProject($entity->getProject());
        $n->setUserInfo($entity->getUserInfo());
        $n->setActionedUserInfo($entity->getProject()->getUserInfo());

        if ($entity->getProject()->getHireUser()) {
            $n->setNotifyType(\App\Entity\Notification::NOTIFY_TYPE_PROJECT_HIRE);
        } else {
            $n->setNotifyType(\App\Entity\Notification::NOTIFY_TYPE_PROJECT_INVITE);
        }
        $em->persist($n);

        $user = $entity->getUserInfo();
        $user->setUnseenProjectInvitation(true);
        $i = $user->getNumNotifications();
        $user->setNumNotifications($i + 1);
        $em->persist($user);
    }

    /**
     * Actions to be performed post persist on the UserConnectInvite entity
     */
    private function UserConnectInvitePostPersist($em, $entity)
    {
        // Add notification as when a user connect is first added it's an invite
        $n = new \App\Entity\Notification();
        $n->setUserInfo($entity->getTo());
        $n->setActionedUserInfo($entity->getFrom());
        $n->setNotifyType(\App\Entity\Notification::NOTIFY_TYPE_CONNNECT_INVITE);
        $em->persist($n);

        $user = $entity->getTo();
        $i    = $user->getNumNotifications();
        $user->setNumNotifications($i + 1);
        $em->persist($user);
    }

    /**
     * Actions to be performed when a new message is added
     */
    private function MessagePostPersist($em, $entity)
    {
        $messageThread = $entity->getMessageThread();
        if ($messageThread->getEmployer() == $entity->getToUserInfo()) {
            $messageThread->setNumEmployerUnread($messageThread->getNumEmployerUnread() + 1);
        } else {
            $messageThread->setNumBidderUnread($messageThread->getNumBidderUnread() + 1);
        }
    }

    /**
     * Actions to be performed post persist on the UserVocalCharacteristicVote entity
     * and UserVocalStyleVote entity and UserVoiceTagVote entity
     */
    private function TagVotePostPersist($em, $entity)
    {
        // Get parent entity
        if ($entity instanceof UserVocalCharacteristicVote) {
            $parent = $entity->getUserVocalCharacteristic();
            $type   = $parent->getVocalCharacteristic();
            $title  = $type->getTitle();
        }
        if ($entity instanceof UserVocalStyleVote) {
            $parent = $entity->getUserVocalStyle();
            $type   = $parent->getVocalStyle();
            $title  = $type->getTitle();
        }
        if ($entity instanceof UserVoiceTagVote) {
            $parent = $entity->getUserVoiceTag();
            $type   = $parent->getVoiceTag();
            $title  = $type->getTitle();
        }

        // Get user info for who the tag is for
        $userInfo = $parent->getUserInfo();

        // Check vocalizr activity for any recent tags
        $activity = $em->getRepository('App:VocalizrActivity')
                ->findRecentyActivityByType(VocalizrActivity::ACTIVITY_TYPE_TAG_VOTE, $userInfo->getId());

        if (!$activity) {
            $activity = new VocalizrActivity();
            $activity->setActivityType(VocalizrActivity::ACTIVITY_TYPE_TAG_VOTE);
            $activity->setUserInfo($userInfo);
            $activity->setActionedUserInfo($entity->getFromUserInfo());
        }

        $data = [];
        if ($json = $activity->getData()) {
            $data = json_decode($json, true);
        }

        $fromUserInfo = $entity->getFromUserInfo();

        $shortName = $em->getClassMetadata(get_class($type))->getReflectionClass()->getShortName();

        // If tag already exists, then ignore
        if (isset($data[$fromUserInfo->getId()]['votes'][$shortName . $parent->getId()])) {
            return false;
        }

        $data[$fromUserInfo->getId()]['user'] = [
            'id'          => $fromUserInfo->getId(),
            'username'    => $fromUserInfo->getUsername(),
            'displayName' => $fromUserInfo->getDisplayName(),
        ];

        $data[$fromUserInfo->getId()]['votes'][$shortName . $parent->getId()] = [
            'id'    => $parent->getId(),
            'title' => $title,
            'type'  => $shortName,
            'total' => $parent->getAgree(),
        ];

        $activity->setData(json_encode($data));
        $activity->setCreatedAt(new \DateTime());
        $em->persist($activity);
    }

    /**
     * Actions to be performed post persist on the ProjectAudio entity
     */
    private function ProjectAudioWaveForm($em, $entity, $entityId)
    {
        // If file uploaded, generate waveform
        if ($entity->getPath()) {
            $this->container->get('service.helper')->
                    execSfCmd('vocalizr:generate-waveform --project_audio ' . $entityId);
        }
    }

    /**
     * Actions to be performed post persist on the UserAudio entity
     */
    private function UserAudioWaveForm($em, $entity, $entityId)
    {
        // If file uploaded, generate waveform
        if ($entity->getPath()) {
            $this->container->get('service.helper')->
                    execSfCmd('vocalizr:generate-waveform --user_audio ' . $entityId);
        }
    }

    //
    // HELPER FUNCTIONS
    //

    /**
     * Adds vocalizr activity for a new project
     */
    private function showProjectAwarded($em, $project)
    {
        $va = new VocalizrActivity();
        $va->setActivityType(VocalizrActivity::ACTIVITY_TYPE_PROJECT_AWARDED);
        $va->setActionedUserInfo($project->getUserInfo());
        $va->setProject($project);
        $jsonData = [];
        $va->setData(json_encode($jsonData));
        $em->persist($va);
    }

    private function showProjectCreated($em, $project)
    {
        $va = new VocalizrActivity();
        $va->setActivityType(VocalizrActivity::ACTIVITY_TYPE_NEW_PROJECT);
        $va->setActionedUserInfo($project->getUserInfo());
        $va->setProject($project);
        $jsonData = [];
        $va->setData(json_encode($jsonData));
        $em->persist($va);
    }

    /**
     * Adds a vocalir activity for a completed project
     */
    private function showProjectCompleted($em, $project)
    {
        if ($project->getHireUser()) {
            return true;
        }

        // Get escrow, if it's zero don't display
        $escrow = $project->getProjectEscrow();
        if (!$escrow->getAmount()) {
            return true;
        }

        $va = new VocalizrActivity();
        $va->setActivityType(VocalizrActivity::ACTIVITY_TYPE_PROJECT_COMPLETED);
        $va->setActionedUserInfo($project->getUserInfo());
        $va->setProject($project);
        $jsonData = [];
        $va->setData(json_encode($jsonData));
        $em->persist($va);
    }

    //
    // HANDLE POST REMOVE EVENTS
    //
    public function postRemove(LifeCycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $em     = $args->getEntityManager();

        $entity instanceof ProjectBid ? $this->ProjectBidPostRemove($em, $entity) : null;
        $entity instanceof UserConnectInvite ? $this->UserConnectInvitePostRemove($em, $entity) : null;
        $entity instanceof Notification ? $this->NotificationPostRemove($em, $entity) : null;
    }

    /**
     * Actions to be performed post remove on the ProjectBid entity
     */
    private function UserConnectInvitePostRemove($em, $entity)
    {
        $user = $entity->getTo();
        $from = $entity->getFrom();

        $qb = $em->getRepository('App:Notification')->createQueryBuilder('n');
        $qb->delete('App:Notification', 'n');
        $qb->where('( (n.user_info = :to AND n.actioned_user_info = :from) OR (n.user_info = :from AND n.actioned_user_info = :to) )');
        $qb->andWhere("(n.notify_type = 'connect_invite')");
        $qb->setParameter('to', $user);
        $qb->setParameter('from', $from);
        $qb->getQuery()->execute();

        $em->getRepository('App:Notification')
        ->updateUnreadCount($user);

        $em->getRepository('App:Notification')
                ->updateUnreadCount($from);

        // If user connection was approved, update both connect counts
        if ($entity->getConnectedAt()) {
            $qb = $em->getRepository('App:UserConnect')->createQueryBuilder('uc');
            $qb->delete('App:UserConnect', 'uc');
            $qb->where('(uc.to = :to AND uc.from = :from) OR (uc.to = :from AND uc.from = :to)');
            $qb->setParameter('to', $user);
            $qb->setParameter('from', $from);
            $qb->getQuery()->execute();

            $uc1 = $em->getRepository('App:UserConnect')->findOneBy([
                'to'   => $entity->getTo(),
                'from' => $entity->getFrom(),
            ]);
            if ($uc1) {
                $em->remove($uc1);
            }
            $uc2 = $em->getRepository('App:UserConnect')->findOneBy([
                'from' => $entity->getTo(),
                'to'   => $entity->getFrom(),
            ]);
            if ($uc2) {
                $em->remove($uc2);
            }

            $user->setConnectCount($user->getConnectCount() - 1);
            $from->setConnectCount($from->getConnectCount() - 1);
            $em->persist($user);
            $em->persist($from);

            $em->flush();
        }
    }

    /**
     * Actions to be performed post remove on the ProjectBid entity
     */
    private function NotificationPostRemove($em, $entity)
    {
        $user = $entity->getUserInfo();
        $em->getRepository('App:Notification')
                ->updateUnreadCount($user);
    }

    /**
     * Actions to be performed post remove on the ProjectBid entity
     *
     * @param EntityManager $em
     * @param ProjectBid $entity
     */
    private function ProjectBidPostRemove($em, $entity)
    {
        $projectLastActivity = $entity->getProject()->getLastActivity();

        // remove the project activity for this bid
        $qb = $em->getRepository('App:ProjectActivity')
                 ->createQueryBuilder('pa');
        $qb->where('pa.project = :project');
        $qb->andWhere('pa.actioned_user_info = :user');
        $qb->andWhere('pa.activity_type = :activityType');
        $qb->setParameter('project', $entity->getProject());
        $qb->setParameter('user', $entity->getUserInfo());
        $qb->setParameter('activityType', ProjectActivity::ACTIVITY_TYPE_BID);
        $projectActivity = $qb->getQuery()->getSingleResult();
        $em->remove($projectActivity);
        $em->flush($projectActivity);

        if (is_array($projectActivity) && isset($projectActivity['name']) && $projectLastActivity['name'] == 'new bid') {
            // if count > 1 then just reduce the count by 1
            if ($projectLastActivity['count'] > 1) {
                $projectLastActivity['count']--;
                $entity->getProject()->setLastActivity($projectLastActivity);
                $em->flush($entity->getProject());
            } else {
                // determine what the last activity was
                $entity->getProject()->setLastActivity('{}');
                $entity->getProject()->setEmployerReadAt(null);
            }
            $em->flush($entity->getProject());
        }
        $numBids = $entity->getProject()->getNumBids() - 1;

        $entity->getProject()->setNumBids($numBids);

        // see if the project user has any unseen activity
        $q = $em->getRepository('App:Project')->createQueryBuilder('p');
        $q->select('count(p)')
                ->where('p.user_info = :user_info')
                ->andWhere('p.last_activity != :empty_activity')
                ->andWhere('p.employer_read_at is null')
                ->setParameter(':user_info', $entity->getProject()->getUserInfo())
                ->setParameter(':empty_activity', '{}');
        $numEmployerUnread = $q->getQuery()->getSingleScalarResult();

        $q = $em->getRepository('App:Project')->createQueryBuilder('p');
        $q->select('count(p)')
                ->where('p.employee_user_info = :user_info')
                ->andWhere('p.last_activity != :empty_activity')
                ->andWhere('p.employee_read_at is null')
                ->setParameter(':user_info', $entity->getProject()->getUserInfo())
                ->setParameter(':empty_activity', '{}');
        $numEmployeeUnread = $q->getQuery()->getSingleScalarResult();

        if ($numEmployerUnread == 0 && $numEmployeeUnread == 0) {
            $entity->getProject()->getUserInfo()->setUnreadProjectActivity(false);
            $em->flush($entity->getProject()->getUserInfo());
        }

        $auditModel = $this->container->get('vocalizr_app.model.user_audit');
        $auditModel->logAction(UserActionAudit::ACTION_REMOVE_BID, null, [
            'bid_uuid' => $entity->getUuid(),
            'project_id' => $entity->getProject()->getId(),
            'affectedUser' => $entity->getUserInfo()->getId(),
        ]);
    }
}