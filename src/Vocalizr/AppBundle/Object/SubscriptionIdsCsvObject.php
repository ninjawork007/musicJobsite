<?php

namespace Vocalizr\AppBundle\Object;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class SubscriptionIdsCsvObject
 *
 * @package Vocalizr\AppBundle\Object
 */
class SubscriptionIdsCsvObject
{
    /**
     * @var UploadedFile
     */
    private $file;

    /**
     * @return UploadedFile
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param UploadedFile $file
     *
     * @return SubscriptionIdsCsvObject
     */
    public function setFile($file)
    {
        $this->file = $file;
        return $this;
    }
}