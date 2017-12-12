<?php

namespace CherezWeb\HostingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Table(name="chwh_server")
 * @ORM\Entity(repositoryClass="CherezWeb\HostingBundle\Repository\ServerRepository")
 */
class Server extends TaskObjectAbstract {
    
    public function __construct() {
        $this->tasks = new ArrayCollection();
        $this->quotas = new ArrayCollection();
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
     * @var Task
     * @ORM\OneToOne(targetEntity="Task")
     */
    protected $task;
    
    //--------------------------------------------------------------------------
    
    /**
     * @var ArrayCollection ArrayCollection of Quota objects.
     * @ORM\OneToMany(targetEntity="Quota", mappedBy="server")
     */
    private $quotas;
    
    /**
     * @return ArrayCollection
     */
    public function getQuotas() {
        return $this->quotas;
    }
    
    //--------------------------------------------------------------------------

    /**
     * @var string
     * @ORM\Column(type="string", length=50)
     */
    private $ipAddress;
    
    /**
     * @return string
     */
    public function getIpAddress() {
        return $this->ipAddress;
    }

    /**
     * @param string $ipAddress
     */
    public function setIpAddress($ipAddress) {
        $this->ipAddress = $ipAddress;
    }

    //--------------------------------------------------------------------------
    
    /**
     * @var string
     * @ORM\Column(type="string", length=50)
     */
    private $accessKey;
    
    /**
     * @return string
     */
    public function getAccessKey() {
        return $this->accessKey;
    }

    /**
     * @param string $accessKey
     */
    public function setAccessKey($accessKey) {
        $this->accessKey = $accessKey;
    }
    
    //--------------------------------------------------------------------------
    
    public function getLockingTasks() {
        return parent::getLockingTasks();
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
