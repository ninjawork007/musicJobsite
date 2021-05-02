<?php

// src/Acme/HelloBundle/DataFixtures/ORM/LoadUserData.php

namespace App\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;

use App\Entity\SubscriptionPlan;

class LoadSubscriptionPlanData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        // Default free subscription plan
        $freeSp = new SubscriptionPlan();
        $freeSp->setTitle('Free Membership');
        $freeSp->setDescription('Free Membership');
        $freeSp->setPrice('0');
        $freeSp->setUserAudioLimit(2);
        $freeSp->setProjectPercentAdded(3);
        $freeSp->setPaymentPercentTaken(10);
        $freeSp->setStaticKey('FREE');
        $freeSp->setUniqueKey(uniqid());
        $freeSp->setHidden(0);
        $manager->persist($freeSp);
        $this->addReference('free-subscription-plan', $freeSp);

        $manager->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 6; // the order in which fixtures will be loaded
    }
}