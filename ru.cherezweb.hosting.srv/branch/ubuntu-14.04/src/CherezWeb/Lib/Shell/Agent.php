<?php

namespace CherezWeb\Lib\Shell;

class Agent {

    protected $sudoPassword = '';
    
    public function getSudoPassword() {
        return $this->sudoPassword;
    }

    public function setSudoPassword($sudoPassword) {
        $this->sudoPassword = (string)$sudoPassword;
    }

    public function checkSudoPassword() {
        $checkSudoResult = $this->sudoExec('whoami');
        if ($checkSudoResult === 'root') {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    public function checkIsRoot() {
        $checkSudoResult = $this->exec('whoami');
        if ($checkSudoResult === 'root') {
            return TRUE;
        } else {
            return FALSE;
        }
    }
    
    private $lastResult = NULL;
    
    public function getLastResult() {
        return $this->lastResult;
    }
    
    private $lastError = NULL;
    
    public function getLastError() {
        return $this->lastError;
    }
    
    private $lastStatus = NULL;
    
    public function getLastStatus() {
        return $this->lastStatus;
    }
    
    public function resetLastData() {
        $this->lastStatus = NULL;
        $this->lastResult = NULL;
        $this->lastError = NULL;
    }

    public function exec($command, array $args = array()) {
        $this->resetLastData();
        
        if ($args) {
            $command = vsprintf($command, $args);
        }
        
        $descriptorspec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("pipe", "w")
        );

        $pipes = array();
        $process = proc_open($command, $descriptorspec, $pipes);

        if (is_resource($process)) {
            fclose($pipes[0]);

            $this->lastResult = trim(stream_get_contents($pipes[1]));
            fclose($pipes[1]);
            $this->lastError = trim(stream_get_contents($pipes[2]));
            fclose($pipes[2]);

            // It is important that you close any pipes before calling
            // proc_close in order to avoid a deadlock
            $this->lastStatus = proc_close($process);
            
            if ($this->lastStatus !== 0) {
                throw new \Exception(sprintf('Ошибка (%s) при выполнении shell команды (%s):' . PHP_EOL . 'result: %s' . PHP_EOL . 'error: %s.' . PHP_EOL, $this->lastStatus, $command, $this->lastResult, $this->lastError));
            }
            return $this->lastResult;
        } else {
            throw new \Exception(sprintf('Не удалось запустить процесс выполнения команды: %s.' , $command));
        }
    }
    
    public function sudoExec($command, array $args = array()) {
        return $this->exec(
            sprintf(
                'echo "%s" | sudo -S -- bash -c "%s"',
                $this->escapeBoudleQuotedCommand($this->sudoPassword),
                $this->escapeBoudleQuotedCommand($command)
            ),
            $args
        );
    }
    
    protected function escapeBoudleQuotedCommand($command) {
        return str_replace("\\'", "'",addslashes($command));
    }

}
