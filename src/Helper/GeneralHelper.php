<?php

namespace App\Helper;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class GeneralHelper
{
    public function __construct($doctrine, $container, $templating, ParameterBagInterface $parameterBag)
    {
        $this->em         = $doctrine->getEntityManager();
        $this->container  = $container;
        $this->templating = $templating;
        $this->parameterBag = $parameterBag;
    }

    /**
     * Get real ip address of client
     *
     * @return string
     */
    public function getRealIpAddress()
    {
        $ip = $_SERVER['REMOTE_ADDR'];
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {   //check ip from share internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {   //to check ip is pass from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }

        return $ip;
    }

    /**
     * Get temp directory for uploaded files
     *
     * @return string
     */
    public function getUploadTmpDir()
    {
        return $this->parameterBag->get('kernel.project_dir') . '/tmp';
    }
}

