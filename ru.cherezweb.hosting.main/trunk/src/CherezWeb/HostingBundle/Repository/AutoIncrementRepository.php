<?php

namespace CherezWeb\HostingBundle\Repository;

use Doctrine\ORM\EntityRepository;

class AutoIncrementRepository extends EntityRepository
{

    public function cleanup()
    {
        $expireDate = new \DateTime('-1 day');
        $aiForDnsRecordsQB = $this->getEntityManager()
            ->getRepository('CherezWebHostingBundle:DnsRecord')
            ->createQueryBuilder('dr')
            ->select('IDENTITY(dr.syncPos)');

        $deletQB = $this->createQueryBuilder('ai');
        $deletQB
            ->delete()
            ->where('ai.created < :expireDate')
            // Для каждого entity, которое использует AutoIncrement, нужно добавлять дополнительное такое условие.
            ->andwhere($deletQB->expr()->notIn('ai.id', $aiForDnsRecordsQB->getDQL()))
            ->setParameter('expireDate', $expireDate, 'utcdatetime')
            ->getQuery()
            ->execute();
    }
}
