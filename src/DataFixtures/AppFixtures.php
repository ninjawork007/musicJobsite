<?php

namespace App\DataFixtures;

use App\Entity\Country;
use App\Entity\Genre;
use App\Entity\Language;
use App\Entity\SubscriptionPlan;
use App\Entity\UserInfo;
use App\Entity\VocalCharacteristic;
use App\Entity\VocalStyle;
use App\Model\CountryModel;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;

class AppFixtures extends Fixture
{
    /** @var CountryModel */
    private $countryModel;

    public function __construct(ContainerInterface $container = null)
    {
        $this->countryModel = $container->get('vocalizr_app.model.country');
    }

    public function load(ObjectManager $manager)
    {
//        LoadGenreData Start
        $electronicaGenre = new Country();
        $electronicaGenre->setTitle('Australia');
        $electronicaGenre->setCode('au');
        $manager->persist($electronicaGenre);

        $electronicaGenre = new Country();
        $electronicaGenre->setTitle('UnitedStates');
        $electronicaGenre->setCode('us');
        $manager->persist($electronicaGenre);

        $electronicaGenre = new Country();
        $electronicaGenre->setTitle('United Kingdom');
        $electronicaGenre->setCode('uk');
        $manager->persist($electronicaGenre);
        $manager->flush();
//        LoadGenreData Start


//        LoadGenreData Start
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
//        LoadGenreData END

//        LoadLanguageData Start
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
//        LoadLanguageData END



//        LoadSubscriptionPlanData Start
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

        $proSp = new SubscriptionPlan();
        $proSp->setTitle('PRO Membership');
        $proSp->setDescription('Pro Membership');
        $proSp->setPrice('0');
        $proSp->setUserAudioLimit(2);
        $proSp->setProjectPercentAdded(3);
        $proSp->setPaymentPercentTaken(10);
        $proSp->setStaticKey('PRO');
        $proSp->setUniqueKey(uniqid());
        $proSp->setHidden(0);
        $manager->persist($proSp);
        $this->addReference('pro-subscription-plan', $proSp);
//        LoadSubscriptionPlanData End


/*//        LoadUserInfoData Start
        $robertUser = new UserInfo();
        $robertUser->setCity('Melbourne');
        $robertUser->setCountry($this->countryModel->byCode('au'));
        $robertUser->setEmail('amine@vocalizr.com');
        $robertUser->setEmailConfirmed(true);
        $robertUser->setFirstName('Robert');
        $robertUser->setLastName('Homewood');
        $robertUser->setGender('m');
        $robertUser->setIsActive(true);
        $robertUser->setIsProducer(true);
        $robertUser->setIsSongwriter(false);
        $robertUser->setIsVocalist(true);

        $encoder  = new MessageDigestPasswordEncoder('sha1', false, 1);
        $password = $encoder->encodePassword("test", "95d76cdfcef0c9af572ab5d67d396da5");

        $robertUser->setPassword($password);
        $robertUser->setSalt('95d76cdfcef0c9af572ab5d67d396da5');
        $robertUser->setUniqueStr('50b07da72772aeb5d40822d95c7fa1aeb791d3b4');
        $robertUser->setStudioAccess(true);
        $robertUser->setUsername('robert79');
        $manager->persist($robertUser);
        $this->addReference('user-amine', $robertUser);

        $robertUser = new UserInfo();
        $robertUser->setCity('Melbourne');
        $robertUser->setCountry($this->countryModel->byCode('au'));
        $robertUser->setEmail('robert@vocalizr.com');
        $robertUser->setEmailConfirmed(true);
        $robertUser->setFirstName('Robert');
        $robertUser->setLastName('Homewood');
        $robertUser->setGender('m');
        $robertUser->setIsActive(true);
        $robertUser->setIsProducer(true);
        $robertUser->setIsSongwriter(false);
        $robertUser->setIsVocalist(true);
        $robertUser->setPassword('bcd4be6dd9250d2a033dad618358f27eff4e3a61');
        $robertUser->setSalt('95d76cdfcef0c9af572ab5d67d396da5');
        $robertUser->setUniqueStr('50b07da72772aeb5d40822d95c7fa1aeb791d3b4');
        $robertUser->setStudioAccess(true);
        $robertUser->setUsername('robert79');
        $manager->persist($robertUser);
        $this->addReference('user-robert', $robertUser);

        $timberlakeUser = new UserInfo();
        $timberlakeUser->setCity('Los Angeles');
        $timberlakeUser->setCountry($this->countryModel->byCode('us'));
        $timberlakeUser->setEmail('robert+1@vocalizr.com');
        $timberlakeUser->setEmailConfirmed(true);
        $timberlakeUser->setFirstName('Justin');
        $timberlakeUser->setLastName('Timberlake');
        $timberlakeUser->setGender('m');
        $timberlakeUser->setIsActive(true);
        $timberlakeUser->setIsProducer(true);
        $timberlakeUser->setIsSongwriter(false);
        $timberlakeUser->setIsVocalist(true);
        $timberlakeUser->setPassword('bcd4be6dd9250d2a033dad618358f27eff4e3a61');
        $timberlakeUser->setSalt('95d76cdfcef0c9af572ab5d67d396da5');
        $timberlakeUser->setUniqueStr('50b07da72772aeb5d40822d95c7fa1aeb791d3b4');
        $timberlakeUser->setStudioAccess(true);
        $timberlakeUser->setUsername('timberlake');
        $manager->persist($timberlakeUser);
        $this->addReference('user-timberlake', $timberlakeUser);

        $jayzUser = new UserInfo();
        $jayzUser->setCity('New York');
        $jayzUser->setCountry($this->countryModel->byCode('us'));
        $jayzUser->setEmail('robert+2@vocalizr.com');
        $jayzUser->setEmailConfirmed(true);
        $jayzUser->setFirstName('Jay');
        $jayzUser->setLastName('Z');
        $jayzUser->setGender('m');
        $jayzUser->setIsActive(true);
        $jayzUser->setIsProducer(true);
        $jayzUser->setIsSongwriter(false);
        $jayzUser->setIsVocalist(true);
        $jayzUser->setPassword('bcd4be6dd9250d2a033dad618358f27eff4e3a61');
        $jayzUser->setSalt('95d76cdfcef0c9af572ab5d67d396da5');
        $jayzUser->setUniqueStr('50b07da72772aeb5d40822d95c7fa1aeb791d3b4');
        $jayzUser->setStudioAccess(true);
        $jayzUser->setUsername('jayz');
        $manager->persist($jayzUser);
        $this->addReference('user-jayz', $jayzUser);

        $usherUser = new UserInfo();
        $usherUser->setCity('New York');
        $usherUser->setCountry($this->countryModel->byCode('us'));
        $usherUser->setEmail('robert+3@vocalizr.com');
        $usherUser->setEmailConfirmed(true);
        $usherUser->setFirstName('User');
        $usherUser->setLastName('Raymond IV');
        $usherUser->setGender('m');
        $usherUser->setIsActive(true);
        $usherUser->setIsProducer(true);
        $usherUser->setIsSongwriter(false);
        $usherUser->setIsVocalist(true);
        $usherUser->setPassword('bcd4be6dd9250d2a033dad618358f27eff4e3a61');
        $usherUser->setSalt('95d76cdfcef0c9af572ab5d67d396da5');
        $usherUser->setUniqueStr('50b07da72772aeb5d40822d95c7fa1aeb791d3b4');
        $usherUser->setStudioAccess(true);
        $usherUser->setUsername('usher');
        $manager->persist($usherUser);
        $this->addReference('user-usher', $usherUser);

        $beyonceUser = new UserInfo();
        $beyonceUser->setCity('New York');
        $beyonceUser->setCountry($this->countryModel->byCode('us'));
        $beyonceUser->setEmail('robert+4@vocalizr.com');
        $beyonceUser->setEmailConfirmed(true);
        $beyonceUser->setFirstName('Beyonce');
        $beyonceUser->setLastName('Knowles');
        $beyonceUser->setGender('f');
        $beyonceUser->setIsActive(true);
        $beyonceUser->setIsProducer(true);
        $beyonceUser->setIsSongwriter(false);
        $beyonceUser->setIsVocalist(true);
        $beyonceUser->setPassword('bcd4be6dd9250d2a033dad618358f27eff4e3a61');
        $beyonceUser->setSalt('95d76cdfcef0c9af572ab5d67d396da5');
        $beyonceUser->setUniqueStr('50b07da72772aeb5d40822d95c7fa1aeb791d3b4');
        $beyonceUser->setStudioAccess(true);
        $beyonceUser->setUsername('beyonce');
        $manager->persist($beyonceUser);
        $this->addReference('user-beyonce', $beyonceUser);

        $georgiaUser = new UserInfo();
        $georgiaUser->setCity('Los Angeles');
        $georgiaUser->setCountry($this->countryModel->byCode('us'));
        $georgiaUser->setEmail('robert+5@vocalizr.com');
        $georgiaUser->setEmailConfirmed(true);
        $georgiaUser->setFirstName('Georgia Anne');
        $georgiaUser->setLastName('Muldrow');
        $georgiaUser->setGender('f');
        $georgiaUser->setIsActive(true);
        $georgiaUser->setIsProducer(true);
        $georgiaUser->setIsSongwriter(false);
        $georgiaUser->setIsVocalist(true);
        $georgiaUser->setPassword('bcd4be6dd9250d2a033dad618358f27eff4e3a61');
        $georgiaUser->setSalt('95d76cdfcef0c9af572ab5d67d396da5');
        $georgiaUser->setUniqueStr('50b07da72772aeb5d40822d95c7fa1aeb791d3b4');
        $georgiaUser->setStudioAccess(true);
        $georgiaUser->setUsername('georgiamuldrow');
        $manager->persist($georgiaUser);
        $this->addReference('user-georgia', $georgiaUser);

        $andreUser = new UserInfo();
        $andreUser->setCity('Los Angeles');
        $andreUser->setCountry($this->countryModel->byCode('us'));
        $andreUser->setEmail('robert+6@vocalizr.com');
        $andreUser->setEmailConfirmed(true);
        $andreUser->setFirstName('Andre Romelle');
        $andreUser->setLastName('Young');
        $andreUser->setGender('m');
        $andreUser->setIsActive(true);
        $andreUser->setIsProducer(true);
        $andreUser->setIsSongwriter(false);
        $andreUser->setIsVocalist(false);
        $andreUser->setPassword('bcd4be6dd9250d2a033dad618358f27eff4e3a61');
        $andreUser->setSalt('95d76cdfcef0c9af572ab5d67d396da5');
        $andreUser->setUniqueStr('50b07da72772aeb5d40822d95c7fa1aeb791d3b4');
        $andreUser->setStudioAccess(true);
        $andreUser->setUsername('Dr Dre');
        $manager->persist($andreUser);
        $this->addReference('user-dre', $andreUser);

        $lukeUser = new UserInfo();
        $lukeUser->setCity('Melbourne');
        $lukeUser->setCountry($this->countryModel->byCode('au'));
        $lukeUser->setEmail('luke@vocalizr.com');
        $lukeUser->setEmailConfirmed(true);
        $lukeUser->setFirstName('Luke');
        $lukeUser->setLastName('Chable');
        $lukeUser->setGender('m');
        $lukeUser->setIsActive(true);
        $lukeUser->setIsProducer(true);
        $lukeUser->setIsSongwriter(false);
        $lukeUser->setIsVocalist(false);
        $lukeUser->setPassword('bcd4be6dd9250d2a033dad618358f27eff4e3a61');
        $lukeUser->setSalt('95d76cdfcef0c9af572ab5d67d396da5');
        $lukeUser->setUniqueStr('50b07da72772aeb5d40822d95c7fa1aeb791d3b4');
        $lukeUser->setStudioAccess(true);
        $lukeUser->setUsername('luke79');
        $manager->persist($lukeUser);
        $this->addReference('user-luke', $lukeUser);

        $tokiUser = new UserInfo();
        $tokiUser->setCity('Los Angeles');
        $tokiUser->setCountry($this->countryModel->byCode('us'));
        $tokiUser->setEmail('luke+1@vocalizr.com');
        $tokiUser->setEmailConfirmed(true);
        $tokiUser->setFirstName('Jennifer');
        $tokiUser->setLastName('Lee');
        $tokiUser->setGender('f');
        $tokiUser->setIsActive(true);
        $tokiUser->setIsProducer(true);
        $tokiUser->setIsSongwriter(false);
        $tokiUser->setIsVocalist(false);
        $tokiUser->setPassword('bcd4be6dd9250d2a033dad618358f27eff4e3a61');
        $tokiUser->setSalt('95d76cdfcef0c9af572ab5d67d396da5');
        $tokiUser->setUniqueStr('50b07da72772aeb5d40822d95c7fa1aeb791d3b4');
        $tokiUser->setStudioAccess(true);
        $tokiUser->setUsername('TOKiMONSTA');
        $manager->persist($tokiUser);
        $this->addReference('user-toki', $tokiUser);

        $daniUser = new UserInfo();
        $daniUser->setCity('Chicago');
        $daniUser->setCountry($this->countryModel->byCode('us'));
        $daniUser->setEmail('luke+2@vocalizr.com');
        $daniUser->setEmailConfirmed(true);
        $daniUser->setFirstName('Dani');
        $daniUser->setLastName('Deahl');
        $daniUser->setGender('f');
        $daniUser->setIsActive(true);
        $daniUser->setIsProducer(true);
        $daniUser->setIsSongwriter(false);
        $daniUser->setIsVocalist(false);
        $daniUser->setPassword('bcd4be6dd9250d2a033dad618358f27eff4e3a61');
        $daniUser->setSalt('95d76cdfcef0c9af572ab5d67d396da5');
        $daniUser->setUniqueStr('50b07da72772aeb5d40822d95c7fa1aeb791d3b4');
        $daniUser->setStudioAccess(true);
        $daniUser->setUsername('realDeahl');
        $manager->persist($daniUser);
        $this->addReference('user-dani', $daniUser);

        $kateUser = new UserInfo();
        $kateUser->setCity('Chicago');
        $kateUser->setCountry($this->countryModel->byCode('us'));
        $kateUser->setEmail('luke+3@vocalizr.com');
        $kateUser->setEmailConfirmed(true);
        $kateUser->setFirstName('Kate');
        $kateUser->setLastName('Simco');
        $kateUser->setGender('f');
        $kateUser->setIsActive(true);
        $kateUser->setIsProducer(true);
        $kateUser->setIsSongwriter(false);
        $kateUser->setIsVocalist(false);
        $kateUser->setPassword('bcd4be6dd9250d2a033dad618358f27eff4e3a61');
        $kateUser->setSalt('95d76cdfcef0c9af572ab5d67d396da5');
        $kateUser->setUniqueStr('50b07da72772aeb5d40822d95c7fa1aeb791d3b4');
        $kateUser->setStudioAccess(true);
        $kateUser->setUsername('simco');
        $manager->persist($kateUser);
        $this->addReference('user-kate', $kateUser);

        $mayaUser = new UserInfo();
        $mayaUser->setCity('Los Angeles');
        $mayaUser->setCountry($this->countryModel->byCode('us'));
        $mayaUser->setEmail('luke+4@vocalizr.com');
        $mayaUser->setEmailConfirmed(true);
        $mayaUser->setFirstName('Maya Jane');
        $mayaUser->setLastName('Coles');
        $mayaUser->setGender('f');
        $mayaUser->setIsActive(true);
        $mayaUser->setIsProducer(true);
        $mayaUser->setIsSongwriter(false);
        $mayaUser->setIsVocalist(false);
        $mayaUser->setPassword('bcd4be6dd9250d2a033dad618358f27eff4e3a61');
        $mayaUser->setSalt('95d76cdfcef0c9af572ab5d67d396da5');
        $mayaUser->setUniqueStr('50b07da72772aeb5d40822d95c7fa1aeb791d3b4');
        $mayaUser->setStudioAccess(true);
        $mayaUser->setUsername('MJ');
        $manager->persist($mayaUser);
        $this->addReference('user-maya', $mayaUser);

        $butchUser = new UserInfo();
        $butchUser->setCity('Los Angeles');
        $butchUser->setCountry($this->countryModel->byCode('us'));
        $butchUser->setEmail('luke+5@vocalizr.com');
        $butchUser->setEmailConfirmed(true);
        $butchUser->setFirstName('Butch');
        $butchUser->setLastName('Vig');
        $butchUser->setGender('m');
        $butchUser->setIsActive(true);
        $butchUser->setIsProducer(true);
        $butchUser->setIsSongwriter(false);
        $butchUser->setIsVocalist(false);
        $butchUser->setPassword('bcd4be6dd9250d2a033dad618358f27eff4e3a61');
        $butchUser->setSalt('95d76cdfcef0c9af572ab5d67d396da5');
        $butchUser->setUniqueStr('50b07da72772aeb5d40822d95c7fa1aeb791d3b4');
        $butchUser->setStudioAccess(true);
        $butchUser->setUsername('ButchV');
        $manager->persist($butchUser);
        $this->addReference('user-butch', $butchUser);

        $georgeUser = new UserInfo();
        $georgeUser->setCity('Los Angeles');
        $georgeUser->setCountry($this->countryModel->byCode('us'));
        $georgeUser->setEmail('luke+6@vocalizr.com');
        $georgeUser->setEmailConfirmed(true);
        $georgeUser->setFirstName('George');
        $georgeUser->setLastName('Martin');
        $georgeUser->setGender('m');
        $georgeUser->setIsActive(true);
        $georgeUser->setIsProducer(true);
        $georgeUser->setIsSongwriter(false);
        $georgeUser->setIsVocalist(false);
        $georgeUser->setPassword('bcd4be6dd9250d2a033dad618358f27eff4e3a61');
        $georgeUser->setSalt('95d76cdfcef0c9af572ab5d67d396da5');
        $georgeUser->setUniqueStr('50b07da72772aeb5d40822d95c7fa1aeb791d3b4');
        $georgeUser->setStudioAccess(true);
        $georgeUser->setUsername('GM');
        $manager->persist($georgeUser);
        $this->addReference('user-george', $georgeUser);

        $eminemUser = new UserInfo();
        $eminemUser->setCity('Los Angeles');
        $eminemUser->setCountry($this->countryModel->byCode('us'));
        $eminemUser->setEmail('luke+7@vocalizr.com');
        $eminemUser->setEmailConfirmed(true);
        $eminemUser->setFirstName('Marshall');
        $eminemUser->setLastName('Mathers');
        $eminemUser->setGender('m');
        $eminemUser->setIsActive(true);
        $eminemUser->setIsProducer(true);
        $eminemUser->setIsSongwriter(false);
        $eminemUser->setIsVocalist(true);
        $eminemUser->setPassword('bcd4be6dd9250d2a033dad618358f27eff4e3a61');
        $eminemUser->setSalt('95d76cdfcef0c9af572ab5d67d396da5');
        $eminemUser->setUniqueStr('50b07da72772aeb5d40822d95c7fa1aeb791d3b4');
        $eminemUser->setStudioAccess(true);
        $eminemUser->setUsername('Eminem');
        $manager->persist($eminemUser);
        $this->addReference('user-eminem', $eminemUser);

        $johnUser = new UserInfo();
        $johnUser->setCity('Melbourne');
        $johnUser->setCountry($this->countryModel->byCode('au'));
        $johnUser->setEmail('john@vocalizr.com');
        $johnUser->setEmailConfirmed(true);
        $johnUser->setFirstName('John');
        $johnUser->setLastName('Smythe');
        $johnUser->setGender('m');
        $johnUser->setIsActive(true);
        $johnUser->setIsProducer(false);
        $johnUser->setIsSongwriter(false);
        $johnUser->setIsVocalist(true);
        $johnUser->setPassword('bcd4be6dd9250d2a033dad618358f27eff4e3a61');
        $johnUser->setSalt('95d76cdfcef0c9af572ab5d67d396da5');
        $johnUser->setUniqueStr('50b07da72772aeb5d40822d95c7fa1aeb791d3b4');
        $johnUser->setStudioAccess(true);
        $johnUser->setUsername('smythe');
        $manager->persist($johnUser);
        $this->addReference('user-john', $johnUser);

        $lenaUser = new UserInfo();
        $lenaUser->setCity('New York');
        $lenaUser->setCountry($this->countryModel->byCode('us'));
        $lenaUser->setEmail('john+1@vocalizr.com');
        $lenaUser->setEmailConfirmed(true);
        $lenaUser->setFirstName('Lena');
        $lenaUser->setLastName('Cullen');
        $lenaUser->setGender('f');
        $lenaUser->setIsActive(true);
        $lenaUser->setIsProducer(false);
        $lenaUser->setIsSongwriter(false);
        $lenaUser->setIsVocalist(true);
        $lenaUser->setPassword('bcd4be6dd9250d2a033dad618358f27eff4e3a61');
        $lenaUser->setSalt('95d76cdfcef0c9af572ab5d67d396da5');
        $lenaUser->setUniqueStr('50b07da72772aeb5d40822d95c7fa1aeb791d3b4');
        $lenaUser->setStudioAccess(true);
        $lenaUser->setUsername('lcullen');
        $manager->persist($lenaUser);
        $this->addReference('user-lena', $lenaUser);

        $jakeUser = new UserInfo();
        $jakeUser->setCity('Chicago');
        $jakeUser->setCountry($this->countryModel->byCode('us'));
        $jakeUser->setEmail('john+2@vocalizr.com');
        $jakeUser->setEmailConfirmed(true);
        $jakeUser->setFirstName('Jake');
        $jakeUser->setLastName('Jenkins');
        $jakeUser->setGender('m');
        $jakeUser->setIsActive(true);
        $jakeUser->setIsProducer(false);
        $jakeUser->setIsSongwriter(false);
        $jakeUser->setIsVocalist(true);
        $jakeUser->setPassword('bcd4be6dd9250d2a033dad618358f27eff4e3a61');
        $jakeUser->setSalt('95d76cdfcef0c9af572ab5d67d396da5');
        $jakeUser->setUniqueStr('50b07da72772aeb5d40822d95c7fa1aeb791d3b4');
        $jakeUser->setStudioAccess(true);
        $jakeUser->setUsername('jj');
        $manager->persist($jakeUser);
        $this->addReference('user-jake', $jakeUser);

        $taylorUser = new UserInfo();
        $taylorUser->setCity('New York');
        $taylorUser->setCountry($this->countryModel->byCode('us'));
        $taylorUser->setEmail('john+3@vocalizr.com');
        $taylorUser->setEmailConfirmed(true);
        $taylorUser->setFirstName('Taylor');
        $taylorUser->setLastName('Swift');
        $taylorUser->setGender('f');
        $taylorUser->setIsActive(true);
        $taylorUser->setIsProducer(false);
        $taylorUser->setIsSongwriter(false);
        $taylorUser->setIsVocalist(true);
        $taylorUser->setPassword('bcd4be6dd9250d2a033dad618358f27eff4e3a61');
        $taylorUser->setSalt('95d76cdfcef0c9af572ab5d67d396da5');
        $taylorUser->setUniqueStr('50b07da72772aeb5d40822d95c7fa1aeb791d3b4');
        $taylorUser->setStudioAccess(true);
        $taylorUser->setUsername('swifty');
        $manager->persist($taylorUser);
        $this->addReference('user-taylor', $taylorUser);

        $justinUser = new UserInfo();
        $justinUser->setCity('New York');
        $justinUser->setCountry($this->countryModel->byCode('us'));
        $justinUser->setEmail('john+4@vocalizr.com');
        $justinUser->setEmailConfirmed(true);
        $justinUser->setFirstName('Justin');
        $justinUser->setLastName('Bieber');
        $justinUser->setGender('m');
        $justinUser->setIsActive(true);
        $justinUser->setIsProducer(false);
        $justinUser->setIsSongwriter(false);
        $justinUser->setIsVocalist(true);
        $justinUser->setPassword('bcd4be6dd9250d2a033dad618358f27eff4e3a61');
        $justinUser->setSalt('95d76cdfcef0c9af572ab5d67d396da5');
        $justinUser->setUniqueStr('50b07da72772aeb5d40822d95c7fa1aeb791d3b4');
        $justinUser->setStudioAccess(true);
        $justinUser->setUsername('biebs');
        $manager->persist($justinUser);
        $this->addReference('user-justin', $justinUser);

        $axelUser = new UserInfo();
        $axelUser->setCity('Washington');
        $axelUser->setCountry($this->countryModel->byCode('us'));
        $axelUser->setEmail('john+5@vocalizr.com');
        $axelUser->setEmailConfirmed(true);
        $axelUser->setFirstName('Axel');
        $axelUser->setLastName('Rose');
        $axelUser->setGender('m');
        $axelUser->setIsActive(true);
        $axelUser->setIsProducer(false);
        $axelUser->setIsSongwriter(false);
        $axelUser->setIsVocalist(true);
        $axelUser->setPassword('bcd4be6dd9250d2a033dad618358f27eff4e3a61');
        $axelUser->setSalt('95d76cdfcef0c9af572ab5d67d396da5');
        $axelUser->setUniqueStr('50b07da72772aeb5d40822d95c7fa1aeb791d3b4');
        $axelUser->setStudioAccess(true);
        $axelUser->setUsername('gunner');
        $manager->persist($axelUser);
        $this->addReference('user-axel', $axelUser);

        $lennonUser = new UserInfo();
        $lennonUser->setCity('London');
        $lennonUser->setCountry($this->countryModel->byCode('uk'));
        $lennonUser->setEmail('john+5@vocalizr.com');
        $lennonUser->setEmailConfirmed(true);
        $lennonUser->setFirstName('John');
        $lennonUser->setLastName('Lennon');
        $lennonUser->setGender('m');
        $lennonUser->setIsActive(true);
        $lennonUser->setIsProducer(false);
        $lennonUser->setIsSongwriter(false);
        $lennonUser->setIsVocalist(true);
        $lennonUser->setPassword('bcd4be6dd9250d2a033dad618358f27eff4e3a61');
        $lennonUser->setSalt('95d76cdfcef0c9af572ab5d67d396da5');
        $lennonUser->setUniqueStr('50b07da72772aeb5d40822d95c7fa1aeb791d3b4');
        $lennonUser->setStudioAccess(true);
        $lennonUser->setUsername('lennon');
        $manager->persist($lennonUser);
        $this->addReference('user-lennon', $lennonUser);

        $lilyUser = new UserInfo();
        $lilyUser->setCity('London');
        $lilyUser->setCountry($this->countryModel->byCode('uk'));
        $lilyUser->setEmail('john+6@vocalizr.com');
        $lilyUser->setEmailConfirmed(true);
        $lilyUser->setFirstName('Lily');
        $lilyUser->setLastName('Allen');
        $lilyUser->setGender('f');
        $lilyUser->setIsActive(true);
        $lilyUser->setIsProducer(false);
        $lilyUser->setIsSongwriter(false);
        $lilyUser->setIsVocalist(true);
        $lilyUser->setPassword('bcd4be6dd9250d2a033dad618358f27eff4e3a61');
        $lilyUser->setSalt('95d76cdfcef0c9af572ab5d67d396da5');
        $lilyUser->setUniqueStr('50b07da72772aeb5d40822d95c7fa1aeb791d3b4');
        $lilyUser->setStudioAccess(true);
        $lilyUser->setUsername('lily');
        $manager->persist($lilyUser);
        $this->addReference('user-lily', $lilyUser);

        $larryUser = new UserInfo();
        $larryUser->setCity('Sussex');
        $larryUser->setCountry($this->countryModel->byCode('uk'));
        $larryUser->setEmail('john+7@vocalizr.com');
        $larryUser->setEmailConfirmed(true);
        $larryUser->setFirstName('Larry');
        $larryUser->setLastName('McNary');
        $larryUser->setGender('m');
        $larryUser->setIsActive(true);
        $larryUser->setIsProducer(false);
        $larryUser->setIsSongwriter(false);
        $larryUser->setIsVocalist(true);
        $larryUser->setPassword('bcd4be6dd9250d2a033dad618358f27eff4e3a61');
        $larryUser->setSalt('95d76cdfcef0c9af572ab5d67d396da5');
        $larryUser->setUniqueStr('50b07da72772aeb5d40822d95c7fa1aeb791d3b4');
        $larryUser->setStudioAccess(true);
        $larryUser->setUsername('lazza');
        $manager->persist($larryUser);
        $this->addReference('user-larry', $larryUser);

        $siaUser = new UserInfo();
        $siaUser->setCity('Melbourne');
        $siaUser->setCountry($this->countryModel->byCode('au'));
        $siaUser->setEmail('john+8@vocalizr.com');
        $siaUser->setEmailConfirmed(true);
        $siaUser->setFirstName('Sia');
        $siaUser->setLastName('Furler');
        $siaUser->setGender('f');
        $siaUser->setIsActive(true);
        $siaUser->setIsProducer(false);
        $siaUser->setIsSongwriter(false);
        $siaUser->setIsVocalist(true);
        $siaUser->setPassword('bcd4be6dd9250d2a033dad618358f27eff4e3a61');
        $siaUser->setSalt('95d76cdfcef0c9af572ab5d67d396da5');
        $siaUser->setUniqueStr('50b07da72772aeb5d40822d95c7fa1aeb791d3b4');
        $siaUser->setStudioAccess(true);
        $siaUser->setUsername('Sia');
        $manager->persist($siaUser);
        $this->addReference('user-sia', $siaUser);

        $nellyUser = new UserInfo();
        $nellyUser->setCity('San Francisco');
        $nellyUser->setCountry($this->countryModel->byCode('us'));
        $nellyUser->setEmail('john+9@vocalizr.com');
        $nellyUser->setEmailConfirmed(true);
        $nellyUser->setFirstName('Nelly');
        $nellyUser->setLastName('Furtado');
        $nellyUser->setGender('f');
        $nellyUser->setIsActive(true);
        $nellyUser->setIsProducer(false);
        $nellyUser->setIsSongwriter(false);
        $nellyUser->setIsVocalist(true);
        $nellyUser->setPassword('bcd4be6dd9250d2a033dad618358f27eff4e3a61');
        $nellyUser->setSalt('95d76cdfcef0c9af572ab5d67d396da5');
        $nellyUser->setUniqueStr('50b07da72772aeb5d40822d95c7fa1aeb791d3b4');
        $nellyUser->setStudioAccess(true);
        $nellyUser->setUsername('NellyFurtado');
        $manager->persist($nellyUser);
        $this->addReference('user-nelly', $nellyUser);

        $freddieUser = new UserInfo();
        $freddieUser->setCity('London');
        $freddieUser->setCountry($this->countryModel->byCode('uk'));
        $freddieUser->setEmail('john+10@vocalizr.com');
        $freddieUser->setEmailConfirmed(true);
        $freddieUser->setFirstName('Freddie');
        $freddieUser->setLastName('Mercury');
        $freddieUser->setGender('m');
        $freddieUser->setIsActive(true);
        $freddieUser->setIsProducer(false);
        $freddieUser->setIsSongwriter(false);
        $freddieUser->setIsVocalist(true);
        $freddieUser->setPassword('bcd4be6dd9250d2a033dad618358f27eff4e3a61');
        $freddieUser->setSalt('95d76cdfcef0c9af572ab5d67d396da5');
        $freddieUser->setUniqueStr('50b07da72772aeb5d40822d95c7fa1aeb791d3b4');
        $freddieUser->setStudioAccess(true);
        $freddieUser->setUsername('Freddie');
        $manager->persist($freddieUser);
        $this->addReference('user-freddie', $freddieUser);

        $nateUser = new UserInfo();
        $nateUser->setCity('Los Angeles');
        $nateUser->setCountry($this->countryModel->byCode('us'));
        $nateUser->setEmail('john+11@vocalizr.com');
        $nateUser->setEmailConfirmed(true);
        $nateUser->setFirstName('Nate');
        $nateUser->setLastName('Reusso');
        $nateUser->setGender('m');
        $nateUser->setIsActive(true);
        $nateUser->setIsProducer(false);
        $nateUser->setIsSongwriter(false);
        $nateUser->setIsVocalist(true);
        $nateUser->setPassword('bcd4be6dd9250d2a033dad618358f27eff4e3a61');
        $nateUser->setSalt('95d76cdfcef0c9af572ab5d67d396da5');
        $nateUser->setUniqueStr('50b07da72772aeb5d40822d95c7fa1aeb791d3b4');
        $nateUser->setStudioAccess(true);
        $nateUser->setUsername('Fun');
        $manager->persist($nateUser);
        $this->addReference('user-nate', $nateUser);

        $pinkUser = new UserInfo();
        $pinkUser->setCity('Los Angeles');
        $pinkUser->setCountry($this->countryModel->byCode('us'));
        $pinkUser->setEmail('john+12@vocalizr.com');
        $pinkUser->setEmailConfirmed(true);
        $pinkUser->setFirstName('Alecia');
        $pinkUser->setLastName('Moore');
        $pinkUser->setGender('f');
        $pinkUser->setIsActive(true);
        $pinkUser->setIsProducer(false);
        $pinkUser->setIsSongwriter(false);
        $pinkUser->setIsVocalist(true);
        $pinkUser->setPassword('bcd4be6dd9250d2a033dad618358f27eff4e3a61');
        $pinkUser->setSalt('95d76cdfcef0c9af572ab5d67d396da5');
        $pinkUser->setUniqueStr('50b07da72772aeb5d40822d95c7fa1aeb791d3b4');
        $pinkUser->setStudioAccess(true);
        $pinkUser->setUsername('Pink');
        $manager->persist($pinkUser);
        $this->addReference('user-pink', $pinkUser);
//        LoadUserInfoData End*/


//        LoadVocalCharacteristicData Start
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
//        LoadVocalCharacteristicData END


//        LoadVocalStyleData Start
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
//        LoadVocalStyleData END



//        UserInfo End
        $manager->flush();
    }
}
