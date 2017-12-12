<?php

namespace CherezWeb\HostingBundle\TaskProcessor\Database;

use CherezWeb\HostingBundle\Entity\Task;
use CherezWeb\HostingBundle\Service\ServerCommander;

class UpdatePassword extends DatabaseTaskProcessor {
    
    protected function processAction(Task $task) {
        $database = $this->loadDatabase($task);

        $password = $this->generatePassword();
        
        $result = $this->executeCommand(
            $task,
            ServerCommander::COMMAND_DATABASE_CHANGE_PASSWORD,
            array(
                'databaseName' => $database->getName(),
                'databasePassword' => $password,
            )
        );
        if ($result !== TRUE) {
            $this->handleUnexpectedCommandResult($result);
        }

        $task->setCompleted(new \DateTime());
        $task->setState(Task::STATE_COMPLETED);
        $task->setResult(Task::RESULT_SUCCESS);
        $database->setTask(NULL);
        $this->getDoctrine()->getManager()->flush();
        
        $this->get('cherez_web.default.mailer')->sendMail(
            sprintf('Изменен пароль базы "%s" на площадке "%s"', $database->getName(), $database->getAllocation()->getName()),
            $database->getAllocation()->getUser()->getEmail(),
            'CherezWebHostingBundle:TaskProcessorEmail:database_update_password',
            array(
                'allocationName' => $database->getAllocation()->getName(),
                'bdHost' => $database->getHost(),
                'bdName' => $database->getName(),
                'bdLogin' => $database->getLogin(),
                'bdPassword' => $password,
            )
        );
    }
    
    public function getType() {
        return Task::TYPE_DATABASE_UPDATE_PASSWORD;
    }
    
}