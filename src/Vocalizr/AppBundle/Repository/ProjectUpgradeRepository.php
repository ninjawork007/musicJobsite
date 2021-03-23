<?php

namespace Vocalizr\AppBundle\Repository;

use Doctrine\ORM\EntityRepository;

use Vocalizr\AppBundle\Entity\Project;
use Vocalizr\AppBundle\Entity\ProjectUpgrade;

class ProjectUpgradeRepository extends EntityRepository
{
    /**
     * Record upgrades that were purchased
     *
     * @param Project $project
     */
    public function recordUpgrades($project, $subscriptionPlan)
    {
        // Private project
        if ($project->getPublishType() == 'private') {
            $amount = $subscriptionPlan['project_private_fee'];
            $pu     = new ProjectUpgrade();
            $pu->setUserInfo($project->getUserInfo());
            $pu->setProject($project);
            $pu->setAmount($amount ? $amount : 0);
            $pu->setUpgrade('private');
            $this->_em->persist($pu);
        }

        // Project highlight
        if ($project->getHighlight()) {
            $amount = $subscriptionPlan['project_highlight_fee'];
            $pu     = new ProjectUpgrade();
            $pu->setUserInfo($project->getUserInfo());
            $pu->setProject($project);
            $pu->setAmount($amount ? $amount : 0);
            $pu->setUpgrade('highlight');
            $this->_em->persist($pu);
        }

        // Project featured
        if ($project->getFeatured()) {
            $amount = $subscriptionPlan['project_feature_fee'];
            $pu     = new ProjectUpgrade();
            $pu->setUserInfo($project->getUserInfo());
            $pu->setProject($project);
            $pu->setAmount($amount ? $amount : 0);
            $pu->setUpgrade('feature');
            $this->_em->persist($pu);
        }

        // Announce project
        if ($project->getShowInNews()) {
            $amount = $subscriptionPlan['project_announce_fee'];
            $pu     = new ProjectUpgrade();
            $pu->setUserInfo($project->getUserInfo());
            $pu->setProject($project);
            $pu->setAmount($amount ? $amount : 0);
            $pu->setUpgrade('announce');
            $this->_em->persist($pu);
        }

        // Lock to certified
        if ($project->getProRequired()) {
            $amount = $subscriptionPlan['project_lock_to_cert_fee'];
            $pu     = new ProjectUpgrade();
            $pu->setUserInfo($project->getUserInfo());
            $pu->setProject($project);
            $pu->setAmount($amount ? $amount : 0);
            $pu->setUpgrade('lock_to_cert');
            $this->_em->persist($pu);
        }

        // Invite favs
        if ($project->getToFavorites()) {
            $amount = $subscriptionPlan['project_favorites_fee'];
            $pu     = new ProjectUpgrade();
            $pu->setUserInfo($project->getUserInfo());
            $pu->setProject($project);
            $pu->setAmount($amount ? $amount : 0);
            $pu->setUpgrade('favorites');
            $this->_em->persist($pu);
        }

        // Restrict entries to brief / criteria
        if ($project->getRestrictToPreferences()) {
            $amount = $subscriptionPlan['project_restrict_fee'];
            $pu     = new ProjectUpgrade();
            $pu->setUserInfo($project->getUserInfo());
            $pu->setProject($project);
            $pu->setAmount($amount ? $amount : 0);
            $pu->setUpgrade('restrict');
            $this->_em->persist($pu);
        }

        // Messaging
        if ($project->getMessaging()) {
            $amount = $subscriptionPlan['project_messaging_fee'];
            $pu     = new ProjectUpgrade();
            $pu->setUserInfo($project->getUserInfo());
            $pu->setProject($project);
            $pu->setAmount($amount ? $amount : 0);
            $pu->setUpgrade('messaging');
            $this->_em->persist($pu);
        }

        $this->_em->flush();
    }
}
