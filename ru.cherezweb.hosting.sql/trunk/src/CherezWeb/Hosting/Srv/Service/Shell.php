<?php

namespace CherezWeb\Hosting\Srv\Service;

use CherezWeb\Hosting\Srv\ServiceAbstract;
use CherezWeb\Lib\Shell\Agent;

class Shell extends ServiceAbstract {

    protected $shellAgent = NULL;
    
    public function exec($command, array $args = array()) {
        if ($this->shellAgent === NULL) {
            $this->shellAgent = new Agent();
            if (!$this->shellAgent->checkIsRoot()) {
                throw new \Exception('Для выполнения комманд необходим root доступ.');
            }
        }
        return $this->shellAgent->exec($command, $args);
    }
    
    /**
     * @return Agent
     */
    public function getAgent() {
        return $this->shellAgent;
    }

}
