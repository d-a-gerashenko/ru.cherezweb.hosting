<?php

header('Content-Type: text/html; charset=utf-8');

set_error_handler(function ($errno, $errstr, $errfile, $errline ) {
    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
}, E_ALL);

spl_autoload_register(function ($class) {
    $filePath = __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    if (!file_exists($filePath)) {
        exit(sprintf('File not found: %s.', $filePath) . PHP_EOL);
    }
    require_once $filePath;
});

chdir(__DIR__);

set_time_limit(4 * 60);