<?php

namespace CherezWeb\HostingBundle\Form\DataTransformer;

use CherezWeb\HostingBundle\Entity\DnsRecord;
use Symfony\Component\Form\DataTransformerInterface;

class DnsRecordHostTransformer implements DataTransformerInterface
{

    private $domainBaseName;

    public function __construct($domainBaseName)
    {
        $this->domainBaseName = $domainBaseName;
    }

    public function transform($dataToFrom)
    {
        if ($dataToFrom == '') {
            return $dataToFrom;
        }
        return DnsRecord::hostToDomainStyle($this->domainBaseName, $dataToFrom);
    }

    public function reverseTransform($dataFromForm)
    {
        if ($dataFromForm == '') {
            return $dataFromForm;
        }
        return DnsRecord::hostFromDomainStyle($this->domainBaseName, $dataFromForm);
    }
}
