<?php

namespace CherezWeb\HostingBundle\Entity;

abstract class AllocationObjectAbstract extends TaskObjectAbstract {
    
//    /**
//     * @var Allocation
//     * @ORM\ManyToOne(targetEntity="Allocation")
//     * @ORM\JoinColumn(nullable=false)
//     */
//    protected $allocation;
    
    /**
     * @return Allocation
     */
    public function getAllocation() {
        return $this->allocation;
    }

    public function setAllocation(Allocation $allocation) {
        $this->allocation = $allocation;
    }
    
    //--------------------------------------------------------------------------
    
    public function getLockingTasks() {
        return array_merge(
            $this->getAllocation()->getLockingTasks(),
            parent::getLockingTasks()
        );
    }
    
}
