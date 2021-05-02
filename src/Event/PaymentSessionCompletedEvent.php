<?php

namespace App\Event;

use Symfony\Component\EventDispatcher\Event;
use App\Entity\PaymentSessionData;
use App\Entity\Project;
use App\Entity\UserInfo;
use App\Service\PayPalService;

/**
 * Class PaymentCompletedEvent
 * @package App\Event
 */
class PaymentSessionCompletedEvent extends Event
{
    const METHOD_STRIPE = 'stripe';

    const NAME = 'vocalizr_app.payment.completed';

    private $method;

    private $methodData;

    private $paymentItems;

    /**
     * @var Project|null
     */
    private $project;

    /**
     * @var UserInfo|null
     */
    private $user;

    /**
     * @var PaymentSessionData|null
     */
    private $paymentSessionData;

    private $responseMessage = '';

    /**
     * PaymentSessionCompletedEvent constructor.
     * @param UserInfo|null $user
     * @param Project|null $project
     * @param PaymentSessionData|null $paymentSessionData
     * @param array $methodData
     * @param array $paymentItems
     * @param string $method
     */
    public function __construct(
        UserInfo $user = null,
        Project $project = null,
        PaymentSessionData $paymentSessionData = null,
        $methodData = [],
        $paymentItems = [],
        $method = self::METHOD_STRIPE
    ) {
        $this->user = $user;
        $this->method = $method;
        $this->project = $project;
        $this->methodData = $methodData;
        $this->paymentItems = $paymentItems;
        $this->paymentSessionData = $paymentSessionData;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @return array
     */
    public function getMethodData()
    {
        return $this->methodData;
    }

    /**
     * @param string $field
     * @return mixed
     */
    public function getMethodDataField($field)
    {
        return $this->methodData[$field];
    }

    /**
     * @return array
     */
    public function getPaymentItems()
    {
        return $this->paymentItems;
    }

    /**
     * @return Project|null
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @return UserInfo|null
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param string $item
     * @return bool
     */
    public function hasItem($item)
    {
        return isset($this->paymentItems[$item]);
    }

    /**
     * @param string $item
     * @return array
     */
    public function getItem($item)
    {
        return ($this->hasItem($item) ? $this->paymentItems[$item] : []);
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
     * @return PaymentSessionCompletedEvent
     */
    public function setResponseMessage($responseMessage)
    {
        $this->responseMessage = $responseMessage;
        return $this;
    }

    /**
     * @param string $part
     * @return PaymentSessionCompletedEvent
     */
    public function addResponseMessage($part)
    {
        $this->responseMessage .= ($this->responseMessage ? ', ' : '') . $part;
        return $this;
    }

    /**
     * @return bool
     */
    public function hasSubscription()
    {
        return ($this->methodData['mode'] === 'subscription');
    }

    /**
     * @return int
     */
    public function getTotalCents()
    {
        return (int)$this->methodData['amount_total'];
    }

    /**
     * @return int
     */
    public function getTotalCentsWithoutSubscription()
    {
        $totalAmount = $this->getTotalCents();

        if ($this->hasSubscription()) {
            $totalAmount -= PayPalService::MONTHLY_PAYMENT_GROSS * 100;
        }

        return $totalAmount;
    }

    /**
     * @return PaymentSessionData|null
     */
    public function getPaymentSessionData()
    {
        return $this->paymentSessionData;
    }

    /**
     * @param PaymentSessionData|null $paymentSessionData
     * @return PaymentSessionCompletedEvent
     */
    public function setPaymentSessionData($paymentSessionData)
    {
        $this->paymentSessionData = $paymentSessionData;
        return $this;
    }
}