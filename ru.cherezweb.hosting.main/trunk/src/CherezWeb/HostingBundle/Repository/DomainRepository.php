<?php

namespace CherezWeb\HostingBundle\Repository;

use Doctrine\ORM\EntityRepository;
use CherezWeb\HostingBundle\Entity\Task;
use CherezWeb\HostingBundle\Entity\Domain;
use CherezWeb\HostingBundle\Entity\Allocation;

class DomainRepository extends EntityRepository {

    /**
     * @param Task $task
     * @return Domain
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
