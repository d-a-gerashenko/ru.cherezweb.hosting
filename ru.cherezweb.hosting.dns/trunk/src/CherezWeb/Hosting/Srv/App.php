<?php

namespace CherezWeb\Hosting\Srv;

class App extends \CherezWeb\Lib\Pattern\Singleton {

    private $serviceContainer;

    protected function __construct() {
        $this->serviceContainer = new ServiceContainer();
        $this->serviceContainer->set(Service\Parameters::toString(), new Service\Parameters($this->serviceContainer));
        $this->serviceContainer->set(Service\Shell::toString(), new Service\Shell($this->serviceContainer));
        $this->serviceContainer->set(\CherezWeb\Hosting\Dns\Service\DnsManager::toString(), new \CherezWeb\Hosting\Dns\Service\DnsManager($this->serviceContainer));
        $this->serviceContainer->set(\CherezWeb\Hosting\Dns\Service\DnsSynchronizer::toString(), new \CherezWeb\Hosting\Dns\Service\DnsSynchronizer($this->serviceContainer));
        $this->serviceContainer->set(\CherezWeb\Hosting\Dns\Service\LogService::toString(), new \CherezWeb\Hosting\Dns\Service\LogService($this->serviceContainer));
        $this->serviceContainer->set(\CherezWeb\Hosting\Dns\Service\StorageService::toString(), new \CherezWeb\Hosting\Dns\Service\StorageService($this->serviceContainer));
    }
    
    /**
     * @return ServiceContainer
     */
    public function getServiceContainer() {
        return $this->serviceContainer;
    }

}
