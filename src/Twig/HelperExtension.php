<?php

namespace App\Twig;

use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use App\Entity\Project;
use App\Repository\ProjectRepository;
use App\Repository\UserInfoRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Symfony\Component\Intl\Intl;

class HelperExtension extends AbstractExtension
{
    /**
     * @var UrlGeneratorInterface
     */
    private $generator;

    /**
     * @var KernelInterface
     */
    private $kernel;

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container, UrlGeneratorInterface $generator, KernelInterface $kernel)
    {
        $this->container = $container;
        $this->generator = $generator;
        $this->kernel    = $kernel;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('timeAgoString', [$this, 'timeAgoFilter']),
            new TwigFilter('timeLeftString', [$this, 'timeLeftFilter']),
            new TwigFilter('timeLeftSingle', [$this, 'timeLeftSingle']),
            new TwigFilter('daysOffset', [$this, 'daysOffsetFilter']),
            new TwigFilter('starRating', [$this, 'starRatingFilter']),
            new TwigFilter('unserialize', [$this, 'unserializeFilter']),
            new TwigFilter('jsonDecode', [$this, 'jsonDecodeFilter']),
            new TwigFilter('jsonEncode', [$this, 'jsonEncodeFilter']),
            new TwigFilter('addPricePercent', [$this, 'addPricePercentFilter']),
            new TwigFilter('getPricePercent', [$this, 'getPricePercentFilter']),
            new TwigFilter('json_decode', [$this, 'jsonDecodeFilter']),
            new TwigFilter('parseWalletDesc', [$this, 'parseWalletDescFilter']),
            new TwigFilter('assetExists', [$this, 'assetExists']),
            new TwigFilter('projectAssetExists', [$this, 'projectAssetExists']),
            new TwigFilter('marketplaceItemAssetExists', [$this, 'marketplaceItemAssetExists']),
            new TwigFilter('countryName', [$this, 'countryNameFilter']),
            new TwigFilter('formatBytes', [$this, 'formatBytes']),
            new TwigFilter('array_filter', [$this, 'array_filter'])
        ];
    }

    public function getFunctions()
    {
        return [
            'routeStartsWith' => new TwigFunction( 'routeStartsWith', [$this, 'routeStartsWith']),
            'countProducers'  => new TwigFunction('countProducers', [$this, 'countProducers']),
            'countJob'        => new TwigFunction('countJob', [$this, 'countJob']),
            'countVocalists'  => new TwigFunction('countVocalists', [$this, 'countVocalists'])
        ];
    }

    public function routeStartsWith($route, $string)
    {
        if (strpos($route, $string) == 0) {
            return true;
        } else {
            return false;
        }
    }

    public function countProducers()
    {
        $em = $this->container->get('doctrine.orm.entity_manager');
        /** @var UserInfoRepository $userInfoRepo */
        $userInfoRepo = $em->getRepository('App:UserInfo');

        return count($userInfoRepo->findBy(['is_producer' => true]));
    }

    public function countVocalists()
    {
        $em = $this->container->get('doctrine.orm.entity_manager');
        /** @var UserInfoRepository $userInfoRepo */
        $userInfoRepo = $em->getRepository('App:UserInfo');

        return count($userInfoRepo->findBy(['is_vocalist' => true]));
    }

    public function countJob()
    {
        $em = $this->container->get('doctrine.orm.entity_manager');
        /** @var ProjectRepository $projectRepo */
        $projectRepo = $em->getRepository('App:Project');

        return count($projectRepo->findBy(['is_active' => true]));
    }

    public function daysOffsetFilter($datetime)
    {
        $now      = new \DateTime();
        $datetime = \DateTime::createFromFormat('U', $datetime);

        $diff = $now->diff($datetime)->format('%a');

        return $diff;
    }

    public function timeAgoFilter($datetime)
    {
        if ($datetime instanceof \DateTime) {
            $time = time() - $datetime->getTimestamp();
        } else {
            $time = time() - strtotime($datetime);
        }

        $tokens = [
            31536000 => 'year',
            2592000  => 'month',
            604800   => 'week',
            86400    => 'day',
            3600     => 'hour',
            60       => 'minute',
            1        => 'second',
        ];

        foreach ($tokens as $unit => $text) {
            if ($time < $unit) {
                continue;
            }
            $numberOfUnits = floor($time / $unit);
            if ($numberOfUnits == 1) {
                if ($text == 'hour') {
                    $numberOfUnits = 'an';
                } else {
                    $numberOfUnits = 'a';
                }
            }
            return $numberOfUnits . ' ' . $text . (($numberOfUnits > 1) ? 's' : '');
        }

        return 'a few seconds';
    }

    public function timeLeftFilter($datetime)
    {
        if (!$datetime) {
            return null;
        }
        $diff = $datetime->getTimestamp() - time();

        // If time has passed, return false
        if ($diff < 0) {
            return false;
        }

        // immediately convert to days
        $temp = $diff / 86400; // 60 sec/min*60 min/hr*24 hr/day=86400 sec/day

        // days
        $tokens['day'] = $days = floor($temp);

        $temp = 24 * ($temp - $days);

        // hours
        $tokens['hour'] = $hours = floor($temp);
        $temp           = 60 * ($temp - $hours);

        // minutes
        $tokens['min'] = $minutes = floor($temp);
        $temp          = 60 * ($temp - $minutes);

        // seconds
        $seconds = floor($temp);

        $result = null;
        foreach ($tokens as $text => $unit) {
            if ($unit > 0) {
                $result[] = $unit . ' ' . $text . ($unit > 1 ? 's' : '');
            }
        }
        if (!is_array($result)) {
            return false;
        }

        if (count($result) > 2) {
            array_pop($result);
        }

        return implode(' ', $result);
    }

    public function timeLeftSingle($datetime)
    {
        if (!$datetime) {
            return null;
        }
        $diff = $datetime->getTimestamp() - time();

        // If time has passed, return false
        if ($diff < 0) {
            return false;
        }

        // immediately convert to days
        $temp = $diff / 86400; // 60 sec/min*60 min/hr*24 hr/day=86400 sec/day

        // days
        $tokens['day'] = $days = floor($temp);

        $temp = 24 * ($temp - $days);

        // hours
        $tokens['hour'] = $hours = floor($temp);
        $temp           = 60 * ($temp - $hours);

        // minutes
        $tokens['min'] = $minutes = floor($temp);
        $temp          = 60 * ($temp - $minutes);

        // seconds
        $seconds = floor($temp);

        $result = null;
        foreach ($tokens as $text => $unit) {
            if ($unit > 0) {
                $result[] = [
                    'num'  => $unit,
                    'text' => $text . ($unit > 1 ? 's' : ''),
                ];
            }
        }
        if (!is_array($result)) {
            return false;
        }

        if (count($result) > 2) {
            array_pop($result);
        }

        return $result[0];
    }

    public function starRatingFilter($rating)
    {
        $maxStars   = 5;
        $emptyStars = $maxStars - $rating;

        echo str_repeat('<i class="icon-star"></i>', $rating);
        echo str_repeat('<i class="icon-star-empty"></i>', $emptyStars);
    }

    public function unserializeFilter($string)
    {
        return unserialize($string);
    }

    public function addPricePercentFilter($price, $percent, $format = false)
    {
        $total_price = $price *= (1 + $percent / 100);
        if ($format) {
            return sprintf('%.2f', $total_price);
        }
        return sprintf('%d', $total_price);
    }

    public function getPricePercentFilter($price, $percent)
    {
        $price = ($price / 100) * $percent;
        return sprintf('%.2f', $price);
    }

    public function jsonEncodeFilter($str)
    {
        return json_encode($str);
    }

    public function jsonDecodeFilter($str)
    {
        return json_decode($str, true);
    }

    /**
     * Parse wallet description, replace variables with data
     *
     * @param string $str
     * @param string $data
     * @param null $type
     *
     * @return mixed|string
     */
    public function parseWalletDescFilter($str, $data, $type = null)
    {
        $patternOverrideMap = [
            'Escrow payment to {username} for gig {project}' => 'Payment into Vocalizr Payment Protection for Gig {project} with {username}',
            'Escrow payment to contest {project}'            => 'Payment into Vocalizr Payment Protection for Contest {project}',
            'Payment for gig {project} from {username}'      => 'Payment for Gig {project} with {username}',
            'Upgrade charges for gig {project}'              => 'Upgrade charges for Gig {project}',
            'Refund gig fee for {project} escrow'            => 'Refund of Gig fee for {project}',
            'Transaction fee'                                => 'PayPal transaction fee',
            'Refund payment for {project} escrow'            => 'Refund of payment for {project}',
            'Cancel escrow payment to contest {project}'     => 'Refund for cancelled Contest {project}',
        ];

        $str = str_replace('Paypal', 'PayPal', $str);

        if (isset($patternOverrideMap[$str])) {
            $str = $patternOverrideMap[$str];
        }

        /** @var object $data */
        $data = json_decode($data);

        if (isset($data->status_string)) {
            $str .= sprintf(' (%s)', $data->status_string);
        }

        if (stristr($str, '{project}') && isset($data->projectUuid)) {
            // Get url for project
            if (isset($data->projectType) && $data->projectType == 'contest') {
                $url = $this->generator->generate('contest_view', ['uuid' => $data->projectUuid]);
            } else {
                /** @var EntityManager $em */
                $em      = $this->kernel->getContainer()->get('doctrine.orm.entity_manager');
                $project = $em->getRepository('App:Project')->getProjectByUuid($data->projectUuid);

                if ($project && $project->getProjectBid()) {
                    $url = $this->generator->generate('project_studio', ['uuid' => $data->projectUuid]);
                } else {
                    $url = $this->generator->generate('project_view', ['uuid' => $data->projectUuid]);
                }
            }
            $replace = '<a href="' . $url . '">' . $data->projectTitle . '</a>';
            $str     = str_replace('{project}', $replace, $str);
        }
        if (stristr($str, '{username}') && isset($data->username)) {
            // Get url for project
            $url     = $this->generator->generate('user_view', ['username' => $data->username]);
            $replace = '<a href="' . $url . '">' . $data->username . '</a>';
            $str     = str_replace('{username}', $replace, $str);
        }

        return $str;
    }

    public function assetExists($path)
    {
        $webRoot = realpath($this->kernel->getRootDir() . '/../web/');
        $toCheck = $webRoot . $path;

        // check if the file exists
        if (!file_exists($toCheck)) {
            return false;
        }

        // check if file is well contained in web/ directory (prevents ../ in paths)
        if (strncmp($webRoot, $toCheck, strlen($webRoot)) !== 0) {
            return false;
        }

        return true;
    }

    public function projectAssetExists($path, $projectId)
    {
        $uploadRoot = realpath($this->kernel->getRootDir() . '/../uploads/');
        $toCheck    = $uploadRoot . DIRECTORY_SEPARATOR . 'project' . DIRECTORY_SEPARATOR . $projectId . DIRECTORY_SEPARATOR .
                'assets' . DIRECTORY_SEPARATOR . $path;

        // check if the file exists
        if (!file_exists($toCheck)) {
            return false;
        }

        // check if file is well contained in web/ directory (prevents ../ in paths)
        if (strncmp($uploadRoot, $toCheck, strlen($uploadRoot)) !== 0) {
            return false;
        }

        return true;
    }

    public function marketplaceItemAssetExists($path, $marketplaceItemId)
    {
        $uploadRoot = realpath($this->kernel->getRootDir() . '/../uploads/');
        $toCheck    = $uploadRoot . DIRECTORY_SEPARATOR . 'marketplace' . DIRECTORY_SEPARATOR . $marketplaceItemId . DIRECTORY_SEPARATOR .
                'assets' . DIRECTORY_SEPARATOR . $path;

        // check if the file exists
        if (!file_exists($toCheck)) {
            return false;
        }

        // check if file is well contained in web/ directory (prevents ../ in paths)
        if (strncmp($uploadRoot, $toCheck, strlen($uploadRoot)) !== 0) {
            return false;
        }

        return true;
    }

    /**
     * Get full country name by passing country code
     */
    public function countryNameFilter($countryCode)
    {
        $countryCode = strtoupper($countryCode);
        \Locale::setDefault('en');
        $countries   = Countries::getNames();
        return array_key_exists($countryCode, $countries)
           ? $countries[$countryCode]
           : $countryCode;
    }

    public function getName()
    {
        return 'helper_extension';
    }

    public function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow   = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow   = min($pow, count($units) - 1);

        // Uncomment one of the following alternatives
        $bytes /= pow(1024, $pow);
        // $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
