<?php

namespace Vocalizr\AppBundle\Service;

use Exception;
use SimpleImage;

/**
 * Class ProcessImageService
 * @package Vocalizr\AppBundle\Service
 */
class ProcessImageService
{
    private $defaultParameters = [
        'processing' => [],
        'quality'    => 90,
        'relative'   => true,
    ];

    /**
     * @var string
     */
    private $webDir;

    /**
     * ImageUploadService constructor.
     * @param string $webDir
     */
    public function __construct($webDir)
    {
        $this->webDir = $webDir;
    }

    /**
     * @param string $filePath
     * @param string $originalName
     * @param array $outputs
     * @param string $outputExtension
     * @return string
     * @throws Exception
     */
    public function processUploadedImage($filePath, $originalName, $outputs = [], $outputExtension = 'jpg')
    {
        if (!$outputExtension) {
            $outputExtension = $this->getExtension($originalName);
        }

        $forceConvert = false;
        if ($outputExtension !== $this->getExtension($originalName)) {
            $forceConvert = true;
        }

        $outputFilename = $this->buildFilename($outputExtension);
        foreach ($outputs as $outputDirectory => $parameters) {
            $parameters = array_merge($this->defaultParameters, $parameters);

            if ($parameters['relative']) {
                $outputDirectory = $this->webDir . DIRECTORY_SEPARATOR . $outputDirectory;
            }

            $this->processImage(
                $filePath,
                $this->getExtension($originalName),
                $outputDirectory,
                $outputFilename,
                $parameters,
                $forceConvert
            );
        }

        return $outputFilename;
    }

    /**
     * @param string $webPath
     */
    public function removeImage($webPath)
    {
        @unlink($this->webDir . $webPath);
    }

    /**
     * @param string $uploadedFilePath
     * @param string $originalExtension
     * @param string $directory
     * @param string $filename
     * @param array $parameters
     * @param bool $forceConvert
     * @throws Exception
     */
    private function processImage($uploadedFilePath, $originalExtension, $directory, $filename, $parameters, $forceConvert)
    {
        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }

        $filePath = $directory . DIRECTORY_SEPARATOR . $filename;

        if (empty($parameters['processing']) && !$forceConvert) {
            copy($uploadedFilePath, $filePath);
        } else {
            $image = new SimpleImage();
            $image->load($uploadedFilePath);

            $rotateAngle = $this->getRotationAngle($uploadedFilePath, $originalExtension);

            if ($rotateAngle !== 0) {
                $image->rotate($rotateAngle);
            }

            foreach ($parameters['processing'] as $name => $processingData) {
                if ($name === 'thumbnail') {
                    $processingData = array_merge(['width' => null, 'height' => null], $processingData);

                    $this->thumbnailProcessing($image, $processingData['width'], $processingData['height']);

                } elseif ($name = 'square') {
                    $image->square_crop($processingData);
                }
            }

            $image->save($filePath, $parameters['quality']);
        }
    }

    /**
     * @param SimpleImage $image
     * @param int|null $destWidth - viewport width
     * @param int|null $destHeight - viewport height
     */
    private function thumbnailProcessing(SimpleImage $image, $destWidth, $destHeight)
    {
        if (!$destWidth) {
            if ($image->get_height() > $destHeight) {
                $image->fit_to_height($destHeight);
            }
        } elseif (!$destHeight) {
            if ($image->get_width() > $destWidth) {
                $image->fit_to_width($destWidth);
            }
        } else {
            $widthDiff  = $destWidth / $image->get_width();
            $heightDiff = $destHeight / $image->get_height();

            $resizeRatio  = max($widthDiff, $heightDiff);
            $resizeWidth  = $image->get_width() * $resizeRatio;
            $resizeHeight = $image->get_height() * $resizeRatio;

            $centerX = abs($destWidth - $resizeWidth) / 2;
            $centerY = abs($destHeight - $resizeHeight) / 2;

            $image->resize($resizeWidth, $resizeHeight);
            $image->crop($centerX, $centerY, $destWidth + $centerX, $destHeight + $centerY);
        }
    }

    /**
     * @param string $extension
     * @return string
     */
    private function buildFilename($extension)
    {
        return sprintf('%s.%s', uniqid('', true), $extension);
    }

    /**
     * @param string $filePath
     * @param string $originalExtension
     * @return int
     */
    private function getRotationAngle($filePath, $originalExtension)
    {
        try {
            if ($originalExtension !== 'png') {
                @$exif = exif_read_data($filePath);

                if (is_array($exif) && array_key_exists('Orientation', $exif)) {
                    switch ($exif['Orientation']) {
                        case 3:
                            return 180;
                        case 6:
                            return 90;
                        case 8:
                            return -90;
                    }
                }
            }
        } catch (\Error $exifError) {
            die($exifError->getMessage());
        }

        return 0;
    }

    /**
     * @param string $filePath
     * @return string
     */
    private function getExtension($filePath)
    {
        @$pathInfo = pathinfo($filePath);
        if ($pathInfo && isset($pathInfo['extension'])) {
            return $pathInfo['extension'];
        } else {
            return null;
        }
    }
}