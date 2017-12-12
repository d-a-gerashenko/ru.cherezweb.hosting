<?php

namespace CherezWeb\HostingBundle\TaskProcessor\Allocation;

use CherezWeb\HostingBundle\Entity\Task;
use CherezWeb\HostingBundle\Service\ServerCommander;

class Create extends AllocationTaskProcessor {
    
    protected function processAction(Task $task) {
        $allocation = $this->loadAllocation($task);
        
        $password = $this->generatePassword();
        
        $result = $this->executeCommand(
            $task,
            ServerCommander::COMMAND_ALLOCATION_CREATE,
            array(
                'allocationName' => $allocation->getName(),
                'allocationSize' => $allocation->getQuota()->getPlan()->getDiskQuota(),
                'allocationPassword' => $password,
            )
        );
        if ($result !== TRUE) {
            $this->handleUnexpectedCommandResult($result);
        }

        $task->setCompleted(new \DateTime());
        $task->setState(Task::STATE_COMPLETED);
        $task->setResult(Task::RESULT_SUCCESS);
        $allocation->setTask(NULL);
        $this->getDoctrine()->getManager()->flush();
        
        $this->get('cherez_web.default.mailer')->sendMail(
            sprintf('Создана площадка "%s"', $allocation->getName()),
            $allocation->getUser()->getEmail(),
            'CherezWebHostingBundle:TaskProcessorEmail:allocation_create',
            array(
                'allocationName' => $allocation->getName(),
                'allocationHost' => $allocation->getQuota()->getServer()->getIpAddress(),
                'allocationPassword' => $password,
            )
        );
    }
    
    public function getType() {
        return Task::TYPE_ALLOCATION_CREATE;
    }
    
}