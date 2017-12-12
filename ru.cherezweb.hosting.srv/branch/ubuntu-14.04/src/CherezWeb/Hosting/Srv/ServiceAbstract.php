<?php
namespace CherezWeb\Hosting\Srv;
use CherezWeb\Hosting\Srv\ServiceContainer;
abstract class ServiceAbstract {
    
    private $serviceContainer;
    
    public function __construct(ServiceContainer $serviceContainer) {
        $this->serviceContainer = $serviceContainer;
    }
    
    /**
     * @return ServiceContainer
     */
    protected function getServiceContainer() {
        return $this->serviceContainer;
    }
    
    static function toString() {
        return get_called_class();
    }
}
