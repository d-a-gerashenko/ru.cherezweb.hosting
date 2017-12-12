<?php

const APACHE2_CONF_PATH = '/etc/apache2/conf-enabled/ru.cherezweb.hosting.srv.conf';

// Настраиваем страницу ошибок.
$directoryAccess = '
<Directory /etc/ru.cherezweb.hosting.srv/default_pages/>
        Require all granted
</Directory>
<Directory /etc/ru.cherezweb.hosting.srv/www/>
        Require all granted
</Directory>
<Directory /var/allocations/>
        Require all granted
</Directory>';

// Настраиваем страницу ошибок.
$errorPagesConfig = 'Alias /error /etc/ru.cherezweb.hosting.srv/default_pages/error
ErrorDocument 404 /error/404/index.html';

// Настраиваем хост по умолчанию, чтобы обрабатывать несуществующие домены.
$defaultVhost = '<VirtualHost *:80>
        DocumentRoot /etc/ru.cherezweb.hosting.srv/default_pages/error/404
</VirtualHost>
<Directory />
    AllowOverride All
    Options -Indexes
</Directory>';

// Настраиваем инклуд хостов, так как в новой версии изменились требования, а мы их обходим.
$allocationsVhosts = 'IncludeOptional sites-enabled/allocations/*';

file_put_contents(
    APACHE2_CONF_PATH,
    implode(
        PHP_EOL . PHP_EOL,
        array(
            $directoryAccess,
            $errorPagesConfig,
            $defaultVhost,
            $allocationsVhosts,
        )
    )
);