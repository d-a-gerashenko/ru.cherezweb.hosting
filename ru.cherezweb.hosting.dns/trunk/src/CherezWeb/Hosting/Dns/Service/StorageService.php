<?php

namespace CherezWeb\Hosting\Dns\Service;

use CherezWeb\Hosting\Srv\ServiceAbstract;

class StorageService extends ServiceAbstract {
     private $storage;

     public function get($key) {
        $this->initStorage();
        return $this->storage->get($key);
    }
    public function set($key, $value) {
        $this->initStorage();
        $this->storage->set($key, $value);
    }
    
    private function initStorage() {
        if ($this->storage === null) {
            $this->storage = new \CherezWeb\Lib\SimpleStorage(
                $this->getServiceContainer()->getParameters()->get('app.temp_dir') . DIRECTORY_SEPARATOR . 'simple_db'
            );
        }
    }
}