<?php

namespace Vocalizr\AppBundle\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Vocalizr\AppBundle\Entity\Revenue\StripeInvoice;
use Vocalizr\AppBundle\Entity\Revenue\StripeProductInvoice;

class UpdatingBalanceTransactionListCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('vocalizr:updating-balance-transaction-list')
            ->setDescription('Updating the balance transaction List')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $stripeManager = $this->getContainer()->get('vocalizr_app.stripe_manager');
        $lastInvoices = '';
        $invoicesList = $stripeManager->call('/invoices?limit=100', [], [], 'GET');
        $result = [];
        if (!isset($invoicesList['error'])) {
            $lastInvoices = end($invoicesList['data'])['id'];
            $result = $this->saveData($invoicesList, $result);
        }

        while(true) {
            if (!$lastInvoices) {
                break;
            }
            $invoicesList = $stripeManager->call('/invoices?limit=100&starting_after=' . end($invoicesList['data'])['id'], [], [], 'GET');
            if (isset($invoicesList['error'])) {
                break;
            }
            $result = $this->saveData($invoicesList, $result);
            $lastInvoices = end($invoicesList['data'])['id'];
        }
        foreach ($result as $key=>$res) {
            print_r("prod_id: " . $key . " | name: ". $res['name'] . " | price_id: ". $res['price_id'] . " | count: " . $res['count'] . "\r\n");
        }
        echo "done";

    }

    private function saveData($invoicesData, $result)
    {
        /**
         * @var EntityManager $em
         */
        $em = $this->getContainer()->get('doctrine')->getEntityManager();
        $stripeManager = $this->getContainer()->get('vocalizr_app.stripe_manager');

        foreach ($invoicesData['data'] as $data) {

            if ($data['status'] != 'paid') {
                continue;
            }

            $create = new \DateTime();
            $create->setTimestamp($data['created']);

            $invoice = $em->getRepository('VocalizrAppBundle:Revenue\StripeInvoice')->findOneBy(['invoice_id' => $data['id']]);
            if ($invoice) {
                continue;
            }
            $productsName = $this->getProductsName();
            $stripeInvoice = new StripeInvoice();
            $stripeInvoice->setAmount($data['amount_paid']);
            $stripeInvoice->setChargeId($data['charge']);
            $stripeInvoice->setDateCreateInvoice($create);
            $stripeInvoice->setInvoiceId($data['id']);
            $stripeInvoice->setCreatedAt(new \DateTime());
            if (isset($data['charge'])) {
                $charge = $stripeManager->call('/charges/' . $data['charge'], [], [], 'GET');
                $stripeInvoice->setIsRefund($charge['refunded']);
                if (isset($charge['balance_transaction'])) {
                    $balanceTransaction = $stripeManager->call('/balance_transactions/' . $charge['balance_transaction'], [], [], 'GET');
                    $stripeInvoice->setFee(isset($balanceTransaction['fee']) ? $balanceTransaction['fee'] : 0);
                }
            } else {
                $stripeInvoice->setIsRefund(false);
            }

            foreach ($data['lines']['data'] as $product) {

                $stripeProductInvoice = new StripeProductInvoice();
                if (isset($productsName[$product['price']['product']])) {
                    $stripeProductInvoice->setName($productsName[$product['price']['product']]);
                    $result = $this->setResults($result, $product['price']['product'], $product['price']['id'], $productsName[$product['price']['product']]);
                } elseif ($product['type'] == 'subscription') {
                    $stripeProductInvoice->setName('subscriptions');
                    if ($product['price']['product'] == '') {
                        $result = $this->setResults($result, $data['charge'], $data['amount_paid'], 'subscriptions');
                    } else {
                        $result = $this->setResults($result, $product['price']['product'], $product['price']['id'], 'subscriptions');
                    }
                } elseif ($product['description'] == 'Transaction Fee') {
                    $stripeProductInvoice->setName('stripe_fee');
                    $result = $this->setResults($result, $product['price']['product'], $product['price']['id'], 'stripe_fee');
                } else {
                    $stripeProductInvoice->setName('undefined');
                    $result = $this->setResults($result, $product['price']['product'], $product['price']['id'], 'undefined');
                }
                $stripeProductInvoice->setIsRefund(false);
                if (isset($charge['amount_refunded'])) {
                    if ($charge['amount_refunded'] == $product['amount']) {
                        $stripeProductInvoice->setIsRefund(true);
                    }
                }

                $stripeProductInvoice->setAmount($product['amount']);
                $stripeProductInvoice->setProductId($product['price']['product']);
                $stripeProductInvoice->setCreatedAt(new \DateTime());
                $stripeInvoice->addProduct($stripeProductInvoice);

                $em->persist($stripeProductInvoice);
                $em->persist($stripeInvoice);
                $em->flush();

            }
            $em->flush();
        }
        $em->flush();
        return $result;

    }

    private function setResults($result, $prodId, $priceId, $name)
    {
        $result[$prodId]['name'] = $name;
        $result[$prodId]['price_id'] = $priceId;
        if (isset($result[$prodId]['count'])) {
            $result[$prodId]['count'] += 1;
        } else {
            $result[$prodId]['count'] = 1;
        }

        return $result;
    }

    /**
     * @return array
     */
    private function getProductsName()
    {
        $stripeConfigProvider = $this->getContainer()->get('vocalizr_app.stripe_configuration_provider');

        $products = $stripeConfigProvider->getPrices()['products'];
        $productsName = [];
        foreach ($products as $key => $product) {
            if (isset($product['id'])) {
                $productsName[$product['id']] = $key;
            }
        }

        return $productsName;
    }

}