<?php
$proftpdMySqlSassword = $argv[1];
const PROFTPD_SQL_CONF_PATH = '/etc/proftpd/sql.conf';
$proftpdSqlConfigTemplateContent = file_get_contents('installer/proftpd_sql_template.conf');
$newProftpdSqlConfigContent = str_replace('%proftpd_mysql_password%', $proftpdMySqlSassword, $proftpdSqlConfigTemplateContent);
file_put_contents(PROFTPD_SQL_CONF_PATH, $newProftpdSqlConfigContent);