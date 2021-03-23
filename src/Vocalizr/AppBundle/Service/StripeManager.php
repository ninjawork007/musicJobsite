<?php

namespace Vocalizr\AppBundle\Service;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Stripe\Customer;
use Stripe\Stripe;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Vocalizr\AppBundle\Entity\UserInfo;

/**
 * Class StripeManager
 * @package Vocalizr\AppBundle\Service
 */
class StripeManager
{
    private $apiKey;

    /** @var Client */
    private $customClient;
    private $baseUrl;

    /**
     * @var string
     */
    private $env;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * StripeManager constructor.
     * @param ContainerInterface $container
     * @param string $apiKey
     * @param string $baseUrl
     * @param string $env
     */
    public function __construct(ContainerInterface $container, $apiKey, $baseUrl, $env)
    {
        $this->apiKey  = $apiKey;
        $this->baseUrl = $baseUrl;
        $this->env = $env;
        $this->container = $container;

        $this->customClient = new Client();

        Stripe::setApiKey($this->apiKey);
    }

    /**
     * @param UserInfo $user
     * @param string $priceId
     * @param string $successUrl
     * @param string|null $cancelUrl
     * @param string
     *
     * @return array
     *
     * @throws GuzzleException
     */
    public function getSessionForSingleItem(UserInfo $user, $priceId, $successUrl, $cancelUrl = null, $mode = 'payment')
    {
        return $this->getStripeSession($user, [[
            'price'    => $priceId,
            'quantity' => 1,
        ]], $successUrl, $cancelUrl, $mode);
    }

    /**
     * @param UserInfo $user
     * @param array $lineItems
     * @param string $successUrl
     * @param string $cancelUrl
     * @param string $mode
     * @return array
     * @throws GuzzleException
     */
    public function getStripeSession(UserInfo $user, $lineItems, $successUrl, $cancelUrl = null, $mode = 'payment')
    {
        if (!$cancelUrl) {
            $cancelUrl = $successUrl;
        }

        $data = [
            'payment_method_types' => ['card'],
            'customer'             => $this->getOrCreateCustomerId($user),
            'line_items'           => $lineItems,
            'mode'                 => $mode,
            'success_url'          => $successUrl,
            'cancel_url'           => $cancelUrl,
        ];

        return $this->call('/checkout/sessions', $data);
    }

    /**
     * @param $chargeId
     * @return array
     * @throws GuzzleException
     */
    public function getRefund($chargeId)
    {

        return $this->call('/refunds', [
            'charge' => $chargeId,
        ]);
    }

    /**
     * @param $chargeId
     * @param $amount
     * @return array
     * @throws GuzzleException
     */
    public function getRefundContest($chargeId, $amount)
    {

        return $this->call('/refunds', [
            'charge' => $chargeId,
            'amount' => $amount
        ]);
    }

    /**
     * @param $subscriptionId
     * @return array
     * @throws GuzzleException
     */
    public function getCancelSubscription($subscriptionId)
    {
        return $this->call('/subscriptions/' . $subscriptionId, [], [], 'DELETE');
    }

    /**
     * @param UserInfo $user
     * @return string|null
     * @throws Exception
     */
    public function getOrCreateCustomerId(UserInfo $user)
    {
        if ($user->getStripeCustId()) {
            return $user->getStripeCustId();
        }

        $customer = Customer::create([
            'email' => $user->getEmail(),
        ]);

        $customerId = $customer->id;

        if (!$customerId) {
            throw new Exception('No customer id');
        }

        $user->setStripeCustId($customerId);

        $em = $this->container->get('doctrine.orm.entity_manager');
        $em->persist($user);
        $em->flush();

        return $customerId;
    }

    /**
     * @param $endpoint
     * @param $dataArray
     * @param array $query
     * @param string $method
     * @return array
     * @throws GuzzleException
     */
    public function call($endpoint, $dataArray = [], $query = [], $method = 'POST')
    {
        $options = [
            RequestOptions::AUTH        => [$this->apiKey, ''],
            RequestOptions::HTTP_ERRORS => false,
        ];

        if ($dataArray) {
            $options[RequestOptions::FORM_PARAMS] = $dataArray;
        }

        if ($query) {
            $options[RequestOptions::QUERY] = $query;
        }

        $response = $this->customClient->request($method, $this->baseUrl . $endpoint, $options);

        $responseContent = $response->getBody()->getContents();
        if ($response->getStatusCode() > 399) {
            error_log(
                "Stripe returned " . $response->getStatusCode() .
                " error. Endpoint: $endpoint, response: " . $responseContent .
                ", data: " . http_build_query($dataArray)
            );
        }

        return json_decode($responseContent, true);
    }

    /**
     * @param string $sessionId
     * @param int $limit
     * @return array
     */
    public function getLineItemsArray($sessionId, $limit = 15)
    {
        $responseData = $this->call(sprintf('/checkout/sessions/%s/line_items', $sessionId), [], ['limit' => $limit], 'GET');

        return $responseData['data'];
    }
}
