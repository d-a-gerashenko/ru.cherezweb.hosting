<?php

namespace CherezWeb\HostingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="chwh_ftp")
 * @ORM\Entity(repositoryClass="CherezWeb\HostingBundle\Repository\FtpRepository")
 */
class Ftp extends AllocationObjectAbstract {
    
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
    
    /**
     * @var string Путь до папки от корня Allocation, начинается со слеша, кончается НЕ слешем.
     * @ORM\Column(type="string", length=150)
     */
    private $dirPath;

    /**
     * @param string $dirPath Путь до папки от корня Allocation, начинается со слеша, кончается НЕ слешем.
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
    
    //--------------------------------------------------------------------------
    
    public function getName() {
        if ($this->getId() === NULL) {
            throw new \Exception('Пустой ID');
        }
        return 'alftp_'.$this->getId();
    }
}
