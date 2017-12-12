<?php

namespace CherezWeb\Hosting\Srv\Service;

use CherezWeb\Hosting\Srv\ServiceAbstract;

class DomainManager extends ServiceAbstract {
    public function create($allocationName, $domainId, $domainName, $domainDirPath) {
        $parameters = $this->getServiceContainer()->getParameters();

        $hostFileContentTemplate = file_get_contents($parameters->get('apache.host_file_template'));
        
        $documentRootPath = $this->getDocumentRootDir($allocationName, $domainDirPath);
        $hostFileContent = str_replace('%DOCUMENT_ROOT_PATH%', $documentRootPath, $hostFileContentTemplate);
        
        $hostFileContent = str_replace('%DOMAIN%', $domainName, $hostFileContent);
        
        $hostFileContent = str_replace('%SYSTEM_USER%', $allocationName, $hostFileContent);
        $hostFileContent = str_replace('%SYSTEM_GROUP%', $allocationName, $hostFileContent);
        
        file_put_contents($this->getHostConfPath($domainId), $hostFileContent);
        $this->getServiceContainer()->getShell()->exec(
            'chmod 644 %s',
            array (
                $this->getHostConfPath($domainId),
            )
        );
        
        $this->getServiceContainer()->getShell()->exec('service apache2 reload');
        
        return TRUE;
    }
    public function delete($domainId) {
        $this->getServiceContainer()->getShell()->exec(
            'rm %s',
            array (
                $this->getHostConfPath($domainId),
            )
        );
        $this->getServiceContainer()->getShell()->exec('service apache2 reload');
        
        return TRUE;
    }
    
    public function disable($domainId) {
        $parameters = $this->getServiceContainer()->getParameters();
        
        $hostFileContent = file_get_contents($this->getHostConfPath($domainId));
        $hostDisabledDocRoot = realpath($parameters->get('apache.host_disabled_doc_root'));
        $hostFileContent = str_replace('DocumentRoot', "DocumentRoot {$hostDisabledDocRoot}\n" . '#DocumentRoot', $hostFileContent);
        
        file_put_contents($this->getHostConfPath($domainId), $hostFileContent);
        
        $this->getServiceContainer()->getShell()->exec('service apache2 reload');
        
        return TRUE;
    }
    public function enable($domainId) {
        $parameters = $this->getServiceContainer()->getParameters();
        
        $hostFileContent = file_get_contents($this->getHostConfPath($domainId));
        $hostFileContent = preg_replace("/DocumentRoot.*?#DocumentRoot/s", 'DocumentRoot', $hostFileContent, 1);
        
        file_put_contents($this->getHostConfPath($domainId), $hostFileContent);
        
        $this->getServiceContainer()->getShell()->exec('service apache2 reload');
        
        return TRUE;
    }
    
    public function getHostConfPath($domainId) {
        $apacheSitesEnabledDir = $this->getServiceContainer()
            ->getParameters()
            ->get('apache.sites_enabled_dir');
        
        if (!file_exists($apacheSitesEnabledDir)) {
            $this->getServiceContainer()->getShell()->exec(
                'mkdir -m 755 -p %s',
                array (
                    $apacheSitesEnabledDir,
                )
            );
        }
        return $apacheSitesEnabledDir . DIRECTORY_SEPARATOR . $domainId;
    }
    
    public function getDocumentRootDir($allocationName, $domainDirPath) {
        $allocationManager = $this->getServiceContainer()->getAllocationManager();
        
        $documentRootDir = $allocationManager->getAllocationUserDataPath($allocationName) . $domainDirPath;

        if (!file_exists($documentRootDir)) {
            $this->getServiceContainer()->getShell()->exec(
                'mkdir -m 700 -p %s',
                array (
                    $documentRootDir,
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
        return $documentRootDir;
    }
}