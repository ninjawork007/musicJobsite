<?php

namespace Vocalizr\AppBundle\Service;

/**
 * Class MediaInfoService
 *
 * @package Vocalizr\AppBundle\Service
 */
class MediaInfoService
{
    private $tempDirectory;

    /**
     * MediaInfoService constructor.
     *
     * @param $tempDirectory
     */
    public function __construct($tempDirectory)
    {
        $this->tempDirectory = $tempDirectory;
    }

    /**
     * @param string $filename
     *
     * @return array
     */
    public function validateProjectAssetAudio($filename)
    {
        $filePath = $this->tempDirectory . DIRECTORY_SEPARATOR . $filename;

        $violations = [];
        $audioInfo  = $this->analyzeAudio($filePath);

        if (!$audioInfo) {
            $violations[] = 'Could not parse audio file';
            return $violations;
        }

        if (!in_array($audioInfo['format'], ['wav', 'aif', 'aiff'])) {
            $violations[] = 'Asset audio must be valid wav or aif file. Current file format is ' . $audioInfo['format'];
        }

        if ($audioInfo['bit_depth'] && ($audioInfo['bit_depth'] < 16 || $audioInfo['bit_depth'] > 24)) {
            $violations[] = 'Asset audio must have bit depth in range from 16 to 24bit. Current audio bit depth is '
                . $audioInfo['bit_depth'] . 'bit.';
        }

        if ($audioInfo['sample_rate'] < 44100 || $audioInfo['sample_rate'] > 96000) {
            $violations[] = 'Asset audio must have sample rate in range from 44.1 to 96kHz. Current sample rate is '
                . number_format($audioInfo['sample_rate'] / 1000, 0) . 'kHz';
        }

        return $violations;
    }

    /**
     * @param string $filename
     *
     * @return array
     */
    public function analyzeAudio($filename)
    {
        $data = [];

        $audioParameters = [
            'bits_per_sample' => 'bit_depth',
            'sample_rate'     => 'sample_rate',
            'dataformat'      => 'format',
            'channels'        => 'channels',
        ];

        $fileInfo = (new \getid3())->analyze($filename);

        if (!isset($fileInfo['audio'])) {
            return [];
        }

        if (isset($fileInfo['comments']['picture'][0]['data'])) {
            $data['pictureData'] = $fileInfo['comments']['picture'][0]['data'];
        } else {
            $data['pictureData'] = null;
        }

        if (isset($fileInfo['comments']['picture'][0]['image_mime'])) {
            $data['pictureMime'] = $fileInfo['comments']['picture'][0]['image_mime'];
        } else {
            $data['pictureMime'] = null;
        }

        foreach (['playtime_seconds', 'playtime_string'] as $key) {
            $data[$key] = null;

            if (isset($fileInfo[$key])) {
                $data[$key] = $fileInfo[$key];
            }
        }

        $audioInfo = $fileInfo['audio'];

        foreach ($audioParameters as $audioKey => $dataKey) {
            $data[$dataKey] = null;
            if (isset($audioInfo[$audioKey])) {
                $data[$dataKey] = $audioInfo[$audioKey];
            }
        }

        return $data;
    }
}