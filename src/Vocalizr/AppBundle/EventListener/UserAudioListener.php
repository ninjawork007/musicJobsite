<?php

namespace Vocalizr\AppBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Vocalizr\AppBundle\Entity\UserAudio;

/**
 * Class UserAudioListener
 *
 * @package Vocalizr\AppBundle\EventListener
 *
 * @deprecated - not used in project
 */
class UserAudioListener
{
    public function __construct($container)
    {
        $this->container = $container;
    }

    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $em     = $args->getEntityManager();

        if ($entity instanceof UserAudio) {
            // If file uploaded, generate waveform
            if ($entity->getPath()) {
                $this->container->get('service.helper')->
                        exec('php ' . $this->container->get('kernel')->getRootDir() . '/console vocalizr:generate-waveform ' . $entity->getId());
            }
        }
    }
}