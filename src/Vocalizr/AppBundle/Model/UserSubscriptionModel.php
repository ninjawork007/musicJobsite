<?php

namespace Vocalizr\AppBundle\Model;

use DateTime;
use Doctrine\ORM\OptimisticLockException;
use Error;
use Exception;
use Stripe\Stripe;
use Stripe\Subscription;
use Vocalizr\AppBundle\Entity\StripeCharge;
use Vocalizr\AppBundle\Entity\SubscriptionPlan;
use Vocalizr\AppBundle\Entity\UserInfo;
use Vocalizr\AppBundle\Entity\UserSubscription;
use Vocalizr\AppBundle\Exception\UnsubscribeException;
use Vocalizr\AppBundle\Repository\SubscriptionPlanRepository;
use Vocalizr\AppBundle\Service\MembershipSourceHelper;

/**
 * Class UserSubscriptionModel
 * @package Vocalizr\AppBundle\Model
 */
class UserSubscriptionModel extends Model
{
    /**
     * @param UserInfo $user
     * @param string $id
     * @param bool $isStripe
     * @param StripeCharge|null $charge
     * @throws Exception
     */
    public function addSubscription(UserInfo $user, $id, $isStripe = true, $charge = null)
    {
        if ($user->isSubscribed()) {
            $charge->setUserSubscription($user->getUserSubscriptions()->last());
            $this->updateObject($charge);
            throw new Exception('User is already subscribed!');
        }

        /** @var SubscriptionPlan $proPlan */
        $proPlan = $this->em->getRepository('VocalizrAppBundle:SubscriptionPlan')->getProPlan();

        $subscription = new UserSubscription();
        $subscription
            ->setUserInfo($user)
            ->setSubscriptionPlan($proPlan)
            ->setIsActive(true)
            ->setDateCommenced(new DateTime())
            ->setLastPaymentDate(new DateTime())
            ->setNextPaymentDate(new DateTime('+1 month midnight'))
        ;

        $user
            ->setSubscriptionPlan($proPlan)
            ->addUserSubscription($subscription)
        ;

        if ($isStripe) {
            $subscription->setStripeSubscrId($id);
        } else {
            $subscription->setPaypalSubscrId($id);
        }

        $this->updateObject($subscription);
    }

    /**
     * @param UserSubscription $subscription
     * @param array $subscriptionObject
     * @throws OptimisticLockException
     */
    public function updateStripeSubscription(UserSubscription $subscription, $subscriptionObject)
    {
        $isActive = $subscriptionObject['status'] === 'active';
        $subscription
            ->setIsActive($isActive)
            ->setLastPaymentDate(new DateTime())
            ->setNextPaymentDate((new DateTime())->setTimestamp($subscriptionObject['current_period_end']))
        ;
        
        $this->setUserPlanBasedOnSubscriptions($subscription->getUserInfo());

        $this->em->flush();
    }

    /**
     * @param UserInfo $user
     */
    public function setUserPlanBasedOnSubscriptions(UserInfo $user)
    {
        $isOnPro = ($user->getSubscriptionPlan() && $user->getSubscriptionPlan()->getStaticKey() === SubscriptionPlan::PLAN_PRO);
        $now = new DateTime();

        $isNewStatusPro = false;

        foreach ($user->getUserSubscriptions() as $subscription) {
            if (
                $subscription->getIsActive() && $now < $subscription->getNextPaymentDate()
                && (!$subscription->getDateEnded() || $now < $subscription->getDateEnded())
            ) {
                $isNewStatusPro = true;
                break;
            }
        }

        if ($isNewStatusPro) {
            if ($isOnPro) {
                // Do nothing as user is already on PRO.
                return;
            }

            $user->setSubscriptionPlan($this->planRepo()->getProPlan());
        } else {
            $user->setSubscriptionPlan(null);
        }
    }

    /**
     * @param UserInfo $userInfo
     * @param bool $throwException
     * @param bool $atPeriodEnd
     * @throws UnsubscribeException
     */
    public function unsubscribe(UserInfo $userInfo, $throwException = false, $atPeriodEnd = false)
    {
        $removeSubscriptionPlan = true;

        /** @var UserSubscription $userSubscription */
        foreach ($userInfo->getUserSubscriptions() as $userSubscription) {
            if ((!$userSubscription->getIsActive() || $userSubscription->getDateEnded()) && $atPeriodEnd) {
                // Ignore all already cancelled or planned subscriptions if atPeriodEnd passed.
                continue;
            }

            try {
                $this->tryRemoveSubscription($userSubscription, $atPeriodEnd);
            } catch (Error $exception) {
                $this->logUnsubscribeError($userInfo, $exception, $throwException);
            } catch (Exception $exception) {
                $this->logUnsubscribeError($userInfo, $exception, $throwException);
            }

            if (!$atPeriodEnd || (!$userSubscription->getLastPaymentDate() && !$userSubscription->getNextPaymentDate())) {
                $dateEnded = new DateTime();
            } else {
                if ($userSubscription->getNextPaymentDate()) {
                    $dateEnded = $userSubscription->getNextPaymentDate();
                } else {
                    $nextPaymentDate = clone $userSubscription->getLastPaymentDate();
                    $dateEnded = $nextPaymentDate->modify('+ 1 month');
                }
            }

            $active = !($dateEnded <= new DateTime());

            $userSubscription
                ->setIsActive($active)
                ->setDateEnded($dateEnded)
                ->setCancelDate(new DateTime())
            ;

            if ($active) {
                $removeSubscriptionPlan = false;
            }

            $this->em->persist($userSubscription);
        }

        if ($removeSubscriptionPlan) {
            $userInfo->setSubscriptionPlan(null);
        }

        $this->em->persist($userInfo);

        /** @noinspection PhpUnhandledExceptionInspection */
        $this->em->flush();
    }

    /**
     * @param UserSubscription $userSubscription
     * @param bool $atPeriodEnd
     * @return bool
     */
    public function tryRemoveSubscription(UserSubscription $userSubscription, $atPeriodEnd)
    {
        // Already unsubscribed.
        if (!$userSubscription->getIsActive()) {
            return true;
        }

        if ($userSubscription->getPaypalSubscrId()) {
            if ($userSubscription->getDateEnded()) {
                return true;
            }
            return $this->container->get('vocalizr_app.paypal_api')->cancelSubscription($userSubscription);

        } elseif ($userSubscription->getStripeSubscrId()) {
            Stripe::setApiKey($this->container->getParameter('stripe_api_key'));

            /** @var Subscription $subscription */
            $subscription = Subscription::retrieve($userSubscription->getStripeSubscrId());

            if ($subscription->status === Subscription::STATUS_CANCELED) {
                // Already cancelled
                return true;
            }

            if ($atPeriodEnd) {
                // Cancellation already planned.
                if ($subscription->cancel_at_period_end === true) {
                    return true;
                }

                $subscription->cancel_at_period_end = true;
                $subscription->save();
            } else {
                $subscription->cancel();
            }

            return true;
        } else {
            error_log('Subscription ' . $userSubscription->getId() . ' doesn\'t have paypal id nor stripe id.');
            return false;
        }
    }

    /**
     * @param UserInfo $userInfo
     * @param Exception $exception
     * @param bool $throwException
     * @throws UnsubscribeException
     */
    private function logUnsubscribeError(UserInfo $userInfo, $exception, $throwException)
    {
        error_log('Coudn\'t unsubscribe user ' . $userInfo->getEmail() . '.');
        $this->container->get('session')->getFlashBag()->add(
            'error',
            'Something went wrong during user subscription cancellation.'
        );
        if ($throwException) {
            throw new UnsubscribeException($exception->getMessage(), 0, $exception);
        }
    }

    /**
     * @return SubscriptionPlanRepository
     */
    public function planRepo()
    {
        return $this->em->getRepository('VocalizrAppBundle:SubscriptionPlan');
    }

    protected function getEntityName()
    {
        return 'VocalizrAppBundle:UserSubscription';
    }
}