<?php


namespace Vocalizr\AppBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class CustomRegexValidator extends ConstraintValidator
{

    /**
     * For preventing duplicate validations
     *
     * @var array
     */
    private $violations;

    /**
     * {@inheritDoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_scalar($value) && !(is_object($value) && method_exists($value, '__toString'))) {
            throw new UnexpectedTypeException($value, 'string');
        }

        $value = (string) $value;

        $matches = [];
        if ($constraint->match xor preg_match_all($constraint->pattern, $value, $matches)) {
            if ($this->violations && in_array($constraint, $this->violations)) {
                return;
            }

            $match = null;
            if (isset($matches[0])) {
                $match = implode(', ', $matches[0]);
            }
            $this->violations[] = $constraint;

            $this->context->addViolation($constraint->message, ['{{ values }}' => $match]);
        }
    }
}