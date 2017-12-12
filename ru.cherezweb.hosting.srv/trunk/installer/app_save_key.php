<?php
const PARAMETERS_PATH = 'data/parameters.ini';

$appAccessKey = $argv[1];

$parametersContent = file_get_contents(PARAMETERS_PATH);
$parametersContent = str_ireplace('%APP_ACCESS_KEY_HASH%', md5($appAccessKey), $parametersContent);
file_put_contents(PARAMETERS_PATH, $parametersContent);