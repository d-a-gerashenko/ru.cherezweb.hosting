<?php

namespace CherezWeb\HostingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="chwh_domain")
 * @ORM\Entity(repositoryClass="CherezWeb\HostingBundle\Repository\DomainRepository")
 */
class Domain extends AllocationObjectAbstract {
    
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    public function getId() {
        return $this->id;
    }
    
    //--------------------------------------------------------------------------
    
    /**
     * @var DomainBase
     * @ORM\ManyToOne(targetEntity="DomainBase")
     * @ORM\JoinColumn(nullable=false)
     */
    private $domainBase;
    
    /**
     * @return DomainBase
     */
    public function getDomainBase() {
        return $this->domainBase;
    }

    public function setDomainBase(DomainBase $domainBase) {
        $this->domainBase = $domainBase;
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
    
    /**
     * @ORM\Column(type="string", length=100, unique=true)
     */
    private $name;

    public function setName($name) {
        $this->name = $name;
    }

    public function getName() {
        return $this->name;
    }
    
    //--------------------------------------------------------------------------
    
    /**
     * @var string Путь до папки от корня Allocation, начинается НЕ со слеша.
     * @ORM\Column(type="string", length=300)
     */
    private $dirPath;

    /**
     * @param string $dirPath Путь до папки от корня Allocation, начинается НЕ со слеша.
     */
    public function setDirPath($dirPath) {
        $this->dirPath = $dirPath;
    }

    /**
     * Путь до папки от корня Allocation, начинается НЕ со слеша.
     * @return string 
     */
    public function getDirPath() {
        return $this->dirPath;
    }
    
}
