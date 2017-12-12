<?php

namespace CherezWeb\Hosting\Srv\Service;

use CherezWeb\Hosting\Srv\ServiceAbstract;

class AllocationManager extends ServiceAbstract {

    public function create($allocationName, $allocationSize, $allocationPassword) {
        $this->getServiceContainer()->getShell()->exec(
            'mkdir -m 755 %s',
            array ($this->getAllocationPath($allocationName))
        );
        $this->getServiceContainer()->getShell()->exec(
            'mkdir -m 700 %s',
            array ($this->getAllocationUserDataPath($allocationName))
        );
        
        $this->getServiceContainer()->getShell()->exec(
            'groupadd %s',
            array ($allocationName)
        );
        $this->getServiceContainer()->getShell()->exec(
            'useradd -g %1$s -s /bin/bash -d %2$s %1$s',
            array (
                $allocationName,
                $this->getAllocationUserDataPath($allocationName),
            )
        );
        
        $this->changePassword($allocationName, $allocationPassword);
        
        $this->getServiceContainer()->getShell()->exec(
            'chown %1$s:%1$s -R %2$s',
            array (
                $allocationName,
                $this->getAllocationUserDataPath($allocationName),
            )
        );
        $this->getServiceContainer()->getShell()->exec(
            'quotatool -g %1$s -b -l %2$s /',
            array (
                $allocationName,
                /*
                 * Переводим размер в байтах в блоки. В большинстве случае размер
                 * блока равен 1024, вот несколько вариантов как можно посмотреть
                 * размер блока:
                 * cat /usr/include/x86_64-linux-gnu/sys/mount.h | grep BLOCK_SIZE
                 * или
                 * dumpe2fs /dev/sdb3 | grep -i 'Block size'
                 */
                (int)($allocationSize / 1024),
            )
        );
        
        return TRUE;
    }
    
    public function changePassword($allocationName, $allocationPassword) {
        $this->killActiveProcesses($allocationName);
        
        $this->getServiceContainer()->getShell()->exec(
            'echo "%1$s\n%1$s" | passwd %2$s',
            array (
                $allocationPassword,
                $allocationName,
            )
        );
        
        // На случай, если удалось все же быстро переконектиться.
        $this->killActiveProcesses($allocationName);
        
        return TRUE;
    }
    
    private function getUid($allocationName) {
        $systemUserInfo = posix_getpwnam($allocationName);
        return $systemUserInfo['uid'];
    }


    private function killActiveProcesses($uid) {
        try {
            $this->getServiceContainer()->getShell()->exec(
                'pkill -u %1$s',
                array (
                    $uid,
                )
            );
        } catch (\Exception $exc) {
            if ($this->getServiceContainer()->getShell()->getAgent()->getLastStatus() !== 1) {
                throw $exc;
            }
        }

    }

    public function delete($allocationName) {
        $this->killActiveProcesses($allocationName);
        $uid = $this->getUid($allocationName);
        
        $this->getServiceContainer()->getShell()->exec(
            'quotatool -g %1$s -b -l 0 /',
            array (
                $allocationName,
            )
        );
        
        $this->getServiceContainer()->getShell()->exec(
            'userdel %1$s',
            array (
                $allocationName,
            )
        );
        // Группу удалять не надо, она удаляется вместе с пользователем.
        
        $this->getServiceContainer()->getShell()->exec(
            'rm -rf %1$s',
            array (
                $this->getAllocationPath($allocationName),
            )
        );
        
        // На случай, если удалось все же быстро переконектиться.
        $this->killActiveProcesses($uid);
        
        // Чистка всех файлов, которые пользователь мог оставить в системе.
        try {
            $this->getServiceContainer()->getShell()->exec(
                'find / -group %1$s -exec rm -fr {} \;',
                array (
                    $uid,
                )
            );
        } catch (\Exception $exc) {}


        return TRUE;
    }
    
    public function getAllocationPath($allocationName) {
        $parameters = $this->getServiceContainer()->getParameters();
        return $parameters->get('allocation.root_dir') . DIRECTORY_SEPARATOR . $allocationName;
    }
    
    public function getAllocationUserDataPath($allocationName) {
        $parameters = $this->getServiceContainer()->getParameters();
        return $this->getAllocationPath($allocationName) . DIRECTORY_SEPARATOR . $parameters->get('allocation.user_data_subdir');
    }
    
}
