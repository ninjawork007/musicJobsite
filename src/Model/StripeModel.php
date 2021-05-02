<?php


namespace App\Model;


/**
 * Class StripeModel
 * @package App\Model
 */
class StripeModel
{
    /** @var array */
    private $headers;

    /** @var string */
    private $url;

    /** @var string */
    private $fields;

    /** @var string */
    private $stripeApiKey;

    /** @var mixed */
    private $callOutput;

    /** @var int */
    private $statusCode;

    /**
     * StripeModel constructor.
     * @param array|null $headers
     * @param string $url
     * @param string $data
     * @param string $stripeApiKey
     */
    public function __construct($headers, $url, $data, $stripeApiKey)
    {
        $this->headers = $headers;
        $this->fields = $data;
        $this->stripeApiKey = $stripeApiKey;
        $this->url = $url;
    }

    public function call()
    {
        $ch = curl_init();
        if ($this->headers) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        }
        curl_setopt($ch, CURLOPT_USERPWD, $this->stripeApiKey);
        curl_setopt($ch, CURLOPT_URL, $this->url);
        if ($this->fields) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->fields);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $this->callOutput = json_decode(curl_exec($ch));
        $this->statusCode = curl_getinfo($ch)['http_code'];
        curl_close($ch);
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return mixed
     */
    public function getCallOutput()
    {
        return $this->callOutput;
    }

}