<?php

namespace CherezWeb\HostingBundle\Repository;

use CherezWeb\HostingBundle\Entity\DnsRecord;
use Doctrine\ORM\EntityRepository;

class DnsRecordRepository extends EntityRepository
{

    /**
     * После этой даты удаленные записи окончательно удаляются, а
     * синхронизация для серверов не успевших синхронизироваться дальше этой
     * записи становистя невозможной.
     */
    private $deletedExpiredDate;
    private $unusedExpiredDate;

    public function __construct($em, \Doctrine\ORM\Mapping\ClassMetadata $class)
    {
        parent::__construct($em, $class);
        $this->deletedExpiredDate = $this->unusedExpiredDate = new \DateTime('-15 day');
    }

    public function cleanupUnused($limit = 100)
    {

        $em = $this->getEntityManager();

        $em->getConnection()->beginTransaction();
        try {
            $unusedDnsRecords = $this
                ->createQueryBuilder('dr')
                ->join('dr.syncPos', 'sp')
                ->leftJoin('CherezWebHostingBundle:DomainBase', 'db', 'WITH', 'db.name = dr.domainBaseName')
                ->select('dr, count(db.id) AS HIDDEN dbCount')
                ->where('dr.isDeleted = :isDeleted')
                ->andWhere('sp.created < :unusedExpiredDate')
                ->andWhere('db IS NULL')
                ->groupBy('dr.id')
                ->having('dbCount = 0')
                ->setParameter('isDeleted', false)
                ->setParameter('unusedExpiredDate', $this->unusedExpiredDate, 'utcdatetime')
                ->getQuery()
                ->setMaxResults($limit)
                ->getResult();

            foreach ($unusedDnsRecords as $unusedDnsRecord) {
                /* @var $unusedDnsRecord DnsRecord */
                $unusedDnsRecord->setIsDeleted(true);
            }

            $em->flush();
            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollback();
            throw new \Exception("Ошибка при удалении неиспользуемых DNS записей: " . $e->getMessage());
        }
    }

    /**
     * Удаляет помеченные на удаление DNS записи старше порога синхронизации.
     */
    public function cleanupDeleted()
    {

        $em = $this->getEntityManager();

        $em->getConnection()->beginTransaction();
        try {

            $syncLimit = $this->getSyncLimit();

            if ($syncLimit === null) {
                $em->getConnection()->rollBack();
                return;
            }

            $this
                ->createQueryBuilder('dr')
                ->delete()
                ->where('dr.isDeleted = :isDeleted')
                ->andWhere('dr.syncPos < :syncPos')
                ->setParameter('isDeleted', true)
                ->setParameter('syncPos', $syncLimit->getSyncPos()->getId())
                ->getQuery()
                ->execute();

            $em->flush();
            $em->getConnection()->commit();
        } catch (\Exception $e) {
            $em->getConnection()->rollback();
            throw new \Exception("Ошибка при очистке удаленных DNS записей: " . $e->getMessage());
        }
    }

    /**
     * Возвращает самую младшую запись из списка устаревших, она служит порогом
     * для синхронизации. DNS серверы, не успевшие её обработать, больше не
     * могут продолжать синхронизацию, и процесс синхронизации нужно начинать
     * заново. Такая ситуация возможна в тех случаях, когда сервер долго не
     * подключался для синхронизации.
     * @return DnsRecord Если возвращается null,
     * значит порога синхронизации еще нет, и не было ни одного удаления.
     */
    public function getSyncLimit()
    {
        return $this
                ->createQueryBuilder('dr')
                ->join('dr.syncPos', 'sp')
                ->where('dr.isDeleted = :isDeleted')
                ->andWhere('sp.created < :expireDate')
                ->setParameter('isDeleted', true)
                ->setParameter('expireDate', $this->deletedExpiredDate, 'utcdatetime')
                ->orderBy('dr.syncPos', 'desc')
                ->getQuery()
                ->setMaxResults(1)
                ->getOneOrNullResult();
    }

    public function findByDomainBase(\CherezWeb\HostingBundle\Entity\DomainBase $domainBase)
    {
        return $this
                ->createQueryBuilder('dr')
                ->join('dr.syncPos', 'sp')
                ->where('dr.isDeleted = :isDeleted')
                ->andWhere('dr.domainBaseName = :domainBaseName')
                ->setParameter('isDeleted', false)
                ->setParameter('domainBaseName', $domainBase->getName())
                ->orderBy('dr.type')
                ->addOrderBy('dr.host')
                ->addOrderBy('dr.priority')
                ->addOrderBy('dr.value')
                ->getQuery()
                ->getResult();
    }

    /**
     * 
     * @param string $domainName
     * @param string $ip
     * @return DnsRecord
     */
    public function createOrUpdateARecord($domainName, $ip)
    {
        $pieces = explode('.', $domainName);
        $domainBaseName = implode('.', array_slice($pieces, -2));

        $newDnsRecord = new DnsRecord();
        $newDnsRecord->setType(DnsRecord::TYPE_A);
        $newDnsRecord->setDomainBaseName($domainBaseName);
        $newDnsRecord->setHostInDomainStyle($domainName);
        $newDnsRecord->setValue(array('ip' => $ip));

        $dnsRecord = $this->findOneBy(array(
            'isDeleted' => false,
            'type' => $newDnsRecord->getType(),
            'domainBaseName' => $newDnsRecord->getDomainBaseName(),
            'host' => $newDnsRecord->getHost(),
        ));
        if ($dnsRecord === null) {
            $dnsRecord = $newDnsRecord;
            $this->getEntityManager()->persist($dnsRecord);
        } else {
            $dnsRecord->setValue($newDnsRecord->getValue());
        }

        return $dnsRecord;
    }

    /**
     * 
     * @param type $domainBaseName
     * @param type $ns
     * @param type $email
     * @param type $refresh
     * @param type $retry
     * @param type $expire
     * @param type $ttl
     * @return DnsRecord
     */
    public function createOrUpdateBaseSoaRecord($domainBaseName, $ns, $email, $refresh, $retry, $expire, $ttl)
    {
        $newDnsRecord = new DnsRecord();
        $newDnsRecord->setRestrictionLevel(10);
        $newDnsRecord->setType(DnsRecord::TYPE_SOA);
        $newDnsRecord->setDomainBaseName($domainBaseName);
        $newDnsRecord->setHostInDomainStyle($domainBaseName);
        $newDnsRecord->setValue(array(
            'nsMaster' => $ns,
            'email' => $email,
            'refresh' => $refresh,
            'retry' => $retry,
            'expire' => $expire,
            'ttl' => $ttl,
        ));

        $dnsRecord = $this->findOneBy(array(
            'isDeleted' => false,
            'type' => $newDnsRecord->getType(),
            'domainBaseName' => $newDnsRecord->getDomainBaseName(),
            'host' => $newDnsRecord->getHost(),
        ));

        if ($dnsRecord === null) {
            $dnsRecord = $newDnsRecord;
            $this->getEntityManager()->persist($dnsRecord);
        } else {
            $dnsRecord->setValue($newDnsRecord->getValue());
        }

        return $dnsRecord;
    }

    public function createOrUpdateBaseNsRecords($domainBaseName, $nsRecordsValues)
    {
        $newDnsRecordTemplate = new DnsRecord();
        $newDnsRecordTemplate->setType(DnsRecord::TYPE_NS);
        $newDnsRecordTemplate->setDomainBaseName($domainBaseName);
        $newDnsRecordTemplate->setHostInDomainStyle($domainBaseName);
        $newDnsRecordTemplate->setRestrictionLevel(20);

        $oldNsRecords = $this->findBy(array(
            'isDeleted' => false,
            'type' => $newDnsRecordTemplate->getType(),
            'domainBaseName' => $newDnsRecordTemplate->getDomainBaseName(),
            'host' => $newDnsRecordTemplate->getHost(),
        ));
        foreach ($oldNsRecords as $oldNsRecord) {
            /* @var $oldNsRecord DnsRecord */
            $oldNsRecord->setIsDeleted(true);
        }

        $dnsRecords = array();
        foreach ($nsRecordsValues as $nsRecordValue) {
            $newDnsRecord = clone $newDnsRecordTemplate;
            $dnsRecords[] = $newDnsRecord;
            /* @var $newDnsRecord DnsRecord */
            $newDnsRecord->setValue(array('nsHost' => $nsRecordValue));
            $this->getEntityManager()->persist($newDnsRecord);
        }

        return $dnsRecords;
    }

    public function findUnique($fields)
    {
        return $this->createQueryBuilder('dr')
                ->where('dr.domainBaseName = :domainBaseName')
                ->andWhere('dr.host = :host')
                ->andWhere('dr.type = :type')
                ->andWhere('dr.value = :value')
                ->andWhere('dr.isDeleted = :isDeleted')
                ->setParameter('domainBaseName', $fields['domainBaseName'])
                ->setParameter('host', $fields['host'])
                ->setParameter('type', $fields['type'])
                ->setParameter('value', serialize(DnsRecord::normalizeValue($fields['value'], $fields['type'])))
                ->setParameter('isDeleted', $fields['isDeleted'])
                ->getQuery()
                ->getResult();
    }

    public function findRecordsForSync($syncPos = null, $limit = 100)
    {
        $qb = $this->createQueryBuilder('dr');
        if ($syncPos !== null) {
            $qb->andWhere('dr.syncPos > :syncPos')
                ->setParameter('syncPos', $syncPos);
        }
        return $qb->orderBy('dr.syncPos')
            ->getQuery()
            ->setMaxResults($limit)
            ->getResult();
    }
}
