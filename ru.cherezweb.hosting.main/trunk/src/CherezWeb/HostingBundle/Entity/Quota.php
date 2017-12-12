<?php

namespace CherezWeb\HostingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Table(name="chwh_quota")
 * @ORM\Entity(repositoryClass="CherezWeb\HostingBundle\Repository\QuotaRepository")
 */
class Quota {
    
    public function __construct() {
        $this->allocations = new ArrayCollection();
        $this->size = 0;
        $this->sizeUsed = 0;
    }
    
    //--------------------------------------------------------------------------
    
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
     * @var Plan
     * @ORM\ManyToOne(targetEntity="Plan", inversedBy="quotas")
     * @ORM\JoinColumn(nullable=false)
     */
    private $plan;
    
    /**
     * @return Plan
     */
    public function getPlan() {
        return $this->plan;
    }

    public function setPlan(Plan $plan) {
        $this->plan = $plan;
    }
    
    //--------------------------------------------------------------------------
    
    /**
     * @var Server
     * @ORM\ManyToOne(targetEntity="Server", inversedBy="quotas")
     * @ORM\JoinColumn(nullable=false)
     */
    private $server;
    
    /**
     * @return Server
     */
    public function getServer() {
        return $this->server;
    }

    public function setServer(Server $server) {
        $this->server = $server;
    }
    
    //--------------------------------------------------------------------------
    
    /**
     * @var integer Размер квоты - число Allocation, для размещения на сервере.
     * @ORM\Column(type="integer")
     */
    protected $size;

    /**
     * Размер квоты - число Allocation, для размещения на сервере.
     * @return integer
     */
    public function getSize() {
        return $this->size;
    }

    /**
     * Размер квоты - число Allocation, для размещения на сервере.
     * @param integer $size
     */
    public function setSize($size) {
        $this->size = $size;
    }
    
    //--------------------------------------------------------------------------
    
    /**
     * @var ArrayCollection ArrayCollection of Allocation objects.
     * @ORM\OneToMany(targetEntity="Allocation", mappedBy="quota")
     */
    private $allocations;
    
    /**
     * @return ArrayCollection
     */
    public function getAllocations() {
        return $this->allocations;
    }
    
    //--------------------------------------------------------------------------
    
    /**
     * @ORM\Version
     * @ORM\Column(type="integer")
     */
    private $version;
    
    public function getVersion() {
        return $this->version;
    }
    
    // Ручное увеличение версии.
    /**
     * Срабатывает, когда значение отличается от загруженного из базы значения.
     * @ORM\Column(type="boolean", options={"default":true})
     */
    private $versionIncTrigger = FALSE;
    
    private $versionIncFlag = FALSE;
    
    public function getVersionIncFlag() {
        return $this->versionIncFlag;
    }

    public function setVersionIncFlag($versionIncFlag) {
        if ($this->versionIncFlag !== (bool)$versionIncFlag) {
            $this->versionIncTrigger = !$this->versionIncTrigger;
            $this->versionIncFlag = (bool)$versionIncFlag;
        }
    }
    
}
