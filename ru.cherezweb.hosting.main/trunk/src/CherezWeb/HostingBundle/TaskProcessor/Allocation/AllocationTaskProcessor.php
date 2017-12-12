<?php

namespace CherezWeb\HostingBundle\TaskProcessor\Allocation;

use CherezWeb\HostingBundle\TaskProcessor\TaskProcessor;
use CherezWeb\HostingBundle\Entity\Task;
use CherezWeb\HostingBundle\Entity\Allocation;
use CherezWeb\HostingBundle\Entity\User;

abstract class AllocationTaskProcessor extends TaskProcessor {
    
    /**
     * @param Task $task
     * @return Allocation
     */
    protected function loadAllocation(Task $task) {
        $entity = $this->getDoctrine()->getRepository('CherezWebHostingBundle:Allocation')
            ->findOneByTask($task);
        if ($entity === NULL) {
            throw new \Exception('Отсутствует entity.');
        } elseif ($entity->getId() != $task->getParameter('allocationId')) {
            throw new \Exception(sprintf('Идентификатор entity #%s отличается от ожидаемого.', $entity->getId()));
        }
        return $entity;
    }
    
    protected function generatePassword() {
        return User::generatePassword();
    }
    
}