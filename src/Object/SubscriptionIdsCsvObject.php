<?php

namespace App\Object;

use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class SubscriptionIdsCsvObject
 *
 * @package App\Object
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