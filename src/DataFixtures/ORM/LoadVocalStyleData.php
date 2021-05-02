<?php

// src/Acme/HelloBundle/DataFixtures/ORM/LoadUserData.php

namespace App\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;

use App\Entity\VocalStyle;

class LoadVocalStyleData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $rockVocalStyle = new VocalStyle();
        $rockVocalStyle->setTitle('Rock');
        $manager->persist($rockVocalStyle);
        $this->addReference('vocal-style-rock', $rockVocalStyle);

        $divaVocalStyle = new VocalStyle();
        $divaVocalStyle->setTitle('Diva');
        $manager->persist($divaVocalStyle);
        $this->addReference('vocal-style-diva', $divaVocalStyle);

        $divoVocalStyle = new VocalStyle();
        $divoVocalStyle->setTitle('Divo');
        $manager->persist($divoVocalStyle);
        $this->addReference('vocal-style-divo', $divoVocalStyle);

        $soulfulVocalStyle = new VocalStyle();
        $soulfulVocalStyle->setTitle('Soulful');
        $manager->persist($soulfulVocalStyle);
        $this->addReference('vocal-style-soulful', $soulfulVocalStyle);

        $heavyMetalVocalStyle = new VocalStyle();
        $heavyMetalVocalStyle->setTitle('Heavy Metal');
        $manager->persist($heavyMetalVocalStyle);
        $this->addReference('vocal-style-heavy-metal', $heavyMetalVocalStyle);

        $deathMetalVocalStyle = new VocalStyle();
        $deathMetalVocalStyle->setTitle('Death Metal');
        $manager->persist($deathMetalVocalStyle);
        $this->addReference('vocal-style-death-metal', $deathMetalVocalStyle);

        $rapVocalStyle = new VocalStyle();
        $rapVocalStyle->setTitle('Rap');
        $manager->persist($rapVocalStyle);
        $this->addReference('vocal-style-rap', $rapVocalStyle);

        $choirVocalStyle = new VocalStyle();
        $choirVocalStyle->setTitle('Choir');
        $manager->persist($choirVocalStyle);
        $this->addReference('vocal-style-choir', $choirVocalStyle);

        $operaVocalStyle = new VocalStyle();
        $operaVocalStyle->setTitle('Opera');
        $manager->persist($operaVocalStyle);
        $this->addReference('vocal-style-opera', $operaVocalStyle);

        $countryVocalStyle = new VocalStyle();
        $countryVocalStyle->setTitle('Country');
        $manager->persist($countryVocalStyle);
        $this->addReference('vocal-style-country', $countryVocalStyle);

        $reggaeVocalStyle = new VocalStyle();
        $reggaeVocalStyle->setTitle('Reggae');
        $manager->persist($reggaeVocalStyle);
        $this->addReference('vocal-style-reggae', $reggaeVocalStyle);

        $spokenWordVocalStyle = new VocalStyle();
        $spokenWordVocalStyle->setTitle('Spoken Word');
        $manager->persist($spokenWordVocalStyle);
        $this->addReference('vocal-style-spoken-word', $spokenWordVocalStyle);

        $classicalVocalStyle = new VocalStyle();
        $classicalVocalStyle->setTitle('Classical');
        $manager->persist($classicalVocalStyle);
        $this->addReference('vocal-style-classical', $classicalVocalStyle);

        $popDivaVocalStyle = new VocalStyle();
        $popDivaVocalStyle->setTitle('Pop Diva');
        $manager->persist($popDivaVocalStyle);
        $this->addReference('vocal-style-pop-diva', $popDivaVocalStyle);

        $popDivoVocalStyle = new VocalStyle();
        $popDivoVocalStyle->setTitle('Pop Divo');
        $manager->persist($popDivoVocalStyle);
        $this->addReference('vocal-style-pop-divo', $popDivoVocalStyle);

        $musicalTheatreVocalStyle = new VocalStyle();
        $musicalTheatreVocalStyle->setTitle('Musical Theatre');
        $manager->persist($musicalTheatreVocalStyle);
        $this->addReference('vocal-style-musical-theatre', $musicalTheatreVocalStyle);

        $aCapellaVocalStyle = new VocalStyle();
        $aCapellaVocalStyle->setTitle('A Capella');
        $manager->persist($aCapellaVocalStyle);
        $this->addReference('vocal-style-acapella', $aCapellaVocalStyle);

        $manager->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 3; // the order in which fixtures will be loaded
    }
}