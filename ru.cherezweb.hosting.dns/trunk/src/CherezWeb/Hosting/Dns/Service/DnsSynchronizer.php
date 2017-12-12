<?php

namespace CherezWeb\Hosting\Dns\Service;

use CherezWeb\Hosting\Dns\DnsRecord;
use CherezWeb\Hosting\Srv\ServiceAbstract;

class DnsSynchronizer extends ServiceAbstract
{
    const SYNC_STATE_CLEAN  = null;
    const SYNC_STATE_CHASE  = 'chase';
    const SYNC_STATE_NORMAL = 'normal';

    public function synchronize()
    {
        $dnsManager = $this->getServiceContainer()->getDnsManger();
        $data = $this->loadData();
        $responseRecords = $data['records'];
        $responseSyncLimit = $data['syncLimit'];
        if ($this->getState() === self::SYNC_STATE_CLEAN) {
            $dnsManager->handleRecords($responseRecords);
            $this->setState(self::SYNC_STATE_CHASE);
            $this->setSyncLimit($responseSyncLimit);
            if (count($responseRecords)) {
                $lastRecord = end($responseRecords);
                $this->setSyncPos($lastRecord->getSyncPos());
            }
            return;
        } elseif ($this->getState() === self::SYNC_STATE_CHASE) {
            if ($this->getSyncLimit() !== $responseSyncLimit) {
                $this->reset();
                return;
            }
            $dnsManager->handleRecords($responseRecords);
            if (count($responseRecords)) {
                $lastRecord = end($responseRecords);
                $this->setSyncPos($lastRecord->getSyncPos());
            }
            if (count($responseRecords) === 0
                || ($this->getSyncPos() !== null && $this->getSyncLimit() !== null && $this->getSyncPos() >= $this->getSyncLimit())) {
                $this->setState(self::SYNC_STATE_NORMAL);
            }
        } elseif ($this->getState() === self::SYNC_STATE_NORMAL) {
            if ($responseSyncLimit !== null && $this->getSyncPos() < $responseSyncLimit) {
                $this->reset();
                return;
            }
            $dnsManager->handleRecords($responseRecords);
            $this->setSyncLimit(null);
            if (count($responseRecords)) {
                $lastRecord = end($responseRecords);
                $this->setSyncPos($lastRecord->getSyncPos());
            }
        }
    }

    private function getState()
    {
        return $this->getServiceContainer()->getStorageService()->get('sync_state');
    }

    private function setState($value)
    {
        $this->getServiceContainer()->getStorageService()->set('sync_state',
            $value);
    }

    private function getSyncPos()
    {
        return $this->getServiceContainer()->getStorageService()->get('sync_pos');
    }

    private function setSyncPos($value)
    {
        $this->getServiceContainer()->getStorageService()->set('sync_pos',
            $value);
    }

    private function getSyncLimit()
    {
        return $this->getServiceContainer()->getStorageService()->get('sync_limit');
    }

    private function setSyncLimit($value)
    {
        $this->getServiceContainer()->getStorageService()->set('sync_limit',
            $value);
    }

    private function reset()
    {
        $this->getServiceContainer()->getDnsManger()->dropData();
        $this->setState(null);
        $this->setSyncLimit(null);
        $this->setSyncPos(null);
    }

    private function loadData()
    {
        $serverUrl = $this->getServiceContainer()->getParameters()->get('app.main_server_url');
        if ($this->getSyncPos() !== null) {
            $serverUrl = $serverUrl . '?' . http_build_query(array('syncPos' => $this->getSyncPos()));
        }
        $responseInJson = file_get_contents($serverUrl);
        $responseData = @json_decode($responseInJson, true);
        if ($responseData === null) {
            throw new \Exception('Can\'t decode data.');
        }
        $requiredKeys = array('records', 'syncLimit');
        $dataKeys = array_keys($responseData);
        if (count($requiredKeys) !== count($dataKeys)
            &&  count(array_diff($requiredKeys, $dataKeys))) {
            throw new \Exception('Unexpected response data.');
        }
        foreach ($responseData['records'] as &$record) {
            $record = DnsRecord::createFromArray($record);
        }
        return $responseData;
    }
}
