<?php

namespace CherezWeb\HostingBundle\TaskProcessor\Job;

use CherezWeb\HostingBundle\TaskProcessor\TaskProcessor;
use CherezWeb\HostingBundle\Entity\Task;
use CherezWeb\HostingBundle\Entity\Job;

abstract class JobTaskProcessor extends TaskProcessor {
    
    /**
     * @param Task $task
     * @return Job
     */
    protected function loadJob(Task $task) {
        $entity = $this->getDoctrine()->getRepository('CherezWebHostingBundle:Job')
            ->findOneByTask($task);
        if ($entity === NULL) {
            throw new \Exception('Отсутствует entity.');
        } elseif ($entity->getId() != $task->getParameter('jobId')) {
            throw new \Exception(sprintf('Идентификатор entity #%s отличается от ожидаемого.', $entity->getId()));
        }
        return $entity;
    }
    
}