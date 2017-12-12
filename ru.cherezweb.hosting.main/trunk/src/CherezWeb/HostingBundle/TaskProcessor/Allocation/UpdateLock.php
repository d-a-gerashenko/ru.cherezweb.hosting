<?php

namespace CherezWeb\HostingBundle\TaskProcessor\Allocation;

use CherezWeb\HostingBundle\Entity\Task;
use CherezWeb\HostingBundle\Service\ServerCommander;
use CherezWeb\HostingBundle\Entity\Domain;

class UpdateLock extends AllocationTaskProcessor {
    
    protected function processAction(Task $task) {
        $allocation = $this->loadAllocation($task);
        
        $domains = $this->getDoctrine()->getRepository('CherezWebHostingBundle:Domain')
            ->findByAllocation($allocation);
        
        $allocationIsLocked = $allocation->getIsLocked();
        $command = ($allocationIsLocked)?ServerCommander::COMMAND_DOMAIN_DISABLE:ServerCommander::COMMAND_DOMAIN_ENABLE;
        foreach ($domains as $domain) {
            /* @var $domain Domain */
            $result = $this->executeCommand(
                $task,
                $command,
                array(
                    'domainId' => $domain->getId(),
                )
            );
            if ($result !== TRUE) {
                $this->handleUnexpectedCommandResult($result, sprintf('Ошибка обновления блокировки (%s) Domain #%s.', $command, $domain->getId()));
            }
        }
        

        $task->setCompleted(new \DateTime());
        $task->setState(Task::STATE_COMPLETED);
        $task->setResult(Task::RESULT_SUCCESS);
        $allocation->setTask(NULL);
        $this->getDoctrine()->getManager()->flush();
        
        $this->get('cherez_web.default.mailer')->sendMail(
            sprintf('Обновление блокировки площадки "%s"', $allocation->getName()),
            $allocation->getUser()->getEmail(),
            'CherezWebHostingBundle:TaskProcessorEmail:allocation_update_lock',
            array(
                'allocationName' => $allocation->getName(),
                'allocationIsLocked' => $allocationIsLocked,
            )
        );
    }
    
    public function getType() {
        return Task::TYPE_ALLOCATION_UPDATE_LOCK;
    }
    
}