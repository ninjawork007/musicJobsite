<?php

namespace Vocalizr\AppBundle\Exception;

/**
 * Class CommonException
 * For all custom error.
 */
class CommonException extends \Exception implements VocalizrExceptionInterface
{
    /**
     * Constructor.
     * @param string|null $message
     */
    public function __construct($message = null)
    {
        parent::__construct($message, 500);
    }
}