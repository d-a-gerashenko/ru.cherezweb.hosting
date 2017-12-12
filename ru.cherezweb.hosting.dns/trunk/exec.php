<?php

require_once __DIR__ . '/kernel.php';



try {
    \CherezWeb\Hosting\Srv\App::getInstance()->getServiceContainer()->get(\CherezWeb\Hosting\Dns\Service\DnsSynchronizer::toString())->synchronize();
} catch (\Exception $exc) {
    \CherezWeb\Hosting\Srv\App::getInstance()->getServiceContainer()->get(\CherezWeb\Hosting\Dns\Service\LogService::toString())->log((string)$exc, \CherezWeb\Hosting\Dns\Service\LogService::TYPE_ERROR);
    echo (string)$exc;
}