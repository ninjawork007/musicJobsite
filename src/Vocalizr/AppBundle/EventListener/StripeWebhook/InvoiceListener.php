<?php

namespace Vocalizr\AppBundle\EventListener\StripeWebhook;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Templating\EngineInterface;
use Vocalizr\AppBundle\Event\StripeWebhookEvent;
use Vocalizr\AppBundle\Exception\WebhookProcessingException;
use Vocalizr\AppBundle\Service\MandrillService;

/**
 * Class InvoiceListener
 * @package Vocalizr\AppBundle\EventListener\StripeWebhook
 */
class InvoiceListener
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var MandrillService
     */
    private $mandrill;

    /**
     * @var EngineInterface
     */
    private $twig;

    /**
     * InvoiceListener constructor.
     * @param EntityManager $em
     * @param EngineInterface $twig
     * @param MandrillService $mandrill
     */
    public function __construct(EntityManager $em, EngineInterface $twig, MandrillService $mandrill)
    {
        $this->em       = $em;
        $this->twig     = $twig;
        $this->mandrill = $mandrill;
    }

    /**
     * @param StripeWebhookEvent $event
     * @throws WebhookProcessingException
     */
    public function onWebhook(StripeWebhookEvent $event)
    {
        if (!$event->matchApiEvent(StripeWebhookEvent::EVENT_INVOICE_PAYMENT_FAILED)) {
            return;
        }

        $event->addResponseMessage($this->paymentFailed($event));
        $event->setFulfilled(true);
    }

    /**
     * @param StripeWebhookEvent $event
     * @return string
     * @throws WebhookProcessingException
     */
    private function paymentFailed(StripeWebhookEvent $event)
    {
        $em      = $this->em;
        $object  = $event->getPayloadObject();
        $user    = $event->getUser();

        if (!$user) {
            throw new WebhookProcessingException('User not found but is required for failed payment handling');
        }

        // get active plan for the User
        $userSubscription = $em->getRepository('VocalizrAppBundle:UserSubscription')->findOneBy([
            'user_info' => $user,
            'is_active' => 1,
        ]);

        if (!$userSubscription) {
            throw new WebhookProcessingException('Subscription not found for user with failed payment.');
        }

//        $body = $this->twig->render('VocalizrAppBundle:Mail:stripeFailedCharge.html.twig', [
//            'user'                 => $user,
//            'next_payment_attempt' => isset($object['next_payment_attempt']) ? $object['next_payment_attempt'] : 0,
//        ]);

//        $this->mandrill->sendMessage($user->getEmail(), 'PRO Membership charge failed', 'default', [
//            'body' => $body,
//        ]);

        return 'ok notified user';
    }
}