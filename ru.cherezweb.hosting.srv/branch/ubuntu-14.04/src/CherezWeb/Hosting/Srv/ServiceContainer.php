<?php

namespace CherezWeb\Hosting\Srv;

use CherezWeb\Hosting\Srv\ServiceAbstract;
use CherezWeb\Hosting\Srv\Service\Parameters;
use CherezWeb\Hosting\Srv\Service\AllocationManager;
use CherezWeb\Hosting\Srv\Service\DomainManager;
use CherezWeb\Hosting\Srv\Service\FtpManager;
use CherezWeb\Hosting\Srv\Service\JobManager;
use CherezWeb\Hosting\Srv\Service\DataBaseManager;
use CherezWeb\Hosting\Srv\Service\Shell;

class ServiceContainer {

    private $services = array();

    /**
     * @param string $key
     * @return ServiceAbstract
     */
    public function get($key) {
        return $this->services[$key];
    }

    public function set($key, ServiceAbstract $service) {
        $this->services[$key] = $service;
    }

    /**
     * @return Parameters
     */
    public function getParameters() {
        return $this->get(Parameters::toString());
    }
    
    /**
     * @return AllocationManager
     */
    public function getAllocationManager() {
        return $this->get(AllocationManager::toString());
    }
    
    /**
     * @return DomainManager
     */
    public function getDomainManager() {
        return $this->get(DomainManager::toString());
    }
    
    /**
     * @return FtpManager
     */
    public function getFtpManager() {
        return $this->get(FtpManager::toString());
    }
    
    /**
     * @return JobManager
     */
    public function getJobManager() {
        return $this->get(JobManager::toString());
    }
    
    /**
     * @return DataBaseManager
     */
    public function getDataBaseManager() {
        return $this->get(DataBaseManager::toString());
    }
    
    /**
     * @return Shell
     */
    public function getShell() {
        return $this->get(Shell::toString());
    }
    
}
