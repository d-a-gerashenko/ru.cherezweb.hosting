<?php

namespace CherezWeb\Hosting\Srv;

class App extends \CherezWeb\Lib\Pattern\Singleton {

    private $serviceContainer;

    protected function __construct() {
        $this->serviceContainer = new ServiceContainer();
        $this->serviceContainer->set(Service\Parameters::toString(), new Service\Parameters($this->serviceContainer));
        $this->serviceContainer->set(Service\Shell::toString(), new Service\Shell($this->serviceContainer));
        $this->serviceContainer->set(Service\AllocationManager::toString(), new Service\AllocationManager($this->serviceContainer));
        $this->serviceContainer->set(Service\DataBaseManager::toString(), new Service\DataBaseManager($this->serviceContainer));
        $this->serviceContainer->set(Service\DomainManager::toString(), new Service\DomainManager($this->serviceContainer));
        $this->serviceContainer->set(Service\FtpManager::toString(), new Service\FtpManager($this->serviceContainer));
        $this->serviceContainer->set(Service\JobManager::toString(), new Service\JobManager($this->serviceContainer));
        $this->serviceContainer->set(Service\Commander::toString(), new Service\Commander($this->serviceContainer));
    }
    
    /**
     * @return ServiceContainer
     */
    public function getServiceContainer() {
        return $this->serviceContainer;
    }

}
