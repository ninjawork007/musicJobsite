<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

define('STRIPE_API_KEY', 'sk_live_3MCMC1EJfOjB7EBDRua3b85P');

class FixSubsCommand extends Command
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        $this->container = $container;
    }

    protected function configure()
    {
        // How often do we run this script

        $this
                ->setName('vocalizr:fix-subs')
                ->setDescription('Fix subs')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container  = $this->container;
        $doctrine   = $container->get('doctrine');
        $em         = $doctrine->getManager();
        $dispatcher = $container->get('hip_mandrill.dispatcher');

        $this->processResults();
    }

    public function processResults($subs = null)
    {
        $limit = 100;

        if (!$subs) {
            $s = new Stripe();
            $s->url .= 'subscriptions?limit=' . $limit;
            $subs = $s->call();
        }

        $i = 0;

        foreach ($subs['data'] as $sub) {
            if (!is_array($sub)) {
                continue;
            }

            //print_r($sub);

            // If they are cancelled, ignore
            if ($sub['canceled_at']) {
                continue;
            }

            if ($sub['status'] != 'trialing') {
                continue;
            }

            $plans = [
                'PRO YEARLY 3 MONTH TRIAL',
                'PRO TRIAL 3 MONTH',
            ];

            // Temp
            /*
            if ($sub['id'] != "sub_8NL1Sj02ueO3mO") {
                echo "SKIP: ".$sub['id']."\n";
                continue;
            }
             *
             */

            // No need to change them if they are on correct plan
            if (in_array($sub['plan']['id'], $plans)) {
                continue;
            }

            $s = new Stripe();
            $s->url .= 'subscriptions/' . $sub['id'];

            $newPlan = 'PRO YEARLY 3 MONTH TRIAL';
            if ($sub['plan']['interval'] == 'month') {
                $newPlan = 'PRO TRIAL 3 MONTH';
            }
            $newEndTime = strtotime('+90 days', $sub['created']);

            //echo "\nNew date: ".date('Y-m-d', $newEndTime)."\n";

            $s->fields['plan']      = $newPlan;
            $s->fields['trial_end'] = $newEndTime;

            $result = $s->call();

            if (!isset($result['id'])) {
                echo $sub['id'] . ": FAILED\n\n";
                print_r($result);
                echo "\n";
                continue;
            }

            echo $sub['id'] . ': Old date: ' . date('Y-m-d', $sub['trial_end']) . ', New date: ' . date('Y-m-d', $newEndTime) . ' - New Plan: ' . $newPlan . "\n";
        }

        if ($subs['has_more']) {
            $s = new Stripe();
            $s->url .= 'subscriptions?limit=' . $limit . '&starting_after=' . $sub['id'];
            $subs = $s->call();

            $this->processResults($subs);
        }
    }
}

class Stripe
{
    public $headers;

    public $url = 'https://api.stripe.com/v1/';

    public $fields = [];

    public function __construct()
    {
        $this->headers = ['Authorization: Bearer ' . STRIPE_API_KEY]; // STRIPE_API_KEY = your stripe api key
    }

    public function call()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_URL, $this->url);
        if ($this->fields) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->fields));
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $output = curl_exec($ch);
        curl_close($ch);

        return json_decode($output, true); // return php array with api response
    }
}