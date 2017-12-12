<?php

namespace CherezWeb\Hosting\Dns\Service;

use CherezWeb\Hosting\Dns\DnsRecord;
use CherezWeb\Hosting\Srv\ServiceAbstract;

class DnsManager extends ServiceAbstract
{

    public function handleRecords(array $dnsRecords)
    {
        if (count($dnsRecords)) {
            foreach ($dnsRecords as $dnsRecord) {
                /* @var $dnsRecord DnsRecord */
                if ($dnsRecord->getIsDeleted()) {
                    $this->deleteDnsRecord($dnsRecord);
                } else {
                    $this->createDnsRecord($dnsRecord);
                }
            }
            $this->applyChanges();
        }
    }

    private function createDnsRecord(DnsRecord $dnsRecord)
    {
        $records = $this->loadRecords($dnsRecord->getDomainBaseName());
        $records[$dnsRecord->getId()] = $dnsRecord;
        $this->saveRecords($dnsRecord->getDomainBaseName(), $records);
    }

    private function deleteDnsRecord(DnsRecord $dnsRecord)
    {
        $records = $this->loadRecords($dnsRecord->getDomainBaseName());
        unset($records[$dnsRecord->getId()]);
        $this->saveRecords($dnsRecord->getDomainBaseName(), $records);
    }

    private function getConfigLine($domainBaseName)
    {
        return sprintf('zone "%s" { type master; file "/etc/bind/cherezweb/zones/%s"; };', $domainBaseName, $domainBaseName);
    }

    private function addConfigLine($domainBaseName)
    {
        $params = $this->getServiceContainer()->getParameters();
        $bindConfigFile = $params->get('app.bind_dns.config_path');
        $configLine = $this->getConfigLine($domainBaseName);
        $bindConfigConten = file_get_contents($bindConfigFile);
        if (!stristr($bindConfigConten, $configLine)) {
            file_put_contents($bindConfigFile, $configLine . PHP_EOL, FILE_APPEND);
        }
    }

    private function removeConfigLine($domainBaseName)
    {
        $params = $this->getServiceContainer()->getParameters();
        $bindConfigFile = $params->get('app.bind_dns.config_path');
        $configLine = $this->getConfigLine($domainBaseName);
        $bindConfigConten = file_get_contents($bindConfigFile);
        if (stristr($bindConfigConten, $configLine)) {
            $bindConfigConten = str_replace($configLine . PHP_EOL, '', $bindConfigConten);
            file_put_contents($bindConfigFile, $bindConfigConten);
        }
    }

    private function getRecordFile($domainBaseName)
    {
        $params = $this->getServiceContainer()->getParameters();
        $recordsFilesDir = $params->get('app.bind_dns.zones_dir');
        $recordFilePath = $recordsFilesDir . DIRECTORY_SEPARATOR . $domainBaseName;
        if (!file_exists($recordFilePath)) {
            touch($recordFilePath);
        }
        return $recordFilePath;
    }

    private function getRecordDumpFile($domainBaseName)
    {
        $recordDumpFilePath = $this->getRecordFile($domainBaseName) . '.dump';
        if (!file_exists($recordDumpFilePath)) {
            touch($recordDumpFilePath);
        }
        return $recordDumpFilePath;
    }

    private function loadRecords($domainBaseName)
    {
        $recordDumpFile = $this->getRecordDumpFile($domainBaseName);
        $recordDump = file_get_contents($recordDumpFile);
        $records = array();
        if (!empty($recordDump)) {
            $records = unserialize($recordDump);
        }
        return $records;
    }

    private function saveRecords($domainBaseName, $dnsRecords)
    {
        $recordFile = $this->getRecordFile($domainBaseName);
        $recordDumpFile = $this->getRecordDumpFile($domainBaseName);
        if (count($dnsRecords) > 0) {
            $this->addConfigLine($domainBaseName);
            file_put_contents($recordDumpFile, serialize($dnsRecords));

            $recordFileContentLines = array();
            foreach ($dnsRecords as $dnsRecord) {
                /* @var $dnsRecord DnsRecord */
                if ($dnsRecord->getType() === DnsRecord::TYPE_SOA) {
                    $index = 0;
                } else {
                    $index = count($recordFileContentLines) + 1;
                }
                $recordFileContentLines[$index] = $this->dnsRecordToString($dnsRecord);
            }
            file_put_contents($recordFile, implode(PHP_EOL, $recordFileContentLines));
        } else {
            $this->removeConfigLine($domainBaseName);
            unlink($recordFile);
            unlink($recordDumpFile);
        }
        $this->reloadDnsRecords();
    }

    private function dnsRecordToString(DnsRecord $dnsRecord)
    {
        $dnsRecordString = sprintf('%s IN %s ', $dnsRecord->getHost(), $dnsRecord->getType());
        switch ($dnsRecord->getType()) {
            case DnsRecord::TYPE_SOA:
                $dnsRecordString = '' .
                    sprintf('$TTL %s', $dnsRecord->getValueOption('ttl')) . PHP_EOL .
                    $dnsRecordString . sprintf(
                        '%s %s (%s %s %s %s %s)',
                        $dnsRecord->getValueOption('nsMaster'),
                        $dnsRecord->getValueOption('email'),
                        $dnsRecord->getUpdated(),
                        $dnsRecord->getValueOption('refresh'),
                        $dnsRecord->getValueOption('retry'),
                        $dnsRecord->getValueOption('expire'),
                        $dnsRecord->getValueOption('ttl')
                    );
                break;
            case DnsRecord::TYPE_A:
                $dnsRecordString = '' .
                    $dnsRecordString . sprintf(
                        '%s',
                        $dnsRecord->getValueOption('ip')
                    );
                break;
            case DnsRecord::TYPE_CNAME:
                $dnsRecordString = '' .
                    $dnsRecordString . sprintf(
                        '%s',
                        $dnsRecord->getValueOption('hostForAlias')
                    );
                break;
            case DnsRecord::TYPE_MX:
                $dnsRecordString = '' .
                    $dnsRecordString . sprintf(
                        '%s %s',
                        $dnsRecord->getPriority(),
                        $dnsRecord->getValueOption('mailServer')
                    );
                break;
            case DnsRecord::TYPE_NS:
                $dnsRecordString = '' .
                    $dnsRecordString . sprintf(
                        '%s',
                        $dnsRecord->getValueOption('nsHost')
                    );
                break;
            case DnsRecord::TYPE_TXT:
                $dnsRecordString = '' .
                    $dnsRecordString . sprintf(
                        '%s',
                        $dnsRecord->getValueOption('txt')
                    );
                break;
            default:
                throw new \Exception('Unexpected dns record type: ' . $dnsRecord->getType());
        }
        return $dnsRecordString;
    }

    private function reloadDnsRecords()
    {
        $this->getServiceContainer()->getShell()->exec('service bind9 reload');
    }

    public function dropData()
    {
        $params = $this->getServiceContainer()->getParameters();
        $bindConfigFile = $params->get('app.bind_dns.config_path');
        file_put_contents($bindConfigFile, '');

        $recordsFilesDir = $params->get('app.bind_dns.zones_dir');
        $shell = $this->getServiceContainer()->getShell();
        $shell->exec('rm -rf %s', array($recordsFilesDir . DIRECTORY_SEPARATOR . '*'));
        $this->applyChanges();
    }
    
    private function applyChanges() {
        $shell = $this->getServiceContainer()->getShell();
        $shell->exec('service bind9 restart');
    }
}
