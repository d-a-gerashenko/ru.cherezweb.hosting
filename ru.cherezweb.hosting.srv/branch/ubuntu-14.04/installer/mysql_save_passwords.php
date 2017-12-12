<?php
const PARAMETERS_PATH = 'data/parameters.ini';

$mysqlRootPassword = $argv[1];
$proftpdMysqlPassword = $argv[2];

$parametersContent = file_get_contents(PARAMETERS_PATH);
$parametersContent = str_ireplace('%MYSQL_ROOT_PASSWORD%', $mysqlRootPassword, $parametersContent);
$parametersContent = str_ireplace('%PROFTPD_MYSQL_PASSWORD%', $proftpdMysqlPassword, $parametersContent);
file_put_contents(PARAMETERS_PATH, $parametersContent);