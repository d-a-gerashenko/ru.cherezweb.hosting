<?php

const PHP_INI_PATH_APACHE = '/etc/php5/apache2/php.ini';
const PHP_INI_PATH_CLI = '/etc/php5/cli/php.ini';

changePhpIni(PHP_INI_PATH_APACHE);
changePhpIni(PHP_INI_PATH_CLI);

function changePhpIni($phpIniPath) {
    $phpIniContent = file_get_contents($phpIniPath);
    
    $phpIniContent = str_replace(
        'short_open_tag = Off',
        ';short_open_tag = Off',
        $phpIniContent
    );
    
    $phpIniContent = str_replace(
        ';opcache.enable=0',
        ';opcache.enable=0' . PHP_EOL .
        'opcache.enable=1',
        $phpIniContent
    );
    $phpIniContent = str_replace(
        ';opcache.enable_cli=0',
        ';opcache.enable_cli=0' . PHP_EOL .
        'opcache.enable_cli=0',
        $phpIniContent
    );
    $phpIniContent = str_replace(
        ';opcache.memory_consumption=64',
        ';opcache.memory_consumption=64' . PHP_EOL .
        'opcache.memory_consumption=128',
        $phpIniContent
    );
    $phpIniContent = str_replace(
        ';opcache.max_accelerated_files=2000',
        ';opcache.max_accelerated_files=2000' . PHP_EOL .
        'opcache.max_accelerated_files=4000',
        $phpIniContent
    );
    
    file_put_contents($phpIniPath, $phpIniContent);
}