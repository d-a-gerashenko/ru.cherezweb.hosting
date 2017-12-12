<?php

namespace CherezWeb\HostingBundle\TaskProcessor\Ftp;

use CherezWeb\HostingBundle\TaskProcessor\TaskProcessor;
use CherezWeb\HostingBundle\Entity\Task;
use CherezWeb\HostingBundle\Entity\Ftp;
use CherezWeb\HostingBundle\Entity\User;

abstract class FtpTaskProcessor extends TaskProcessor {
    
    /**
     * @param Task $task
     * @return Ftp
     */
    protected function loadFtp(Task $task) {
        $entity = $this->getDoctrine()->getRepository('CherezWebHostingBundle:Ftp')
            ->findOneByTask($task);
        if ($entity === NULL) {
            throw new \Exception('Отсутствует entity.');
        } elseif ($entity->getId() != $task->getParameter('ftpId')) {
            throw new \Exception(sprintf('Идентификатор entity #%s отличается от ожидаемого.', $entity->getId()));
        }
        return $entity;
    }
    
    protected function generatePassword() {
        return User::generatePassword();
    }
    
}