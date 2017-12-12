<?php

namespace CherezWeb\Hosting\Srv\Service;

use CherezWeb\Hosting\Srv\ServiceAbstract;

class DataBaseManager extends ServiceAbstract {

    public function create($allocationName, $databaseName, $databasePassword) {
        $dataBasePath = $this->getDataBasePath($databaseName);
        
        $this->getServiceContainer()->getShell()->exec(
            'mkdir -m 700 %s',
            array (
                $dataBasePath,
            )
        );
        $this->getServiceContainer()->getShell()->exec(
            'chown -R mysql:%s %s',
            array (
                $allocationName,
                $dataBasePath,
            )
        );
        $this->getServiceContainer()->getShell()->exec(
            'chmod g+s %s',
            array (
                $dataBasePath,
            )
        );
        
        $this->getPdo()->exec("
            CREATE USER '{$databaseName}'@'%'
        ");
            
        $this->changeDataBasePassword($databaseName, $databasePassword);
        
        return TRUE;
    }

    public function delete($databaseName) {
        $this->closeActiveConnections($databaseName);
        
        $this->getPdo()->exec("
            DROP USER {$databaseName};
            FLUSH PRIVILEGES;
        ");
            
        $dataBasePath = $this->getDataBasePath($databaseName);
        $this->getServiceContainer()->getShell()->exec(
            'rm -rf %s',
            array (
                $dataBasePath,
            )
        );
        
        // На случай, если удалось все же быстро переконектиться.
        $this->closeActiveConnections($databaseName);
        
        return TRUE;
    }
    
    private function closeActiveConnections($databaseName) {
        // mysql -uuser -ppassword -e 'show processlist' | grep USER_NAME | awk {'print "kill "$1";"'}| mysql -uuser -ppassword
        $activeConnections = $this->getPdo()
            ->query("SELECT * FROM INFORMATION_SCHEMA.PROCESSLIST WHERE USER = '{$databaseName}'");
        while ($row = $activeConnections->fetch(\PDO::FETCH_ASSOC)) {
            $this->getPdo()->exec("kill {$row['ID']}");
        }
    }

    public function changeDataBasePassword($databaseName, $databasePassword) {
        $this->closeActiveConnections($databaseName);
        
        $this->getPdo()->exec("
            GRANT USAGE ON *.* TO '{$databaseName}'@'%' IDENTIFIED BY '{$databasePassword}';
            GRANT ALL PRIVILEGES ON {$databaseName}.* TO '{$databaseName}'@'%';
            FLUSH PRIVILEGES;
        ");
        
        // На случай, если удалось все же быстро переконектиться.
        $this->closeActiveConnections($databaseName);
            
        return TRUE;
    }

    private $pdo;

    protected function getPdo() {
        if (!$this->pdo) {
            $parameters = $this->getServiceContainer()->get(Parameters::toString());
            /* @var $parameters Parameters */

            $this->pdo = new \PDO(
                "mysql:host={$parameters->get('mysql.host')}",
                $parameters->get('mysql.root_user'),
                $parameters->get('mysql.root_user_password'),
                array(
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                )
            );
        }
        return $this->pdo;
    }
    
    public function getDataBasePath($databaseName) {
        $parameters = $this->getServiceContainer()->getParameters();
        return $parameters->get('mysql.bases_dir') . DIRECTORY_SEPARATOR . $databaseName;
    }
    
}
