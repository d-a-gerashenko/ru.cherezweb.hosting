<?php
require_once __DIR__ . '/kernel.php';



try {
    $serviceContainer = \CherezWeb\Hosting\Srv\App::getInstance()->getServiceContainer();
    $connections = $serviceContainer->getStorageService()->get('connections');
    if ($connections === null) {
        $connections = array();
    }
    $servers = array();
    foreach (include('data/servers.php') as $server) {
        $serverIp = $server['ip'];
        if (key_exists($serverIp, $servers)) {
            throw new \Exception('Duplicated ip: ' . $serverIp);
        }
        $servers[$server['ip']] = $server;
    }
    foreach(array_diff(array_keys($connections), array_keys($servers)) as $ipToDele) {
        $pidToKill = $connections[$ipToDele];
        try {
            $serviceContainer->getShell()->exec('kill -9 %s', array($pidToKill));
        } catch (\Exception $ex) {

        }
        echo sprintf("Process #%s killed.", $pidToKill) . PHP_EOL;
        unset($connections[$ipToDele]);
    }
    foreach ($servers as $server) {
        $serverIp = $server['ip'];
        if (key_exists($serverIp, $connections)) {
            $pid = $connections[$serverIp];
            try {
                $serviceContainer->getShell()->exec('ps -p %s', array($pid));
                continue;
            } catch (\Exception $ex) {
                echo sprintf('Process #%s is not runing.' . PHP_EOL, $pid);
            }
        }
        
        $pid = $startResult = $serviceContainer->getShell()->exec('sshpass -p%s ssh -tt -o StrictHostKeyChecking=no -L %s:localhost:3306 %s@%s >/dev/null 2>/dev/null & echo $!', array(
            $server['password'],
            $server['port'],
            $server['user'],
            $server['ip'],
        ));

        $connections[$serverIp] = $pid;
        $serviceContainer->getStorageService()->set('connections', $connections);
    }
    echo 'Current connections: ' . PHP_EOL;
    print_r($connections);
} catch (\Exception $exc) {
    \CherezWeb\Hosting\Srv\App::getInstance()->getServiceContainer()->get(\CherezWeb\Hosting\Dns\Service\LogService::toString())->log((string) $exc, \CherezWeb\Hosting\Dns\Service\LogService::TYPE_ERROR);
    echo (string) $exc;
}