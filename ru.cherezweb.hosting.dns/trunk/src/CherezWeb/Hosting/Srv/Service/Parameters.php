<?php

namespace CherezWeb\Hosting\Srv\Service;

use CherezWeb\Hosting\Srv\ServiceAbstract;

class Parameters extends ServiceAbstract {

    const PARAMETERS_FILE = 'data/parameters.ini';

    private $parameters;

    public function get($key) {
        if ($this->parameters === NULL) {
            $this->parameters = parse_ini_file(self::PARAMETERS_FILE);
        }
        
        return $this->parameters[$key];
    }

}
