<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Yaml\Parser;

/**
 * Class StripeConfigurationProvider
 * @package App\Service
 */
class StripeConfigurationProvider
{
    private $stripePricesFile;

    private $stripePrices;

    /**
     * StripeConfigurationLoader constructor.
     *
     * @param ParameterBagInterface $params
     */
    public function __construct(ParameterBagInterface $params)
    {
        $env     = $params->get('kernel.environment');
        $fileDir = $params->get('kernel.project_dir').'/config/packages';

        if (file_exists($fileDir . DIRECTORY_SEPARATOR . 'stripe_products_loc.yml')) {
            $filename = 'stripe_products_loc.yml';
        } elseif ($env === "prod") {
            $filename = 'stripe_products.yml';
        } else {
            $filename = 'stripe_products_dev.yml';
        }

        $this->stripePricesFile = $fileDir . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function hasProduct($key)
    {
        return isset($this->getPrices()['products'][$key]);
    }

    /**
     * @param string $key
     * @return string|null
     */
    public function getProductId($key)
    {
        if (!$this->hasProduct($key)) {
            return null;
        }

        $product = $this->getPrices()['products'][$key];

        if (!isset($product['id'])) {
            return null;
        }

        return $product['id'];
    }

    /**
     * @return array
     */
    public function getPrices()
    {
        if (!$this->stripePrices) {
            $this->stripePrices = (new Parser())->parse(file_get_contents($this->stripePricesFile), true);
        }

        return $this->stripePrices;
    }

    /**
     * @param string $productKey
     * @return array|null
     */
    public function getProductPrices($productKey)
    {
        return $this->getPrices()['products'][$productKey]['prices'];
    }

    /**
     * @param string $productKey
     * @param string $priceId
     * @return false|string
     */
    public function searchProductPriceKey($productKey, $priceId)
    {
        $prices = $this->getProductPrices($productKey);

        return array_search($priceId, $prices);
    }

    /**
     * @param string $productKey
     * @return array
     */
    public function getProductPriceValues($productKey)
    {
        return $this->getPrices()['products'][$productKey]['price_values'];
    }

    /**
     * @param string $productKey
     * @param string|int $priceKey
     * @return string|null
     */
    public function getProductPriceId($productKey, $priceKey)
    {
        return $this->getProductPrices($productKey)[$priceKey];
    }

    /**
     * @param string $product
     * @param string $priceKey
     * @return bool
     */
    public function hasProductPrice($product, $priceKey)
    {
        if (!$this->hasProduct($product)) {
            return false;
        }

        return isset($this->getProductPrices($product)[$priceKey]);
    }
    /**
     * @param string $key - monthly or yearly
     *
     * @return string
     */
    public function getSubscriptionPriceId($key)
    {
        return $this->getPrices()['subscriptions'][$key];
    }

    /**
     * @param string $priceId
     * @return string|null
     */
    public function getProductKeyByPriceId($priceId)
    {
        foreach ($this->getPrices()['products'] as $productKey => $productData) {
            $priceKey = array_search($priceId, $productData['prices']);
            if ($priceKey !== false) {
                return $productKey;
            }
        }

        return null;
    }

    /**
     * @param array $lineItems
     * @return array
     */
    public function indexLineItemsByProductKeys($lineItems)
    {
        $products = [];

        foreach ($lineItems as $lineItem) {
            if (!isset($lineItem['price']['id'])) {
                continue;
            }

            $priceId = $lineItem['price']['id'];

            $products[$this->getProductKeyByPriceId($priceId)] = $lineItem;
        }

        return $products;
    }

    /**
     * @param string $productKey
     * @param string[] $possiblePriceIds
     * @param float $priceValue
     * @param string|null $description
     * @return array
     */
    public function createPriceLineItem($productKey, $possiblePriceIds, $priceValue = null, $description = null)
    {
        $priceId = null;

        foreach ($possiblePriceIds as $key) {
            if ($this->hasProductPrice($productKey, $key)) {
                $priceId = $this->getProductPriceId($productKey, $key);
            }
        }

        // Ad-hoc price if price id was not found.
        if (!$priceId) {
            if (!$priceValue) {
                throw new \InvalidArgumentException('Price value or existing price id is mandatory.');
            }

            $priceData = [
                'currency' => 'usd',
                'unit_amount' => (int)($priceValue * 100),
            ];

            if ($product = $this->getProductId($productKey)) {
                $priceData['product'] = $product;
            } elseif ($description) {
                $priceData['product_data'] = ['name' => $description];
            }

            $item = [
                'price_data' => $priceData,
                'quantity' => 1,
            ];
        } else {
            $item = [
                'price' => $priceId,
                'quantity' => 1,
            ];
        }

        return $item;
    }
}