<?php

namespace Vocalizr\AppBundle\Model;

use LogicException;
use Vocalizr\AppBundle\Entity\Counter;
use Vocalizr\AppBundle\Entity\UserInfo;
use Vocalizr\AppBundle\Exception\CounterLimitReachedException;
use Vocalizr\AppBundle\Repository\CounterRepository;

/**
 * Class CounterModel
 * @package Vocalizr\AppBundle\Model
 *
 * @property CounterRepository $repository
 */
class CounterModel extends Model
{
    const LIFECYCLE_MONTHLY = 1;
    const LIFECYCLE_PERSISTENT = 2;

    const COUNTER_GROUP_MESSAGE = 'message';
    const COUNTER_GROUP_CONNECT = 'connect';

    private $counterLifecycles = [
        Counter::TYPE_MESSAGE                => self::LIFECYCLE_MONTHLY,
        Counter::TYPE_CONNECT                => self::LIFECYCLE_MONTHLY,
        Counter::TYPE_PERSISTENT_CONNECTIONS => self::LIFECYCLE_PERSISTENT,
    ];

    private $counterPriority = [
        self::COUNTER_GROUP_MESSAGE => [Counter::TYPE_MESSAGE],
        self::COUNTER_GROUP_CONNECT => [Counter::TYPE_CONNECT, Counter::TYPE_PERSISTENT_CONNECTIONS],
    ];

    /**
     * @param UserInfo $userInfo
     * @param string $group
     * @param int|null $timestamp
     *
     * @return Counter[] - counters for passed group in descending priority order
     */
    public function getCounters(UserInfo $userInfo, $group, $timestamp = null)
    {
        $counters = [];

        if (!$timestamp) {
            $timestamp = time();
        }

        foreach ($this->counterPriority[$group] as $counterType) {
            $criteria = [
                'user_info' => $userInfo,
                'type'      => $counterType,
            ];
            $lifecycle = $this->counterLifecycles[$counterType];

            if ($lifecycle === self::LIFECYCLE_MONTHLY) {
                $criteria['date'] = date('Y-m', $timestamp);
            }

            $counter = $this->repository->findOneBy($criteria, ['created_at' => 'DESC']);

            if ($counter) {
                $counters[$counterType] = $counter;
            }
        }

        return $counters;
    }

    /**
     * @param UserInfo $userInfo
     * @param string $group
     * @param int[] $limitList
     *
     * @throws CounterLimitReachedException
     */
    public function incrementCounter(UserInfo $userInfo, $group, $limitList)
    {
        $counter = $this->chooseCounter($userInfo, $group, $limitList);

        $counter->setCount($counter->getCount() + 1);

        $this->updateObject($counter);
    }

    /**
     * Returns first counter which does not exceed specified or stored limit, throw exception instead.
     *
     * @param UserInfo $userInfo
     * @param string $group
     * @param int[] $limitList
     *
     * @return Counter
     *
     * @throws CounterLimitReachedException
     */
    public function chooseCounter(UserInfo $userInfo, $group, $limitList)
    {
        $counters = array_values($this->getCounters($userInfo, $group));

        $lastPeriodicCounter = null;
        $lastLimit   = 0;

        foreach ($counters as $id => $counter) {
            if (isset($limitList[$id])) {
                $limit = $limitList[$id];
            } elseif (!is_null($counter->getLimit())) {
                $limit = $counter->getLimit();
            } else {
                $limit = null;
            }

            if (is_null($limit) && $counter !== end($counters)) {
                throw new LogicException('Unlimited counter can only be the last counter in group.');
            }

            if ($limit - $counter->getCount() > 0) {
                return $counter;
            }

            if ($counter->getDate()) {
                $lastLimit           = $limit;
                $lastPeriodicCounter = $counter;
            }
        }

        if ($lastPeriodicCounter) {
            throw new CounterLimitReachedException($lastLimit, $lastPeriodicCounter);
        } else {
            $type = $this->counterPriority[$group][0];
            $limit = isset($limitList[0]) ? $limitList[0] : null;
            $counter = new Counter();
            $counter
                ->setType($type)
                ->setUserInfo($userInfo)
                ->setLimit($limit)
            ;

            if ($this->counterLifecycles[$type] === self::LIFECYCLE_MONTHLY) {
                $counter->setDate(date('Y-m'));
            }

            return $counter;
        }
    }

    /**
     * @return string
     */
    protected function getEntityName()
    {
        return 'VocalizrAppBundle:Counter';
    }
}