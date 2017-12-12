<?php

namespace CherezWeb\HostingBundle\TaskProcessor\Database;

use CherezWeb\HostingBundle\TaskProcessor\TaskProcessor;
use CherezWeb\HostingBundle\Entity\Task;
use CherezWeb\HostingBundle\Entity\Database;
use CherezWeb\HostingBundle\Entity\User;

abstract class DatabaseTaskProcessor extends TaskProcessor {
    
    /**
     * @param Task $task
     * @return Database
     */
    protected function loadDatabase(Task $task) {
        $entity = $this->getDoctrine()->getRepository('CherezWebHostingBundle:Database')
            ->findOneByTask($task);
        if ($entity === NULL) {
            throw new \Exception('Отсутствует entity.');
        } elseif ($entity->getId() != $task->getParameter('databaseId')) {
            throw new \Exception(sprintf('Идентификатор entity #%s отличается от ожидаемого.', $entity->getId()));
        }
        return $entity;
    }
    
    protected function generatePassword() {
        return User::generatePassword();
    }
    
}