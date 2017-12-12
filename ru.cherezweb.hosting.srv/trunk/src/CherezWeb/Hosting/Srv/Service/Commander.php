<?php

namespace CherezWeb\Hosting\Srv\Service;

use CherezWeb\Hosting\Srv\ServiceAbstract;

class Commander extends ServiceAbstract {

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
    
    public function execute($command, $parameters) {
        switch ($command) {
            case self::COMMAND_ALLOCATION_CREATE:
                return $this->getServiceContainer()->getAllocationManager()
                    ->create($parameters['allocationName'], $parameters['allocationSize'], $parameters['allocationPassword']);

            case self::COMMAND_ALLOCATION_DELETE:
                return $this->getServiceContainer()->getAllocationManager()
                    ->delete($parameters['allocationName']);
                
            case self::COMMAND_ALLOCATION_CHANGE_PASSWORD:
                return $this->getServiceContainer()->getAllocationManager()
                    ->changePassword($parameters['allocationName'], $parameters['allocationPassword']);

            case self::COMMAND_FTP_CREATE:
                return $this->getServiceContainer()->getFtpManager()
                    ->create($parameters['allocationName'], $parameters['ftpName'], $parameters['ftpPassword'], $parameters['ftpDirPath']);

            case self::COMMAND_FTP_DELETE:
                return $this->getServiceContainer()->getFtpManager()
                    ->delete($parameters['ftpName']);

            case self::COMMAND_FTP_CHANGE_PASSWORD:
                return $this->getServiceContainer()->getFtpManager()
                    ->changeFtpUserPassword($parameters['ftpName'], $parameters['ftpPassword']);

            case self::COMMAND_FTP_UPDATE_DIR_PATH:
                return $this->getServiceContainer()->getFtpManager()
                    ->changeFtpDirPath($parameters['allocationName'], $parameters['ftpName'], $parameters['ftpDirPath']);

            case self::COMMAND_DATABASE_CREATE:
                return $this->getServiceContainer()->getDataBaseManager()
                    ->create($parameters['allocationName'], $parameters['databaseName'], $parameters['databasePassword']);

            case self::COMMAND_DATABASE_DELETE:
                return $this->getServiceContainer()->getDataBaseManager()
                    ->delete($parameters['databaseName']);

            case self::COMMAND_DATABASE_CHANGE_PASSWORD:
                return $this->getServiceContainer()->getDataBaseManager()
                    ->changeDataBasePassword($parameters['databaseName'], $parameters['databasePassword']);

            case self::COMMAND_DOMAIN_CREATE:
                return $this->getServiceContainer()->getDomainManager()
                    ->create($parameters['allocationName'], $parameters['domainId'], $parameters['domainName'], $parameters['domainDirPath']);

            case self::COMMAND_DOMAIN_DELETE:
                return $this->getServiceContainer()->getDomainManager()
                    ->delete($parameters['domainId']);

            case self::COMMAND_DOMAIN_DISABLE:
                return $this->getServiceContainer()->getDomainManager()
                    ->disable($parameters['domainId']);

            case self::COMMAND_DOMAIN_ENABLE:
                return $this->getServiceContainer()->getDomainManager()
                    ->enable($parameters['domainId']);

            case self::COMMAND_JOB_CRATE:
                return $this->getServiceContainer()->getJobManager()
                    ->create($parameters['allocationName'], $parameters['jobId'], $parameters['jobSchedule'], $parameters['jobScriptPath']);

            case self::COMMAND_JOB_DELETE:
                return $this->getServiceContainer()->getJobManager()
                    ->remove($parameters['jobId']);

            default:
                throw new \Exception(sprintf('Неправильный тип таска: %s', $command));
        }
    }
    
}
