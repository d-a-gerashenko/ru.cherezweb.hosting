<?php

namespace CherezWeb\HostingBundle\Service;

use CherezWeb\HostingBundle\Entity\Server;

class ServerCommander {
    
    const COMMAND_ALLOCATION_CREATE = 'allocation_creat';
    const COMMAND_ALLOCATION_DELETE = 'allocation_delete';
    const COMMAND_ALLOCATION_CHANGE_PASSWORD = 'allocation_change_password';
    const COMMAND_FTP_CREATE = 'ftp_create';
    const COMMAND_FTP_DELETE = 'ftp_delete';
    const COMMAND_FTP_CHANGE_PASSWORD = 'ftp_change_password';
    const COMMAND_FTP_UPDATE_DIR_PATH = 'ftp_update_dir_path';
    const COMMAND_DATABASE_CREATE = 'database_create';
    const COMMAND_DATABASE_DELETE = 'database_delete';
    const COMMAND_DATABASE_CHANGE_PASSWORD = 'database_change_password';
    const COMMAND_DOMAIN_CREATE = 'domain_create';
    const COMMAND_DOMAIN_DELETE = 'domain_delete';
    const COMMAND_DOMAIN_DISABLE = 'domain_disable';
    const COMMAND_DOMAIN_ENABLE = 'domain_enable';
    const COMMAND_JOB_CRATE = 'job_crate';
    const COMMAND_JOB_DELETE = 'job_delete';

    static function getCommandVariants() {
        $prefix = 'COMMAND_';
        $variants = array();
        $refl = new \ReflectionClass(get_called_class());
        foreach ($refl->getConstants() as $name => $value) {
            if (strpos($name, $prefix) === 0) {
                $variants[] = $value;
            }
        }
        return $variants;
    }

    /**
     * 
     * @param Server $server
     * @param string $command
     * @param array $parameters
     * @return mixed
     * @throws \Exception
     */
    public function executeCommand(Server $server, $command, array $parameters = array()) {
        if (!in_array($command, self::getCommandVariants())) {
            throw new \Exception(sprintf('Неправильная команда: %s', $command));
        }
        
        $response = $this->makeRequest($server, array (
            'command' => $command,
            'parameters' => $parameters,
            'accessKey' => $server->getAccessKey()
        ));
        
        if ($response['error'] !== NULL) {
            throw new \Exception(sprintf('Сервер вернул ошибку при выполнении команды: %s.', $response['error']));
        }

        return $response['result'];
    }
    
    private function makeRequest(Server $server, $request) {
        $postData = http_build_query(
            array(
                'data' => json_encode($request)
            )
        );
        $opts = array('http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'timeout' => 6 * 60,
                'content' => $postData
            )
        );
        $context  = stream_context_create($opts);
        $responseString = file_get_contents(
            $this->getServerUrl($server),
            FALSE,
            $context
        );
        $response = json_decode(
            $responseString,
            TRUE
        );
        
        if (!is_array($response) || array('result', 'error') != array_keys($response)) {
            throw new \Exception(sprintf("Неправильный формат ответа: %s\n---\n%s\n---\n.", var_export($response, TRUE), $responseString));
        }
        return $response;
    }


    /**
     * Можно передавать данные и в URL, но запросы могут кешироваться в логах,
     * в резульатате чего ключ доступа может попасть в логи.
     */
    private function getServerUrl(Server $server, $request = NULL) {
        if ($server->getIpAddress() == '') {
            throw new \Exception(sprintf('Невозможно сгенерировать backendUrl сервера "%s", не задан ipAddress.', $server->getId()));
        }
        $url = "http://{$server->getIpAddress()}:81/";
        if ($request !== NULL) {
            $url .= "?data=" . urlencode(
                json_encode(
                    $request
                )
            );
        }
        return $url;
    }

}
