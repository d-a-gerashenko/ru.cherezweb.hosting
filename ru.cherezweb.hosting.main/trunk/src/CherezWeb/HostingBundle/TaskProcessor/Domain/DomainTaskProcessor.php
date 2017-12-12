<?php

namespace CherezWeb\HostingBundle\TaskProcessor\Domain;

use CherezWeb\HostingBundle\TaskProcessor\TaskProcessor;
use CherezWeb\HostingBundle\Entity\Task;
use CherezWeb\HostingBundle\Entity\Domain;

abstract class DomainTaskProcessor extends TaskProcessor {
    
    /**
     * @param Task $task
     * @return Domain
     */
    protected function loadDomain(Task $task) {
        $entity = $this->getDoctrine()->getRepository('CherezWebHostingBundle:Domain')
            ->findOneByTask($task);
        if ($entity === NULL) {
            throw new \Exception('Отсутствует entity.');
        } elseif ($entity->getId() != $task->getParameter('domainId')) {
            throw new \Exception(sprintf('Идентификатор entity #%s отличается от ожидаемого.', $entity->getId()));
        }
        return $entity;
    }
    
}