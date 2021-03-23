<?php

namespace Vocalizr\AppBundle\Service;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Vocalizr\AppBundle\Document\ProfileView;
use Vocalizr\AppBundle\Entity\UserInfo;

/**
 * Class StatisticsService
 * @package Vocalizr\AppBundle\Service
 */
class StatisticsService
{
    /**
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var Session
     */
    private $session;

    /**
     * StatisticsService constructor.
     * @param DocumentManager $dm
     * @param SessionInterface $session
     */
    public function __construct(DocumentManager $dm, SessionInterface $session)
    {
        $this->dm      = $dm;
        $this->session = $session;
    }

    /**
     * Record stats whenever a user's profile has been viewed
     *
     * @param UserInfo $profileOwner
     * @param UserInfo $currentUser
     *
     * @return bool
     */
    public function recordProfileViewStat(UserInfo $profileOwner, UserInfo $currentUser = null)
    {
        // If they are viewing their own profile, ignore recording stat
        if ($currentUser && $currentUser->getId() == $profileOwner->getId()) {
            return false;
        }

        // Check session
        $session       = $this->session;
        $uProfileViews = [];
        if ($session->get('uProfileViews')) {
            $uProfileViews = $session->get('uProfileViews');
        }

        $uniqueView = false;
        if (!in_array($profileOwner->getId(), $uProfileViews)) {
            $uniqueView = true;
            // Update session to store profile view for user session
            $uProfileViews[] = $profileOwner->getId();
            $session->set('uProfileViews', $uProfileViews);
        }

        // Record view stat
        $profileViewRepo = $this->dm->getRepository('VocalizrAppBundle:ProfileView');

        $params = [
            'user_id' => $profileOwner->getId(),
            'date'    => date('Y-m-d'),
            'unique'  => false,
        ];
        $profileViewStat = $profileViewRepo->findOneBy($params);
        if (!$profileViewStat) {
            $profileViewStat = new ProfileView();
            $profileViewStat->fromArray($params);
        }
        $count = $profileViewStat->getCount();
        $profileViewStat->setCount($count + 1);
        $this->dm->persist($profileViewStat);

        // Record unique view stat
        if ($uniqueView) {
            $params = [
                'user_id' => $profileOwner->getId(),
                'date'    => date('Y-m-d'),
                'unique'  => true,
            ];
            $profileViewStatUnique = $profileViewRepo->findOneBy($params);
            if (!$profileViewStatUnique) {
                $profileViewStatUnique = new ProfileView();
                $profileViewStatUnique->fromArray($params);
            }
            $count = $profileViewStatUnique->getCount();
            $profileViewStatUnique->setCount($count + 1);
            $this->dm->persist($profileViewStatUnique);

            // Also store ProfileViewUser stat if not logged in
            if (!$currentUser) {
                $params = [
                    'user_id'      => $profileOwner->getId(),
                    'from_user_id' => null,
                    'date'         => date('Y-m-d'),
                ];
                $profileViewUser = new \Vocalizr\AppBundle\Document\ProfileViewUser();
                $profileViewUser->setCreatedAt(date('Y-m-d H:i:s'));
                $profileViewUser->fromArray($params);
                $this->dm->persist($profileViewUser);
            }
        }

        // If user is logged in, store profile view user
        if ($currentUser) {
            $pVURepo = $this->dm->getRepository('VocalizrAppBundle:ProfileViewUser');
            $params  = [
                'user_id'      => $profileOwner->getId(),
                'from_user_id' => $currentUser->getId(),
                'date'         => date('Y-m-d'),
            ];
            if (!$profileViewUser = $pVURepo->findOneBy($params)) {
                $profileViewUser = new \Vocalizr\AppBundle\Document\ProfileViewUser();
                $profileViewUser->setCreatedAt(date('Y-m-d H:i:s'));
                $profileViewUser->fromArray($params);
                $this->dm->persist($profileViewUser);
            }
        }

        $this->dm->flush();

        return true;
    }
}