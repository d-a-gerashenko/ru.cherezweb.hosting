<?php

namespace CherezWeb\HostingBundle\Repository;

use Doctrine\ORM\EntityRepository;
use CherezWeb\HostingBundle\Entity\Allocation;
use CherezWeb\HostingBundle\Entity\AllocationLock;

class AllocationLockRepository extends EntityRepository {

    /**
     * 
     * @param Allocation $allocation
     * @param string $type
     * @return AllocationLock
     */
    public function findOneByAllocationAndType(Allocation $allocation, $type) {
        if (!in_array($type, AllocationLock::getTypeVariants())) {
            throw new \Exception(sprintf('Неправильный формат $type: %s.', $type));
        }
        return $this->findOneBy(array('allocation' => $allocation, 'type' => $type));
    }

}
