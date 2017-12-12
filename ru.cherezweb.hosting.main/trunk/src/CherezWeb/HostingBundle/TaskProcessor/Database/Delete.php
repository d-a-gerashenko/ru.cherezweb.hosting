<?php

namespace CherezWeb\HostingBundle\TaskProcessor\Database;

use CherezWeb\HostingBundle\Entity\Task;
use CherezWeb\HostingBundle\Service\ServerCommander;

class Delete extends DatabaseTaskProcessor {
    
    protected function processAction(Task $task) {
        $database = $this->loadDatabase($task);
        $result = $this->executeCommand(
            $task,
            ServerCommander::COMMAND_DATABASE_DELETE,
            array(
                'databaseName' => $database->getName(),
            )
        );
        if ($result !== TRUE) {
            $this->handleUnexpectedCommandResult($result);
        }

        $task->setCompleted(new \DateTime());
        $task->setState(Task::STATE_COMPLETED);
        $task->setResult(Task::RESULT_SUCCESS);
        
        $databaseAllocation = $database->getAllocation();
        $databaseName = $database->getName();
        
        $this->getDoctrine()->getManager()->remove($database);
        $this->getDoctrine()->getManager()->flush();
        
        $this->get('cherez_web.default.mailer')->sendMail(
            sprintf('Удалена база "%s" на площадке "%s"', $databaseName, $databaseAllocation->getName()),
            $databaseAllocation->getUser()->getEmail(),
            'CherezWebHostingBundle:TaskProcessorEmail:database_delete',
            array(
                'allocationName' => $databaseAllocation->getName(),
                'bdName' => $databaseName,
            )
        );
    }
    
    public function getType() {
        return Task::TYPE_DATABASE_DELETE;
    }
    
}