<?php

/**
 * Запускать раз в 2 минуты.
 */

require_once __DIR__ . '/../kernel.php';

$shell = \CherezWeb\Hosting\Srv\App::getInstance()->getServiceContainer()->getShell();

try {
    if (!stristr($shell->exec('/usr/sbin/service mysql status'), 'mysql start/running')) {
        throw new \Exception($shell->getAgent()->getLastResult());
    }
    if (!stristr($shell->exec('/usr/sbin/service apache2 status'), 'Apache2 is running')) {
        throw new \Exception($shell->getAgent()->getLastResult());
    }
    if (!stristr($shell->exec('/usr/sbin/service proftpd status'), 'currently running')) {
        throw new \Exception($shell->getAgent()->getLastResult());
    }
} catch (\Exception $exc) {
    restartServices($exc);
}


function restartServices($cause) {
    $parameters = \CherezWeb\Hosting\Srv\App::getInstance()->getServiceContainer()->getParameters();
    $shell = \CherezWeb\Hosting\Srv\App::getInstance()->getServiceContainer()->getShell();
    
    $logFileDirPath = $parameters->get('app.log_dir') . DIRECTORY_SEPARATOR . 'crash_monitor ' . date("Y-m-d") . '.log';
            
    file_put_contents($logFileDirPath, '*********************************' . PHP_EOL, FILE_APPEND);
    file_put_contents($logFileDirPath, 'Cause               (' . date("Y-m-d H:i:s") . '):' . $cause . PHP_EOL, FILE_APPEND);
    file_put_contents($logFileDirPath, '---------------------------------' . PHP_EOL, FILE_APPEND);
	
    try {
        $shell->exec('/usr/sbin/service apache2 restart');
        file_put_contents($logFileDirPath, 'Apache restarted    (' . date("Y-m-d H:i:s") . '):' . $shell->getAgent()->getLastResult() . PHP_EOL, FILE_APPEND);
        file_put_contents($logFileDirPath, '---------------------------------' . PHP_EOL, FILE_APPEND);
        $shell->exec('/usr/sbin/service mysql restart');
        file_put_contents($logFileDirPath, 'MySql restart       (' . date("Y-m-d H:i:s") . '):' . $shell->getAgent()->getLastResult() . PHP_EOL, FILE_APPEND);
        file_put_contents($logFileDirPath, '---------------------------------' . PHP_EOL, FILE_APPEND);
        $shell->exec('/usr/sbin/service proftpd restart');
        file_put_contents($logFileDirPath, 'ProFTPD restart     (' . date("Y-m-d H:i:s") . '):' . $shell->getAgent()->getLastResult() . PHP_EOL, FILE_APPEND);
    } catch (\Exception $exc) {
        file_put_contents($logFileDirPath, 'Error               (' . date("Y-m-d H:i:s") . '):' . $exc . PHP_EOL, FILE_APPEND);
    }
}