<?php
const PROFTPD_CONF_PATH = '/etc/proftpd/proftpd.conf';
$proftpdConfigContent = file_get_contents(PROFTPD_CONF_PATH);
// ProFTPd не переопределяет параметры.
$newConfRowsBefore = array(
    '# ru.cherezweb.hosting.srv',
    'TimeoutNoTransfer 60',
    'TimeoutStalled 60',
    'TimeoutIdle 60',
    'MaxInstances 50',
    'DefaultRoot ~',
    'RequireValidShell off',
    'IdentLookups off',
    'ServerIdent on "FTP Server ready."',
);
// Инклуд вызывает ошибку в начале конфига, потому его в конец.
$newConfRowsAfter = array(
    '# ru.cherezweb.hosting.srv',
    'Include /etc/proftpd/sql.conf',
);
$newProftpdConfigContent = implode("\r\n", $newConfRowsBefore)."\r\n\r\n".$proftpdConfigContent."\r\n".implode("\r\n", $newConfRowsAfter)."\r\n";
file_put_contents(PROFTPD_CONF_PATH, $newProftpdConfigContent);