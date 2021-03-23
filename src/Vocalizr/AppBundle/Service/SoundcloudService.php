<?php

namespace Vocalizr\AppBundle\Service;

class SoundcloudService
{
    private $_client;

    public function __construct($doctrine, $container, $templating)
    {
        $this->em         = $doctrine->getEntityManager();
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