<?php

namespace CherezWeb\Hosting\Srv\Service;

use CherezWeb\Hosting\Srv\ServiceAbstract;

class FtpManager extends ServiceAbstract {

    public function create($allocationName, $ftpName, $ftpPassword, $ftpDirPath) {
        $allocationManager = $this->getServiceContainer()->getAllocationManager();
        
        $ftpDirAbsolutePath = $allocationManager->getAllocationUserDataPath($allocationName) . DIRECTORY_SEPARATOR . $ftpDirPath;
        
        if (!file_exists($ftpDirAbsolutePath)) {
            $this->getServiceContainer()->getShell()->exec(
                'mkdir -m 700 -p %s',
                array (
                    $ftpDirAbsolutePath,
                )
            );
            $this->getServiceContainer()->getShell()->exec(
                'chown %1$s:%1$s -R %2$s',
                array (
                    $allocationName,
                    $allocationManager->getAllocationUserDataPath($allocationName),
                )
            );
        }
        
        $systemUserInfo = posix_getpwnam($allocationName);
        
        $this->getPdo()->exec("
            REPLACE INTO
                `ftpuser` (`userid`, `uid`,`gid`,`homedir`,`shell`)
            VALUES
                ('{$ftpName}','{$systemUserInfo['uid']}','{$systemUserInfo['gid']}','{$ftpDirAbsolutePath}','/bin/false')
        ");
                
        $this->changeFtpUserPassword($ftpName, $ftpPassword);
        
        return TRUE;
    }

    public function delete($ftpName) {
        $this->closeActiveConnections($ftpName);
        
        $this->getPdo()->exec("
            DELETE FROM
                `ftpuser`
            WHERE
                `userid` = '{$ftpName}'
        ");
        
        // На случай, если удалось все же быстро переконектиться.
        $this->closeActiveConnections($ftpName);
                
        return TRUE;
    }

    public function changeFtpUserPassword($ftpName, $ftpPassword) {
        $this->closeActiveConnections($ftpName);
        
        $ftpPasswordEncoded = $this->getServiceContainer()->getShell()->exec(
            '/bin/echo "{md5}"`/bin/echo -n "%s" | openssl dgst -binary -md5 | openssl enc -base64`',
            array (
                $ftpPassword
            )
        );
        $this->getPdo()->exec("
            UPDATE
                `ftpuser`
            SET
                `passwd` = '{$ftpPasswordEncoded}'
            WHERE
                `userid` = '{$ftpName}'
        ");
        
        // На случай, если удалось все же быстро переконектиться.
        $this->closeActiveConnections($ftpName);

        return TRUE;
    }
    
    private function closeActiveConnections($ftpName) {
        $hasActiveConnections = TRUE;
        try {
            $this->getServiceContainer()->getShell()->exec(
                'ftpwho | grep %1$s',
                array (
                    $ftpName
                )
            );
        } catch (\Exception $exc) {
            if ($this->getServiceContainer()->getShell()->getAgent()->getLastStatus() !== 1) {
                throw $exc;
            }
            $hasActiveConnections = FALSE;
        }

        if ($hasActiveConnections) {
            $this->getServiceContainer()->getShell()->exec(
                'ftpwho | grep %1$s | sed \'s/ .*//g\' | xargs kill -TERM',
                array (
                    $ftpName
                )
            );
        }
    }
    
    public function changeFtpDirPath($allocationName, $ftpName, $ftpDirPath) {
        $this->closeActiveConnections($ftpName);
        
        $row = $this->getPdo()->query("
            SELECT
                `passwd`
            FROM
                `ftpuser`
            WHERE
                `userid` = '{$ftpName}'
        ")->fetch(\PDO::FETCH_ASSOC);
                
        if ($row === FALSE) {
            $ftpPasswordEncoded = '';
        } else {
            $ftpPasswordEncoded = $row['passwd'];
        }
        
        $this->delete($ftpName);
        $this->create($allocationName, $ftpName, '', $ftpDirPath);
        $this->getPdo()->exec("
            UPDATE
                `ftpuser`
            SET
                `passwd` = '{$ftpPasswordEncoded}'
            WHERE
                `userid` = '{$ftpName}'
        ");
        
        // На случай, если удалось все же быстро переконектиться.
        $this->closeActiveConnections($ftpName);
        
        return TRUE;
    }
    
    private $pdo;

    protected function getPdo() {
        if (!$this->pdo) {
            $parameters = $this->getServiceContainer()->getParameters();

            $this->pdo = new \PDO(
                "mysql:host={$parameters->get('mysql.host')};dbname={$parameters->get('proftpd.mysql.database')}",
                $parameters->get('proftpd.mysql.login'),
                $parameters->get('proftpd.mysql.password'),
                array(
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                )
            );
        }
        return $this->pdo;
    }

}
