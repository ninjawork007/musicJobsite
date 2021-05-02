<?php

// src/Acme/HelloBundle/DataFixtures/ORM/LoadUserData.php

namespace App\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;

use App\Entity\VocalCharacteristic;

class LoadVocalCharacteristicData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $raspyVocalCharacteristic = new VocalCharacteristic();
        $raspyVocalCharacteristic->setTitle('Raspy');
        $manager->persist($raspyVocalCharacteristic);
        $this->addReference('vocal-characteristic-raspy', $raspyVocalCharacteristic);

        $roughVocalCharacteristic = new VocalCharacteristic();
        $roughVocalCharacteristic->setTitle('Rough');
        $manager->persist($roughVocalCharacteristic);
        $this->addReference('vocal-characteristic-rough', $roughVocalCharacteristic);

        $smootheVocalCharacteristic = new VocalCharacteristic();
        $smootheVocalCharacteristic->setTitle('Smoothe');
        $manager->persist($smootheVocalCharacteristic);
        $this->addReference('vocal-characteristic-smoothe', $smootheVocalCharacteristic);

        $silkyVocalCharacteristic = new VocalCharacteristic();
        $silkyVocalCharacteristic->setTitle('Silky');
        $manager->persist($silkyVocalCharacteristic);
        $this->addReference('vocal-characteristic-silky', $silkyVocalCharacteristic);

        $strongVocalCharacteristic = new VocalCharacteristic();
        $strongVocalCharacteristic->setTitle('Strong');
        $manager->persist($strongVocalCharacteristic);
        $this->addReference('vocal-characteristic-strong', $strongVocalCharacteristic);

        $crispVocalCharacteristic = new VocalCharacteristic();
        $crispVocalCharacteristic->setTitle('Crisp');
        $manager->persist($crispVocalCharacteristic);
        $this->addReference('vocal-characteristic-crispy', $crispVocalCharacteristic);

        $deepVocalCharacteristic = new VocalCharacteristic();
        $deepVocalCharacteristic->setTitle('Deep');
        $manager->persist($deepVocalCharacteristic);
        $this->addReference('vocal-characteristic-deep', $deepVocalCharacteristic);

        $highVocalCharacteristic = new VocalCharacteristic();
        $highVocalCharacteristic->setTitle('High');
        $manager->persist($highVocalCharacteristic);
        $this->addReference('vocal-characteristic-high', $highVocalCharacteristic);

        $lowVocalCharacteristic = new VocalCharacteristic();
        $lowVocalCharacteristic->setTitle('Low');
        $manager->persist($lowVocalCharacteristic);
        $this->addReference('vocal-characteristic-low', $lowVocalCharacteristic);

        $manager->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 4; // the order in which fixtures will be loaded
    }
}