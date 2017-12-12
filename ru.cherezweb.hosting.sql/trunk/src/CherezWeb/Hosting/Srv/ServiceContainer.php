<?php

namespace CherezWeb\Hosting\Srv;

use CherezWeb\Hosting\Dns\Service\DnsManager;
use CherezWeb\Hosting\Dns\Service\LogService;
use CherezWeb\Hosting\Dns\Service\StorageService;
use CherezWeb\Hosting\Srv\Service\Parameters;
use CherezWeb\Hosting\Srv\Service\Shell;
use CherezWeb\Hosting\Srv\ServiceAbstract;

class ServiceContainer
{
    private $services = array();

    /**
     * @param string $key
     * @return ServiceAbstract
     */
    public function get($key)
    {
        return $this->services[$key];
    }

    public function set($key, ServiceAbstract $service)
    {
        $this->services[$key] = $service;
    }

    /**
     * @return Parameters
     */
    public function getParameters()
    {
        return $this->get(Parameters::toString());
    }

    /**
     * @return Shell
     */
    public function getShell()
    {
        return $this->get(Shell::toString());
    }

    /**
     * @return StorageService
     */
    public function getStorageService()
    {
        return $this->get(StorageService::toString());
    }

    /**
     * @return DnsManager
     */
    public function getDnsManger()
    {
        return $this->get(DnsManager::toString());
    }

    /**
     * @return DnsManager
     */
    public function getLogService()
    {
        return $this->get(LogService::toString());
    }
}
