<?php

namespace CherezWeb\HostingBundle\TaskProcessor\Ftp;

use CherezWeb\HostingBundle\Entity\Task;
use CherezWeb\HostingBundle\Service\ServerCommander;

class UpdatePassword extends FtpTaskProcessor {
    
    protected function processAction(Task $task) {
        $ftp = $this->loadFtp($task);
        
        $password = $this->generatePassword();

        $result = $this->executeCommand(
            $task,
            ServerCommander::COMMAND_FTP_CHANGE_PASSWORD,
            array(
                'ftpName' => $ftp->getName(),
                'ftpPassword' => $password,
            )
        );
        if ($result !== TRUE) {
            $this->handleUnexpectedCommandResult($result);
        }

        $task->setCompleted(new \DateTime());
        $task->setState(Task::STATE_COMPLETED);
        $task->setResult(Task::RESULT_SUCCESS);
        $ftp->setTask(NULL);
        $this->getDoctrine()->getManager()->flush();
        
        $this->get('cherez_web.default.mailer')->sendMail(
            sprintf('Изменен пароль ftp доступа "%s" на площадке "%s"', $ftp->getName(), $ftp->getAllocation()->getName()),
            $ftp->getAllocation()->getUser()->getEmail(),
            'CherezWebHostingBundle:TaskProcessorEmail:ftp_update_password',
            array(
                'allocationName' => $ftp->getAllocation()->getName(),
                'ftpHost' => $ftp->getAllocation()->getQuota()->getServer()->getIpAddress(),
                'ftpLogin' => $ftp->getName(),
                'ftpPassword' => $password,
                'ftpDir' => $ftp->getDirPath(),
            )
        );
    }
    
    public function getType() {
        return Task::TYPE_FTP_UPDATE_PASSWORD;
    }
    
}