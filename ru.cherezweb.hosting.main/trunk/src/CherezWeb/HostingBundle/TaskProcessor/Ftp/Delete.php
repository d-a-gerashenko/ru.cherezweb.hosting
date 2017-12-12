<?php

namespace CherezWeb\HostingBundle\TaskProcessor\Ftp;

use CherezWeb\HostingBundle\Entity\Task;
use CherezWeb\HostingBundle\Service\ServerCommander;

class Delete extends FtpTaskProcessor {
    
    protected function processAction(Task $task) {
        $ftp = $this->loadFtp($task);

        $result = $this->executeCommand(
            $task,
            ServerCommander::COMMAND_FTP_DELETE,
            array(
                'ftpName' => $ftp->getName(),
            )
        );
        if ($result !== TRUE) {
            $this->handleUnexpectedCommandResult($result);
        }

        $task->setCompleted(new \DateTime());
        $task->setState(Task::STATE_COMPLETED);
        $task->setResult(Task::RESULT_SUCCESS);
        
        $ftpAllocation = $ftp->getAllocation();
        $ftpHost = $ftp->getAllocation()->getQuota()->getServer()->getIpAddress();
        $ftpLogin = $ftp->getName();
        $ftpDir = $ftp->getDirPath();
        
        $this->getDoctrine()->getManager()->remove($ftp);
        $this->getDoctrine()->getManager()->flush();
        
        $this->get('cherez_web.default.mailer')->sendMail(
            sprintf('Удален ftp доступ "%s" с площадки "%s"', $ftpLogin, $ftpAllocation->getName()),
            $ftpAllocation->getUser()->getEmail(),
            'CherezWebHostingBundle:TaskProcessorEmail:ftp_delete',
            array(
                'allocationName' => $ftpAllocation->getName(),
                'ftpHost' => $ftpHost,
                'ftpLogin' => $ftpLogin,
                'ftpDir' => $ftpDir,
            )
        );
    }
    
    public function getType() {
        return Task::TYPE_FTP_DELETE;
    }
    
}