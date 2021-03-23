<?php

namespace Vocalizr\AppBundle\EventListener\StripeWebhook;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\OptimisticLockException;
use Vocalizr\AppBundle\Entity\UserStripeIdentity;
use Vocalizr\AppBundle\Event\StripeWebhookEvent;
use Vocalizr\AppBundle\Exception\WebhookProcessingException;

/**
 * Class IdentityListener
 * @package Vocalizr\AppBundle\EventListener\StripeWebhook
 */
class IdentityListener
{
    /**
     * @var EntityManager
     */
    private $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    /**
     * @param StripeWebhookEvent $event
     * @throws OptimisticLockException
     */
    public function onWebhook(StripeWebhookEvent $event)
    {
        if (!$event->matchApiEvent([
            StripeWebhookEvent::EVENT_IDENTITY_REPORT_UNVERIFIED,
            StripeWebhookEvent::EVENT_IDENTITY_REPORT_VERIFIED,
            StripeWebhookEvent::EVENT_IDENTITY_INTENT_REQUIRES_ACTION,
        ])) {
            return;
        }

        $message = $this->processEvent($event);

        if ($message) {
            $event->stopPropagation();
            $event->setResponseMessage($message);
        }
    }

    /**
     * @param StripeWebhookEvent $event
     * @return string|null
     * @throws OptimisticLockException
     */
    private function processEvent(StripeWebhookEvent $event)
    {
        $object        = $event->getPayloadObject();
        $identityRepo  = $this->em->getRepository(UserStripeIdentity::class);

        switch ($event->getApiEvent()) {
            case StripeWebhookEvent::EVENT_IDENTITY_REPORT_UNVERIFIED:
                /** @var UserStripeIdentity $identity */
                $identity = $identityRepo->findOneBy(['verificationIntentId' => $object['verification_intent']]);
                if (!$identity) {
                    return 'ok identity not found';
                }

                $identity
                    ->setVerified(false)
                    ->setData($object['details'])
                    ->setVerificationReportId($object['id'])
                ;
                $this->em->flush();
                return 'ok identity unverified';
            case StripeWebhookEvent::EVENT_IDENTITY_REPORT_VERIFIED:
                /** @var UserStripeIdentity $identity */
                $identity = $identityRepo->findOneBy([
                    'verificationIntentId' => $object['verification_intent'],
                ]);
                if (!$identity) {
                    throw new WebhookProcessingException('ok identity not found');
                }
                $identity
                    ->setVerified(true)
                    ->setVerificationReportId($object['id'])
                ;
                $identity->getUser()
                    ->setLastName($object['person_details']['last_name'])
                    ->setFirstName($object['person_details']['first_name'])
                ;
                $this->em->flush();
                return 'ok identity verified';
            case StripeWebhookEvent::EVENT_IDENTITY_INTENT_REQUIRES_ACTION:
                $identity = $identityRepo->findOneBy(['verificationIntentId' => $object['id']]);
                if (!$identity) {
                    return 'ok identity not found';
                }
                if ($object['last_verification_error'] && $object['last_verification_error']['type'] == 'consent_declined') {
                    $this->em->remove($identity);
                    $this->em->flush();
                    return 'ok deleted';
                }
                return 'ok no action';
        }

        return null;
    }
}