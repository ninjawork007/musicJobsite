<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Environment;

class SoundcloudService
{
    private $_client;
    public $em;
    public $container;
    public $templating;

    public function __construct(EntityManagerInterface $doctrine, ContainerInterface $container, Environment $templating)
    {
        $this->em         = $doctrine;
        $this->container  = $container;
        $this->templating = $templating;

        // create client object with app credentials
        $clientId      = $this->container->getParameter('soundcloud_client_id');
        $clientSecret  = $this->container->getParameter('soundcloud_client_secret');
        $this->_client = new \Services_Soundcloud($clientId, $clientSecret);
    }

    public function getClient()
    {
        return $this->_client;
    }
}