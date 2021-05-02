<?php

namespace App\Helper;

class GeneralHelper
{
    public function __construct($doctrine, $container, $templating)
    {
        $this->em         = $doctrine->getEntityManager();
        $this->container  = $container;
        $this->templating = $templating;
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
        return $this->container->get('kernel')->getRootdir() . '/../tmp';
    }
}

