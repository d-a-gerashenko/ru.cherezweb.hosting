<?php

namespace CherezWeb\HostingBundle\Controller;

use CherezWeb\HostingBundle\Entity\DnsRecord;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class DnsApiController extends Controller{
    
	public function apiAction(Request $request) {
        $dnsRecordRepo = $this->getDoctrine()->getRepository('CherezWebHostingBundle:DnsRecord');
        /* @var $dnsRecordRepo \CherezWeb\HostingBundle\Repository\DnsRecordRepository */
        
        $syncLimit = $dnsRecordRepo->getSyncLimit();
        $syncLimitId = ($syncLimit === null) ? null : $syncLimit->getSyncPos()->getId();
        return new JsonResponse(
            array(
                'syncLimit' => $syncLimitId,
                'records' => self::dnsRecordsToArray($dnsRecordRepo->findRecordsForSync($request->get('syncPos')))
            )
        );
    }
    
    private static function dnsRecordsToArray(array $dnsRecords) {
        $result = array();
        foreach($dnsRecords as $dnsRecord) {
            $result[] = self::dnsRecordToArray($dnsRecord);
        }
        return $result;
    }
    
    private static function dnsRecordToArray(DnsRecord $dnsRecord) {
        return array(
            'id' => $dnsRecord->getId(),
            'syncPos' => $dnsRecord->getSyncPos()->getId(),
            'updated' => $dnsRecord->getSyncPos()->getCreated()->getTimestamp(),
            'type' => $dnsRecord->getType(),
            'domainBaseName' => $dnsRecord->getDomainBaseName(),
            'host' => $dnsRecord->getHost(),
            'value' => $dnsRecord->getValue(),
            'priority' => $dnsRecord->getPriority(),
            'isDeleted' => $dnsRecord->getIsDeleted(),
        );
    }
    
}
