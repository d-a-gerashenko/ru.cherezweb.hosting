<?php

namespace CherezWeb\HostingBundle\TaskProcessor\Ftp;

use CherezWeb\HostingBundle\Entity\Task;
use CherezWeb\HostingBundle\Service\ServerCommander;

class UpdateDirPath extends FtpTaskProcessor {
    
    protected function processAction(Task $task) {
        $ftp = $this->loadFtp($task);
        
        $result = $this->executeCommand(
            $task,
            ServerCommander::COMMAND_FTP_UPDATE_DIR_PATH,
            array(
                'allocationName' => $ftp->getAllocation()->getName(),
                'ftpName' => $ftp->getName(),
                'ftpDirPath' => $ftp->getDirPath(),
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
            sprintf('Изменен путь ftp доступа "%s" на площадке "%s"', $ftp->getName(), $ftp->getAllocation()->getName()),
            $ftp->getAllocation()->getUser()->getEmail(),
            'CherezWebHostingBundle:TaskProcessorEmail:ftp_update_dir_path',
            array(
                'allocationName' => $ftp->getAllocation()->getName(),
                'ftpHost' => $ftp->getAllocation()->getQuota()->getServer()->getIpAddress(),
                'ftpLogin' => $ftp->getName(),
                'ftpDir' => $ftp->getDirPath(),
            )
        );
    }
    
    public function getType() {
        return Task::TYPE_FTP_UPDATE_DIR_PATH;
    }
    
}