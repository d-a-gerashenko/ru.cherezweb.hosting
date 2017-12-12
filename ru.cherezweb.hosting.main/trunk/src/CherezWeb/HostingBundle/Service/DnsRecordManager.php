<?php

namespace CherezWeb\HostingBundle\Service;

use CherezWeb\HostingBundle\Entity\DomainBase;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class DnsRecordManager
{

    const NS1 = 'ns1.cherezweb.ru.';
    const NS2 = 'ns2.cherezweb.ru.';

    /**
     * @var Container;
     */
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function initBaseDomainRecords(DomainBase $domainBase)
    {
        $dnsRecordRepo = $this->container->get('doctrine')
            ->getRepository('CherezWebHostingBundle:DnsRecord');
        /* @var $dnsRecordRepo \CherezWeb\HostingBundle\Repository\DnsRecordRepository */
        
        $emailForDns = str_replace('@', '.', $domainBase->getUser()->getEmail()) . '.';

        $dnsRecordRepo->createOrUpdateBaseSoaRecord(
            $domainBase->getName(), self::NS1, $emailForDns, 14400, 900, 1209600, 21600
        );

        $dnsRecordRepo->createOrUpdateBaseNsRecords($domainBase->getName(), array(self::NS1, self::NS2));
    }
}
