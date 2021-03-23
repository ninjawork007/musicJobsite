<?php

// src/Acme/HelloBundle/DataFixtures/ORM/LoadUserData.php

namespace Vocalizr\AppBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Vocalizr\AppBundle\Entity\Language;

class LoadLanguageData extends AbstractFixture implements OrderedFixtureInterface
{
    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $englishLanguage = new Language();
        $englishLanguage->setTitle('English');
        $manager->persist($englishLanguage);
        $this->addReference('language-english', $englishLanguage);

        $spanishLanguage = new Language();
        $spanishLanguage->setTitle('Spanish');
        $manager->persist($spanishLanguage);
        $this->addReference('language-spanish', $spanishLanguage);

        $frenchLanguage = new Language();
        $frenchLanguage->setTitle('French');
        $manager->persist($frenchLanguage);
        $this->addReference('language-french', $frenchLanguage);

        $dutchLanguage = new Language();
        $dutchLanguage->setTitle('Dutch');
        $manager->persist($dutchLanguage);
        $this->addReference('language-dutch', $dutchLanguage);

        $italianLanguage = new Language();
        $italianLanguage->setTitle('Italian');
        $manager->persist($italianLanguage);
        $this->addReference('language-italian', $italianLanguage);

        $mandarinLanguage = new Language();
        $mandarinLanguage->setTitle('Mandarin');
        $manager->persist($mandarinLanguage);
        $this->addReference('language-mandarin', $mandarinLanguage);

        $japaneseLanguage = new Language();
        $japaneseLanguage->setTitle('Japanese');
        $manager->persist($japaneseLanguage);
        $this->addReference('language-japanese', $japaneseLanguage);

        $southKoreanLanguage = new Language();
        $southKoreanLanguage->setTitle('South Korean');
        $manager->persist($southKoreanLanguage);
        $this->addReference('language-south-korean', $southKoreanLanguage);

        $manager->flush();
    }

    /**
     * {@inheritDoc}
     */
    public function getOrder()
    {
        return 2; // the order in which fixtures will be loaded
    }
}