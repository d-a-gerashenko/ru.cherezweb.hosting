<?php

namespace CherezWeb\HostingBundle\Repository;

use Doctrine\ORM\EntityRepository;
use CherezWeb\HostingBundle\Entity\Task;
use CherezWeb\HostingBundle\Entity\Database;
use CherezWeb\HostingBundle\Entity\Allocation;

class DatabaseRepository extends EntityRepository {

    /**
     * @param Task $task
     * @return Database
     */
    public function findOneByTask(Task $task) {
        return $this->findOneBy(array('task' => $task));
    }
    
    /**
     * @param Allocation $allocation
     * @return array
     */
    public function findByAllocation(Allocation $allocation) {
        return $this->findBy(array('allocation' => $allocation));
    }

}
