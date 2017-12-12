<?php

namespace CherezWeb\HostingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="chwh_database")
 * @ORM\Entity(repositoryClass="CherezWeb\HostingBundle\Repository\DatabaseRepository")
 */
class Database extends AllocationObjectAbstract {
    
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @return integer 
     */
    public function getId() {
        return $this->id;
    }
    
    //--------------------------------------------------------------------------
    
    /**
     * @var Task
     * @ORM\OneToOne(targetEntity="Task")
     */
    protected $task;
    
    //--------------------------------------------------------------------------
    
    /**
     * @var Allocation
     * @ORM\ManyToOne(targetEntity="Allocation")
     * @ORM\JoinColumn(nullable=false)
     */
    protected $allocation;
    
    //--------------------------------------------------------------------------
    
    public function getHost() {
        return 'localhost';
    }
    
    //--------------------------------------------------------------------------
    
    public function getName() {
        if ($this->getId() === NULL) {
            throw new \Exception('Пустой ID');
        }
        return 'aldb_'.$this->getId();
    }
    
    //--------------------------------------------------------------------------
    
    public function getLogin() {
        return $this->getName();
    }
    
}
