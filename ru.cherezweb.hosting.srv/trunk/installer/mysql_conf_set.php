<?php
const MYSQL_CONF_PATH = '/etc/mysql/my.cnf';
$mysqlConfigContent = file_get_contents(MYSQL_CONF_PATH);
$mysqlConfigContent = str_ireplace('[client]', '[client]' . PHP_EOL . 'default-character-set=utf8', $mysqlConfigContent);
$mysqlConfigContent = str_ireplace('[mysql]', '[mysql]' . PHP_EOL . 'default-character-set=utf8', $mysqlConfigContent);
$mysqlConfigContent = str_ireplace(
    '[mysqld]',
    '[mysqld]' . PHP_EOL .
    'collation-server=utf8_unicode_ci' . PHP_EOL .
    'init-connect=\'SET NAMES utf8\'' . PHP_EOL .
    'init-connect=\'SET NAMES utf8\'' . PHP_EOL .
    'character-set-server=utf8',
    $mysqlConfigContent
);
file_put_contents(MYSQL_CONF_PATH, $mysqlConfigContent);