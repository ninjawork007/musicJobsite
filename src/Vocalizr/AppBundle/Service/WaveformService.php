<?php

namespace Vocalizr\AppBundle\Service;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Exception;
use Symfony\Component\Process\Process;
use Vocalizr\AppBundle\Entity\ProjectAsset;
use Vocalizr\AppBundle\Entity\ProjectAudio;
use Vocalizr\AppBundle\Entity\ProjectBid;
use Vocalizr\AppBundle\Entity\UserAudio;
use Vocalizr\AppBundle\Entity\Waveform;

/**
 * Class WaveformService
 *
 * @package Vocalizr\AppBundle\Service
 */
class WaveformService
{
    const MAX_PEAKS_COUNT = 500;

    const TYPE_PROJECT_BID = 'project_bid';

    const TYPE_USER_AUDIO = 'user_audio';

    const TYPE_PROJECT_AUDIO = 'project_audio';

    const TYPE_PROJECT_ASSET = 'project_asset';

    private $em;

    private $baseDir;

    /**
     * @var MediaInfoService
     */
    private $mediaInfoService;

    /**
     * @var string
     */
    private $scClientId;

    private $entityWaveformTypeMap = [
        ProjectBid::class   => self::TYPE_PROJECT_BID,
        UserAudio::class    => self::TYPE_USER_AUDIO,
        ProjectAudio::class => self::TYPE_PROJECT_AUDIO,
        ProjectAsset::class => self::TYPE_PROJECT_ASSET,
    ];

    /**
     * WaveformService constructor.
     *
     * @param EntityManager    $entityManager
     * @param MediaInfoService $mediaInfoService
     * @param string           $kernelDir
     * @param string           $scClientId
     */
    public function __construct(EntityManager $entityManager, MediaInfoService $mediaInfoService, $kernelDir, $scClientId)
    {
        $this->em               = $entityManager;
        $this->baseDir          = $kernelDir . '/../';
        $this->mediaInfoService = $mediaInfoService;
        $this->scClientId       = $scClientId;
    }

    /**
     * @param mixed|object $audio
     *
     * @return Waveform|null
     */
    public function findWaveform($audio)
    {
        $metadata = $this->getWaveformMetadata($audio);

        if (!$metadata) {
            return $this->defaultWaveform();
        }

        $waveform = $this->getWaveformById($metadata[0]);

        if (!$waveform) {
            return null;
        }

        if ($this->isWaveformValid($waveform)) {
            // Save some space.
            $this->em->detach($waveform);
            return $waveform;
        } else {
            $this->em->remove($waveform);
            $this->em->flush();
            return null;
        }
    }

    /**
     * @param mixed $entity
     *
     * @return Waveform
     */
    public function findOrGenerateWaveform($entity)
    {
        $waveform = $this->findWaveform($entity);
        if ($waveform) {
            return $waveform;
        }

        $data = $this->getWaveformMetadata($entity);

        if (!$data) {
            return null;
        }

        list($id, $filename) = $data;
        return $this->generateWaveform($id, $filename);
    }

    /** @noinspection PhpDocMissingThrowsInspection */

    /**
     * @param string        $id
     * @param string        $filename
     * @param Waveform|null $waveform
     *
     * @return Waveform
     */
    public function generateWaveform($id, $filename, $waveform = null)
    {
        if (!$waveform) {
            $waveform = new Waveform();
        }

        try {
            $peaks = $this->generateForFile($filename);
        } catch (Exception $exception) {
            error_log('Waveform generator: Could not generate waveform. ' . $exception->getMessage());
            return $this->defaultWaveform();
        }

        if (empty($peaks)) {
            return $this->defaultWaveform();
        }

        $waveform
            ->setPath($id)
            ->setPeaks($peaks)
        ;

        $this->em->persist($waveform);
        /** @noinspection PhpUnhandledExceptionInspection */
        $this->em->flush();

        return $waveform;
    }

    /**
     * @param mixed $entity
     *
     * @return array
     */
    public function getDeferredData($entity)
    {
        return [
            'type' => $this->getAudioType($entity),
            'id'   => $entity->getId(),
        ];
    }

    /**
     * @param string $type
     * @param int    $id
     *
     * @return object|null
     */
    public function findAudioByDeferredData($type, $id)
    {
        $className = array_search($type, $this->entityWaveformTypeMap);
        if (!$className) {
            return null;
        }

        return $this->em->getRepository($this->em->getClassMetadata($className)->getName())->find($id);
    }

    /**
     * @param string $id
     *
     * @return object|Waveform|null
     */
    private function getWaveformById($id)
    {
        return $this->getRepo()->find($id);
    }

    /**
     * @param $filePath
     *
     * @return string|null
     *
     * @throws Exception
     */
    private function getDecoder($filePath)
    {
        $urlHost = parse_url($filePath, PHP_URL_HOST);

        if ($urlHost && strpos($urlHost, 'soundcloud.com') !== false) {
            return 'soundcloud';
        }

        $fileInfo = $this->mediaInfoService->analyzeAudio($filePath);

        switch ($format = $fileInfo['format']) {
            case 'mp4':
            case 'aac':
                return 'faad';
            // Native-supported formats
            case 'mp3':
            case 'wav':
            case 'aiff':
                return null;
        }

        throw new Exception('Unknown format ' . $format);
    }

    /**
     * @param string $decoder  - currently only faad is supported.
     * @param string $filePath
     *
     * @return string
     *
     * @throws Exception
     */
    private function decode($decoder, $filePath)
    {
        $outputPath = null;
        $cmd        = null;
        switch ($decoder) {
            case 'faad':
                $outputPath = $this->tempFilename('decode', 'wav');
                $cmd        = sprintf(
                    'faad -o %s %s',
                    escapeshellarg($outputPath),
                    escapeshellarg($filePath)
                );
                break;
            case 'soundcloud':
                $outputPath = $this->downloadSoundcloudAudio($filePath);
                break;
            default:
                throw new Exception('Unknown decoder ' . $decoder);
        }

        if ($cmd) {
            $process = new Process($cmd);
            $process->run();
            if ($process->getExitCode() !== 0) {
                throw new Exception(sprintf(
                    'Decode process exited with code %d (%s). Output: %s; Error output: %s.',
                    $process->getExitCode(),
                    $process->getExitCodeText(),
                    $process->getOutput(),
                    $process->getErrorOutput()
                ));
            }
        }

        if (!file_exists($outputPath)) {
            throw new Exception(
                'File that should be generated by decoder ' . $decoder . ' does not exist!'
            );
        }

        return $outputPath;
    }

    /**
     * @param mixed|object $audio
     *
     * @return string|null
     */
    private function getAudioType($audio)
    {
        foreach ($this->entityWaveformTypeMap as $className => $type) {
            if ($audio instanceof $className) {
                return $type;
            }
        }

        return null;
    }

    /**
     * @param mixed|object $audio
     *
     * @return array|null
     */
    private function getWaveformMetadata($audio)
    {
        $audioType = $this->getAudioType($audio);

        switch ($audioType) {
            case self::TYPE_PROJECT_BID:
                $path = $audio->getPath();
                if (!$audio->getPath()) {
                    error_log('Project audio without path');
                    return null;
                }
                $projectId = $audio->getProject()->getId();
                $filename  = $this->baseDir . 'uploads/audio/project/' . $projectId . '/bids/' . $path;
                return [$path, $filename];
            case self::TYPE_PROJECT_AUDIO:
                $path = $audio->getPath();
                if (!$path) {
                    error_log('Project audio without path');
                    return null;
                }
                $filename = $this->baseDir . 'uploads/audio/project/' . $audio->getId() . '/' . $path;
                $id       = 'project_audio_' . $path;
                return [$id, $filename];
            case self::TYPE_USER_AUDIO:
                $path = $audio->getPath();
                if ($path) {
                    $filename = $this->baseDir . 'uploads/audio/user/' . $audio->getUserInfo()->getId() . '/' . $path;
                } elseif ($audio->getScStreamUrl()) {
                    $filename = $path = $audio->getScStreamUrl();
                } else {
                    error_log('User audio without path and SC link');
                    return null;
                }
                return [$path, $filename];
            case self::TYPE_PROJECT_ASSET:
                $projectId = $audio->getProject()->getId();
                $path      = $audio->getPreviewPath() ? $audio->getPreviewPath() : $audio->getPath();
                $id        = 'project_asset_' . $path;
                $filename  = $this->baseDir . 'uploads/project/' . $projectId . '/assets/' . $path;
                return [$id, $filename];
            default:
                error_log('Unknown audio type for waveform generator: ' . $audioType);
                return null;
        }
    }

    /**
     * @param string $filePath
     *
     * @return array|null
     *
     * @throws Exception
     */
    private function generateForFile($filePath)
    {
        try {
            $decoderName = $this->getDecoder($filePath);
        } catch (Exception $e) {
            // Try to bypass decoder if format seems unsupported.
            $decoderName = null;
        }

        if ($decoderName) {
            $filePath = $this->decode($decoderName, $filePath);
        }

        $tempFilename = $this->tempFilename('waveform_json', 'json');

        $execCommand = 'audiowaveform -i ' . escapeshellarg($filePath) . ' -z 10000 -o ' . escapeshellarg($tempFilename);

        $process = new Process($execCommand);
        $process->run();

        if ($decoderName && file_exists($filePath)) {
            unlink($filePath);
        }

        if (!file_exists($tempFilename)) {
            throw new Exception(
                'File that should be generated by audiowaveform does not exist! Process output: '
                . $process->getOutput() . ', Process error output: ' . $process->getErrorOutput() . '.'
            );
        }

        $data = file_get_contents($tempFilename);

        unlink($tempFilename);

        $decodedData = json_decode($data);

        if (!$decodedData) {
            throw new Exception('Audiowaveform has been generated empty or not valid file');
        }

        $peaksArray = $decodedData->data;

        if (empty($peaksArray)) {
            return [];
        }

        $scaledPeaks = $this->scaleArray($peaksArray);

        return $scaledPeaks;
    }

    /**
     * @param array $array
     *
     * @return array
     */
    private function scaleArray($array)
    {
        $originalCount = count($array);
        $array         = array_map('abs', $array);
        $maxValue      = max($array);
        $resultCount   = self::MAX_PEAKS_COUNT;
        $scaleFactor   = $resultCount / $originalCount;

        if ($maxValue < 1) {
            $maxValue = 1;
        }

        // Normalize array - set all values between 0 and 1 with precision 2.
        $array = array_map(function ($value) use ($maxValue) {
            return round(($value / $maxValue), 2);
        }, $array);

        $result = [];

        // Return normalized array if scale is not needed.
        if ($scaleFactor >= 1) {
            return $array;
        }

        // Scaling the array
        $lastIndex = null;
        foreach ($array as $index => $value) {
            $resultIndex = (int) ($index * $scaleFactor);
            if ($resultIndex === $lastIndex) {
                $lastIndex = $resultIndex;
                continue;
            }
            $lastIndex = $resultIndex;

            $result[$resultIndex] = $value;
        }

        return $result;
    }

    /**
     * @return EntityRepository
     */
    private function getRepo()
    {
        return $this->em->getRepository(Waveform::class);
    }

    /**
     * @return Waveform
     */
    private function defaultWaveform()
    {
        $peaks = [0.8];

        $waveform = new Waveform();
        $waveform->setPeaks($peaks);

        return $waveform;
    }

    private function tempFilename($prefix, $format = '')
    {
        return sys_get_temp_dir() . DIRECTORY_SEPARATOR .
            uniqid($prefix . '_', true) .
            ($format ? '.' . $format : '');
    }

    /**
     * @param Waveform|null $waveform
     *
     * @return bool
     */
    private function isWaveformValid($waveform)
    {
        if (!$waveform || empty($waveform->getPeaks())) {
            error_log(sprintf('Waveform %s is not valid: empty peak array', $waveform->getPath()));
            return false;
        }

        $peaks = $waveform->getPeaks();

        if (max($peaks) > 3 || $min = min($peaks) < 0) {
            error_log(sprintf(
                'Waveform %s is not valid: loudness is out of range: min(%.2f), max(%.2f)',
                $waveform->getPath(),
                max($peaks),
                min($peaks)
            ));
            return false;
        }

        return true;
    }

    /**
     * @param string $url
     *
     * @return string
     */
    private function downloadSoundcloudAudio($url)
    {
        $url .= '?client_id=' . $this->scClientId;
        $filename = $this->tempFilename('sc_audio', 'mp3');
        $fp       = fopen($filename, 'w');
        $ch       = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FILE           => $fp,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);

        return $filename;
    }
}