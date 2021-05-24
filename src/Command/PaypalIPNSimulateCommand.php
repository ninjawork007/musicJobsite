<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PaypalIPNSimulateCommand extends Command
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        $this->container = $container;
    }

    protected function configure()
    {
        $this
                ->setName('vocalizr:paypal-ipn-simulate')
                ->setDescription('Checks eternal website for recorded ipn data')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container     = $this->container;
        $doctrine      = $container->get('doctrine');
        $em            = $doctrine->getManager();
        $payPalService = $container->get('service.paypal');

        $simulateUrl = $container->getParameter('paypal_ipn_simulator_url');
        $contents    = file($simulateUrl, FILE_SKIP_EMPTY_LINES);

        foreach ($contents as $row) {
            parse_str($row, $data);

            $payPalService->processIpn($data);
        }

        return 1;
    }
}