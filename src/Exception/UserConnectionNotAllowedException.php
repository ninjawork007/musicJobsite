<?php

namespace App\Exception;

use Exception;
use Traversable;
use App\Model\UserConnectModel;

/**
 * Class UserConnectionNotAllowedException
 * @package App\Exception
 */
class UserConnectionNotAllowedException extends \Exception implements VocalizrExceptionInterface, \IteratorAggregate
{
    private static $violationMessages = [
        UserConnectModel::CONSTRAINT_NOT_SUBSCRIBED  => 'You\'re not subscribed',
        UserConnectModel::CONSTRAINT_LIMIT_REACHED   => 'You have reached your connection limit',
        UserConnectModel::CONSTRAINT_SELF_CONNECTION => 'Why you want to connect with yourself? :)',
        UserConnectModel::CONSTRAINT_BLOCKED         => 'Your request is being blocked by this user',
        UserConnectModel::CONSTRAINT_REQUEST_SENT    => 'You have already sent a request',
        UserConnectModel::CONSTRAINT_CONNECTED       => 'You are already connected',
        UserConnectModel::CONSTRAINT_HAVE_REQUEST    => 'This user has already sent you a request',

    ];

    private $violations;

    /**
     * UserConnectionNotAllowedException constructor.
     * @param string[] $violations
     */
    public function __construct($violations)
    {
        $this->violations = $violations;
        parent::__construct();
    }

    /**
     * @return string[]
     */
    public function getViolations()
    {
        return $this->violations;
    }

    /**
     * @param string $violation
     * @return bool
     */
    public function hasViolation($violation)
    {
        return in_array($violation, $this->violations);
    }

    /**
     * @return string[]
     */
    public function getViolationMessages()
    {
        $messages = [];
        foreach ($this->violations as $violation) {
            if (isset(self::$violationMessages[$violation])) {
                $messages[$violation] = self::$violationMessages[$violation];
            } else {
                $messages[$violation] = $violation;
            }
        }

        return $messages;
    }

    /**
     * @inheritDoc
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->getViolationMessages());
    }
}
