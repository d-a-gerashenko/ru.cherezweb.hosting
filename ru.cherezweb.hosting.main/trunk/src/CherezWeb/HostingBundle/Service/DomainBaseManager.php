<?php

namespace CherezWeb\HostingBundle\Service;

use CherezWeb\HostingBundle\Entity\DomainBase;
use CherezWeb\HostingBundle\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

class DomainBaseManager
{

    /**
     * @var Container;
     */
    private $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Инициализация базового домена стала более сложной задачей с появление
     * DNS записей. Теперь при инициализации домена требуется не только создвать
     * базовый домен, но и обновить базовые DNS записи.
     * @param DomainBase $domainBase
     */
    public function findOrInitActive(User $user, $domainName)
    {
        $dnsRecordManager = $this->container->get('cherez_web.hosting.dns_record_manager');
        /* @var $dnsRecordManager DnsRecordManager */
        
        $domainBaseRepo = $this->container->get('doctrine')
            ->getRepository('CherezWebHostingBundle:DomainBase');
        /* @var $domainBaseRepo \CherezWeb\HostingBundle\Repository\DomainBaseRepository */
        
        $domainBase = $domainBaseRepo->findOrInitActive($user, $domainName);
        if ($domainBase->getId() === null) {
            // Если базывый домен был только что создан, требуется инициализировать DNS записи.
            $dnsRecordManager->initBaseDomainRecords($domainBase);
        }
        return $domainBase;
    }
}
