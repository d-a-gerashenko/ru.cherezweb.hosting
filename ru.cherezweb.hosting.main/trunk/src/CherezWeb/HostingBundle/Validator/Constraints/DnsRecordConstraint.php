<?php

namespace CherezWeb\HostingBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class DnsRecordConstraint extends Constraint
{

    public $message = 'Ошибка в DNS записи: "%string%".';

    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
