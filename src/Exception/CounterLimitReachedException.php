<?php

namespace App\Exception;

use Exception;
use App\Entity\Counter;

/**
 * Class CounterLimitReachedException
 * @package App\Exception
 */
class CounterLimitReachedException extends Exception implements VocalizrExceptionInterface
{
    /**
     * @var int
     */
    private $limit;

    /**
     * @var Counter
     */
    private $counter;

    /**
     * CounterLimitReachedException constructor.
     * @param int $limit
     * @param Counter $counter
     */
    public function __construct($limit, $counter)
    {
        $this->limit   = $limit;
        $this->counter = $counter;

        parent::__construct(sprintf(
            'Counter limit of %d has been reached for counter %s',
            $limit,
            $counter->getType()
        ));
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @return Counter
     */
    public function getCounter()
    {
        return $this->counter;
    }
}