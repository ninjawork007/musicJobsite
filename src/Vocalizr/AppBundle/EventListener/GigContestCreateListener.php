<?php

namespace Vocalizr\AppBundle\EventListener;

use Hip\MandrillBundle\Dispatcher;
use Hip\MandrillBundle\Message;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Vocalizr\AppBundle\Event\JustCreatedEvent;

/**
 * Class JustCreatedListener
 *
 * @package Vocalizr\AppBundle\EventListener
 */
class GigContestCreateListener
{
    /** @var Router */
    private $router;

    private $mandrillDispatcher;

    /**
     * JustCreatedListener constructor.
     *
     * @param Dispatcher $mandrillDispatcher
     * @param Router     $router
     */
    public function __construct($mandrillDispatcher, $router)
    {
        $this->mandrillDispatcher = $mandrillDispatcher;
        $this->router             = $router;
    }

    /**
     * Listen event contest_or_gig.just_created
     *
     * @param JustCreatedEvent $event
     */
    public function onCreateAction(JustCreatedEvent $event)
    {
        $message = new Message();

        $link = $this->router->generate('project_view', [
            'uuid' => $event->getCreatedEntity()->getUuid(),
        ], Router::ABSOLUTE_URL);

        if ($event->getCreatedEntity()->getLookingFor() === 'vocalist') {
            $lookingFor = 'vocal';
        } else {
            $lookingFor = 'producer';
        }

        if ($event->isGig()) {
            $projectType = 'Gig';
        } else {
            $projectType = 'Contest';
        }

        $mcSubject = "I'm searching for an awesome {$lookingFor}! Checkout my {$projectType} on Vocalizr!";

        $message
            ->addTo($event->getUser()->getEmail())
            ->setSubject("Your {$projectType} is live! Here's what's next")
            ->addMergeVars($event->getUser()->getEmail(), [
                'ARCHIVE_LINK_SHORT' => $link,
                'MC_SUBJECT'         => $mcSubject,
                'FORWARD'            => $this->getShareLink([
                    'subject' => "Checkout my {$projectType} on Vocalizr",
                    'body'    => "Hey, I thought you'd like to have a look at my {$projectType} on Vocalizr!\n\n" .
                        $link,
                ]),

            ])
        ;

        if ($event->isGig()) {
            if ($lookingFor == 'producer') {
                $result = $this->mandrillDispatcher->send($message, 'What happens next? (Gigs) for Producers');
            } else {
                $result = $this->mandrillDispatcher->send($message, 'What happens next? (Gigs)');
            }
        } else {
            if ($lookingFor == 'producer') {
                $result = $this->mandrillDispatcher->send($message, 'What happens next? (Contests) for Producers');
            } else {
                $result = $this->mandrillDispatcher->send($message, 'What happens next? (Contests)');
            }
        }
    }

    /**
     * @param string[] $parameters
     *
     * @return string
     */
    private function getShareLink($parameters)
    {
        $link = 'mailto:?' . http_build_query($parameters, null, '&', PHP_QUERY_RFC3986);
        return $link;
    }
}