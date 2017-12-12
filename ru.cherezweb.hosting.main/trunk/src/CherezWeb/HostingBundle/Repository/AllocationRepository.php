<?php

namespace CherezWeb\HostingBundle\Repository;

use Doctrine\ORM\EntityRepository;
use CherezWeb\HostingBundle\Entity\User;
use CherezWeb\HostingBundle\Entity\Task;
use CherezWeb\HostingBundle\Entity\Allocation;

class AllocationRepository extends EntityRepository {

    /**
     * @param Task $task
     * @return Allocation
     */
    public function findOneByTask(Task $task) {
        return $this->findOneBy(array('task' => $task));
    }
    
    public function findByUser(User $user) {
        return $this->findBy(array('user' => $user), array('id' => 'ASC'));
    }

}
