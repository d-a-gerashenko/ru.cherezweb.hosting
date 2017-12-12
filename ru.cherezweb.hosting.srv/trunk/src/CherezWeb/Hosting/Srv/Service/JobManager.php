<?php

namespace CherezWeb\Hosting\Srv\Service;

use CherezWeb\Hosting\Srv\ServiceAbstract;

class JobManager extends ServiceAbstract {

    public function create($allocationName, $jobId, $jobSchedule, $jobScriptPath) {
        $allocationManager = $this->getServiceContainer()->getAllocationManager();
        
        $docRootDir = $allocationManager->getAllocationUserDataPath($allocationName);
        $scriptPath = $docRootDir . $jobScriptPath;
        $scriptDirPath = dirname($scriptPath);
        
        $cronCommand = "{$jobSchedule} sudo -u {$allocationName} bash -c \"cd {$scriptDirPath} && php -f {$scriptPath}\"";
        
        $cronContent = $this->getCronContent();
        
        $cronContent .= PHP_EOL
            . "#job_{$jobId}{" . PHP_EOL
            . $cronCommand . PHP_EOL
            . "#}job";
        $this->setCronContent($cronContent);
        
        return TRUE;
    }

    public function remove($jobId) {
        $cronContent = $this->getCronContent();
        $cronContent = preg_replace("/(" . PHP_EOL . "?)#job_{$jobId}{.*?#}job/s", '', $cronContent);
        $this->setCronContent($cronContent);
        
        return TRUE;
    }
    
    protected function getCronContent() {
        try {
            $this->getServiceContainer()->getShell()->exec(
                'crontab -l > %s',
                array (
                    $this->getCronContentTempFile()
                )
            );
        } catch (\Exception $exc) {
            if ($this->getServiceContainer()->getShell()->getAgent()->getLastError() !== 'no crontab for root') {
                throw $exc;
            }
        }

        $cronContent = file_get_contents($this->getCronContentTempFile());
        $this->getServiceContainer()->getShell()->exec(
            'rm %s',
            array (
                $this->getCronContentTempFile()
            )
        );
        
        return $cronContent;
    }
    
    protected function setCronContent($cronContent) {
        file_put_contents($this->getCronContentTempFile(), $cronContent);
        $this->getServiceContainer()->getShell()->exec(
            'crontab %s',
            array (
                $this->getCronContentTempFile()
            )
        );
        $this->getServiceContainer()->getShell()->exec(
            'rm %s',
            array (
                $this->getCronContentTempFile()
            )
        );
    }
    
    protected function getCronContentTempFile() {
        return $this->getServiceContainer()->getParameters()->get('app.temp_dir') . DIRECTORY_SEPARATOR . 'cron_temp_content';
    }

}
