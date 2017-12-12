<?php

namespace CherezWeb\HostingBundle\Entity;

abstract class TaskObjectAbstract {
    
//    /**
//     * @var Task
//     * @ORM\OneToOne(targetEntity="Task")
//     */
//    protected $task;
    
    /**
     * @return Task
     */
    public function getTask() {
        return $this->task;
    }

    public function setTask(Task $task = NULL) {
        $this->task = $task;
    }
    
    //--------------------------------------------------------------------------
    
    /**
     * Возвращает блокирующие объект таски (Task).
     * @return array Array of locking object tasks.
     */
    public function getLockingTasks() {
        return $this->getTask() === NULL ? array() : array($this->getTask());
    }
    
}
