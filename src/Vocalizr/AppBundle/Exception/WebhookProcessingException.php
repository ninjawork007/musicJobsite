<?php

namespace Vocalizr\AppBundle\Exception;

use Exception;

/**
 * Class WebhookProcessingException
 * @package Vocalizr\AppBundle\Exception
 */
class WebhookProcessingException extends Exception implements VocalizrExceptionInterface
{
    private $responseStatus;

    private $responseMessage;

    /**
     * WebhookProcessingException constructor.
     * @param string $responseMessage
     * @param int $responseStatus
     * @param null $previous
     */
    public function __construct($responseMessage = 'something happened', $responseStatus = 400, $previous = null)
    {
        $this->responseMessage = $responseMessage;
        $this->responseStatus = $responseStatus;

        parent::__construct('Could not process webhook: ' . $responseMessage, $responseStatus, $previous);
    }

    /**
     * @return int
     */
    public function getResponseStatus()
    {
        return $this->responseStatus;
    }

    /**
     * @return string
     */
    public function getResponseMessage()
    {
        return $this->responseMessage;
    }
}