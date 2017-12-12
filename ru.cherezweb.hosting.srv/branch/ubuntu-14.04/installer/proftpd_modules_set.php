<?php
const PROFTPD_MODULES_CONF_PATH = '/etc/proftpd/modules.conf';
$proftpdModulesConfigContent = file_get_contents(PROFTPD_MODULES_CONF_PATH);
$newConfRows = array(
    '# ru.cherezweb.hosting.srv',
    'LoadModule mod_sql.c',
    'LoadModule mod_sql_mysql.c',
);
$newProftpdModulesConfigContent = $proftpdModulesConfigContent."\r\n".implode("\r\n", $newConfRows)."\r\n";
file_put_contents(PROFTPD_MODULES_CONF_PATH, $newProftpdModulesConfigContent);