<?php
const CONF_PATH = '/etc/php5/conf.d/ming.ini';
$configContent = file_get_contents(CONF_PATH);
$newConfigContent = str_replace('#', ';', $configContent);
file_put_contents(CONF_PATH, $newConfigContent);