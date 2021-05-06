<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\UserInfo;

/**
 * Class StripeWebhookEvent
 * @package App\Event
 */
class StripeWebhookEvent extends Event
{
    const NAME = 'vocalizr_app.stripe.webhook';

    const EVENT_IDENTITY_REPORT_UNVERIFIED      = 'identity.verification_report.unverified';
    const EVENT_IDENTITY_REPORT_VERIFIED        = 'identity.verification_report.verified';
    const EVENT_IDENTITY_INTENT_REQUIRES_ACTION = 'identity.verification_intent.requires_action';

    const EVENT_SUBSCRIPTION_DELETED   = 'customer.subscription.deleted';
    const EVENT_SUBSCRIPTION_UPDATED   = 'customer.subscription.updated';

    const EVENT_CHARGE_SUCCEED         = 'charge.succeeded';
    const EVENT_CHARGE_FAILED          = 'charge.failed';
    const EVENT_INVOICE_PAYMENT_FAILED = 'invoice.payment_failed';

    const EVENT_CHECKOUT_SESSION_COMPLETED = 'checkout.session.completed';

    /**
     * @var string|null
     */
    private $apiEvent;

    /**
     * @var string
     */
    private $responseMessage = '';

    /**
     * @var int
     */
    private $responseStatus = 200;

    /**
     * @var UserInfo|null
     */
    private $user;

    /**
     * @var array
     */
    private $apiPayload;

    /** @var Request */
    private $request;

    /**
     * @var bool
     */
    private $fulfilled = false;

    /**
     * StripeWebhookEvent constructor.
     * @param string $apiEvent
     * @param UserInfo|null $user
     * @param array $apiData
     * @param Request|null $request
     */
    public function __construct($apiEvent, UserInfo $user = null, $apiData = [], Request $request = null)
    {
        $this->apiEvent   = $apiEvent;
        $this->user       = $user;
        $this->apiPayload = $apiData;
        $this->request    = $request;
    }

    /**
     * @return string|null
     */
    public function getApiEvent()
    {
        return $this->apiEvent;
    }

    /**
     * @param string|null $apiEvent
     * @return StripeWebhookEvent
     */
    public function setApiEvent($apiEvent)
    {
        $this->apiEvent = $apiEvent;
        return $this;
    }

    /**
     * @param string|array $compare
     * @return bool
     */
    public function matchApiEvent($compare)
    {
        if (!is_array($compare)) {
            $compare = [$compare];
        }

        return in_array($this->apiEvent, $compare);
    }

    /**
     * @return string
     */
    public function getResponseMessage()
    {
        return $this->responseMessage;
    }

    /**
     * @param string $responseMessage
     * @return StripeWebhookEvent
     */
    public function setResponseMessage($responseMessage)
    {
        $this->responseMessage = $responseMessage;
        return $this;
    }

    /**
     * @param string $part
     * @return StripeWebhookEvent
     */
    public function addResponseMessage($part)
    {
        $this->responseMessage .= ($this->responseMessage ? ', ' : '') . $part;
        return $this;
    }

    /**
     * @return UserInfo|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param UserInfo|null $user
     * @return StripeWebhookEvent
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return array
     */
    public function getApiPayload()
    {
        return $this->apiPayload;
    }

    /**
     * @return array
     */
    public function getPayloadObject()
    {
        if (isset($this->apiPayload['data']['object'])) {
            return $this->apiPayload['data']['object'];
        } else {
            return [];
        }
    }

    /**
     * @param array $apiPayload
     * @return StripeWebhookEvent
     */
    public function setApiPayload($apiPayload)
    {
        $this->apiPayload = $apiPayload;
        return $this;
    }

    /**
     * @return bool
     */
    public function isFulfilled()
    {
        return $this->fulfilled;
    }

    /**
     * @param bool $fulfilled
     * @return StripeWebhookEvent
     */
    public function setFulfilled($fulfilled)
    {
        $this->fulfilled = $fulfilled;
        return $this;
    }

    /**
     * @return int
     */
    public function getResponseStatus()
    {
        return $this->responseStatus;
    }

    /**
     * @param int $responseStatus
     * @return StripeWebhookEvent
     */
    public function setResponseStatus($responseStatus)
    {
        $this->responseStatus = $responseStatus;
        return $this;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }
}