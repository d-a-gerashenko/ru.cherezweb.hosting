<?php

namespace CherezWeb\Hosting\Dns\Service;

use CherezWeb\Hosting\Srv\ServiceAbstract;

class LogService extends ServiceAbstract
{
    const TYPE_INFO = 'INFO';
    const TYPE_WARNING = 'WARNING';
    const TYPE_ERROR = 'ERROR';
    
    public function log($message, $type)
    {
        if (in_array($type, array( self::TYPE_INFO, self::TYPE_WARNING, self::TYPE_ERROR))) {

        }
        $logFile = $this->getServiceContainer()->getParameters()->get('app.log_dir') . DIRECTORY_SEPARATOR . 'app-' . date('Y-m-d');
        file_put_contents($logFile, sprintf('%s: %s' . PHP_EOL, $type, $message), FILE_APPEND);
    }
}
