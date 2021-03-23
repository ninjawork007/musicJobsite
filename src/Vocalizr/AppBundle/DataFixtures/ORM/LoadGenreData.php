<?php

// src/Acme/HelloBundle/DataFixtures/ORM/LoadUserData.php

namespace Vocalizr\AppBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Vocalizr\AppBundle\Entity\Genre;

class LoadGenreData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $electronicaGenre = new Genre();
        $electronicaGenre->setTitle('Electronica');
        $manager->persist($electronicaGenre);
        $this->addReference('genre-electronica', $electronicaGenre);

        $progressiveHouseGenre = new Genre();
        $progressiveHouseGenre->setTitle('Progressive House');
        $manager->persist($progressiveHouseGenre);
        $this->addReference('genre-progressive-house', $progressiveHouseGenre);

        $tranceGenre = new Genre();
        $tranceGenre->setTitle('Trance');
        $manager->persist($tranceGenre);
        $this->addReference('genre-trance', $tranceGenre);

        $techGenre = new Genre();
        $techGenre->setTitle('Tech');
        $manager->persist($techGenre);
        $this->addReference('genre-tech', $techGenre);

        $technoGenre = new Genre();
        $technoGenre->setTitle('Techno');
        $manager->persist($technoGenre);
        $this->addReference('genre-techno', $technoGenre);

        $electroGenre = new Genre();
        $electroGenre->setTitle('Electro');
        $manager->persist($electroGenre);
        $this->addReference('genre-electro', $electroGenre);

        $drumnBassGenre = new Genre();
        $drumnBassGenre->setTitle('Drum N Bass');
        $manager->persist($drumnBassGenre);
        $this->addReference('genre-drum-n-bass', $drumnBassGenre);

        $houseGenre = new Genre();
        $houseGenre->setTitle('House');
        $manager->persist($houseGenre);
        $this->addReference('genre-house', $houseGenre);

        $dubstepGenre = new Genre();
        $dubstepGenre->setTitle('Dubstep');
        $manager->persist($dubstepGenre);
        $this->addReference('genre-dubstep', $dubstepGenre);

        $chilloutGenre = new Genre();
        $chilloutGenre->setTitle('Chill Out');
        $manager->persist($chilloutGenre);
        $this->addReference('genre-chillout', $chilloutGenre);

        $hardcoreGenre = new Genre();
        $hardcoreGenre->setTitle('Hardcore');
        $manager->persist($hardcoreGenre);
        $this->addReference('genre-hardcore', $hardcoreGenre);

        $indieDanceGenre = new Genre();
        $indieDanceGenre->setTitle('Indie Dance');
        $manager->persist($indieDanceGenre);
        $this->addReference('genre-indie-dance', $indieDanceGenre);

        $nuDiscoGenre = new Genre();
        $nuDiscoGenre->setTitle('Nu Disco');
        $manager->persist($nuDiscoGenre);
        $this->addReference('genre-nu-disco', $nuDiscoGenre);

        $trapGenre = new Genre();
        $trapGenre->setTitle('Trap');
        $manager->persist($trapGenre);
        $this->addReference('genre-trap', $trapGenre);

        $funkGenre = new Genre();
        $funkGenre->setTitle('Funk');
        $manager->persist($funkGenre);
        $this->addReference('genre-funk', $funkGenre);

        $rnbGenre = new Genre();
        $rnbGenre->setTitle('RnB');
        $manager->persist($rnbGenre);
        $this->addReference('genre-rnb', $rnbGenre);

        $hiphopGenre = new Genre();
        $hiphopGenre->setTitle('Hip Hop');
        $manager->persist($hiphopGenre);
        $this->addReference('genre-hiphop', $hiphopGenre);

        $rapGenre = new Genre();
        $rapGenre->setTitle('Rap');
        $manager->persist($rapGenre);
        $this->addReference('genre-rap', $rapGenre);

        $rockGenre = new Genre();
        $rockGenre->setTitle('Rock');
        $manager->persist($rockGenre);
        $this->addReference('genre-rock', $rockGenre);

        $metalGenre = new Genre();
        $metalGenre->setTitle('Heavey Metal');
        $manager->persist($metalGenre);
        $this->addReference('genre-heavy-metal', $metalGenre);

        $progRockGenre = new Genre();
        $progRockGenre->setTitle('Prog Rock');
        $manager->persist($progRockGenre);
        $this->addReference('genre-prog-rock', $progRockGenre);

        $countryWesternGenre = new Genre();
        $countryWesternGenre->setTitle('Country / Western');
        $manager->persist($countryWesternGenre);
        $this->addReference('genre-country-western', $countryWesternGenre);

        $indieRockGenre = new Genre();
        $indieRockGenre->setTitle('Indie Rock');
        $manager->persist($indieRockGenre);
        $this->addReference('genre-indie-rock', $indieDanceGenre);

        $punkGenre = new Genre();
        $punkGenre->setTitle('Punk');
        $manager->persist($punkGenre);
        $this->addReference('genre-punk', $punkGenre);

        $popGenre = new Genre();
        $popGenre->setTitle('Pop');
        $manager->persist($popGenre);
        $this->addReference('genre-pop', $popGenre);

        $bluesGenre = new Genre();
        $bluesGenre->setTitle('Blues');
        $manager->persist($bluesGenre);
        $this->addReference('genre-blues', $bluesGenre);

        $soulGenre = new Genre();
        $soulGenre->setTitle('Soul');
        $manager->persist($soulGenre);
        $this->addReference('genre-soul', $soulGenre);

        $operaGenre = new Genre();
        $operaGenre->setTitle('Opera');
        $manager->persist($operaGenre);
        $this->addReference('genre-opera', $operaGenre);

        $reggaeGenre = new Genre();
        $reggaeGenre->setTitle('Reggae');
        $manager->persist($reggaeGenre);
        $this->addReference('genre-reggae', $reggaeGenre);

        $jazzGenre = new Genre();
        $jazzGenre->setTitle('Jazz');
        $manager->persist($jazzGenre);
        $this->addReference('genre-jazz', $jazzGenre);

        $hardRockGenre = new Genre();
        $hardRockGenre->setTitle('Hard Rock');
        $manager->persist($hardRockGenre);
        $this->addReference('genre-hard-rock', $hardRockGenre);

        $folkGenre = new Genre();
        $folkGenre->setTitle('Folk');
        $manager->persist($folkGenre);
        $this->addReference('genre-folk', $folkGenre);

        $classicalGenre = new Genre();
        $classicalGenre->setTitle('Classical');
        $manager->persist($classicalGenre);
        $this->addReference('genre-classical', $classicalGenre);

        $latinGenre = new Genre();
        $latinGenre->settitle('Latin');
        $manager->persist($latinGenre);
        $this->addReference('genre-latin', $latinGenre);

        $breaksGenre = new Genre();
        $breaksGenre->settitle('Breaks');
        $manager->persist($breaksGenre);
        $this->addReference('genre-breaks', $breaksGenre);

        $manager->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 1; // the order in which fixtures will be loaded
    }
}