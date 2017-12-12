<?php

namespace CherezWeb\HostingBundle\Validator\Constraints;

use CherezWeb\HostingBundle\Entity\DnsRecord;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class DnsRecordConstraintValidator extends ConstraintValidator
{

    /**
     * @param DnsRecord $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if ($value !== NULL && in_array($value->getType(), array(DnsRecord::TYPE_NS, DnsRecord::TYPE_CNAME)) && $value->getHost() === '@') {
            $this->context->addViolation(
                $constraint->message, array('%string%' => sprintf('запись с типом %s не может быть корневой', $value->getType()))
            );
        }
    }
}
