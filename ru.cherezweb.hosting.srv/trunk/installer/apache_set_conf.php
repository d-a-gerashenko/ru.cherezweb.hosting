<?php

const APACHE2_CONF_PATH = '/etc/apache2/httpd.conf';

// Настраиваем страницу ошибок.
$errorPagesConfig = 'Alias /error /etc/ru.cherezweb.hosting.srv/default_pages/error
ErrorDocument 404 /error/404/index.html';

// Настраиваем хост по умолчанию, чтобы обрабатывать несуществующие домены.
$defaultVhost = '<VirtualHost *:80>
        DocumentRoot /etc/ru.cherezweb.hosting.srv/default_pages/error/404
</VirtualHost>
<Directory />
    AllowOverride All
    Order allow,deny
    allow from all
    Options -Indexes
</Directory>';

file_put_contents(
    APACHE2_CONF_PATH,
    implode(
        PHP_EOL . PHP_EOL,
        array(
            $errorPagesConfig,
            $defaultVhost,
        )
    )
);