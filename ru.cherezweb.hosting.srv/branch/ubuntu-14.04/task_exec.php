<?php

require_once __DIR__ . '/kernel.php';

try {
    $params = json_decode(urldecode($argv[1]), TRUE);
    $command = $params['command'];
    $parameters = $params['parameters'];
    
    $result = \CherezWeb\Hosting\Srv\App::getInstance()->getServiceContainer()->get(\CherezWeb\Hosting\Srv\Service\Commander::toString())->execute($command, $parameters);
    
    echo json_encode(array('result' => $result, 'error' => NULL));
} catch (\Exception $exc) {
    echo json_encode(array('result' => NULL, 'error' => (string)$exc));
}