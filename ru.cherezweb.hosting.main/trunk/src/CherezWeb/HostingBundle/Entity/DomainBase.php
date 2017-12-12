<?php

namespace CherezWeb\HostingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Table(name="chwh_domain_base")
 * @ORM\Entity(repositoryClass="CherezWeb\HostingBundle\Repository\DomainBaseRepository")
 */
class DomainBase {
    
    public function __construct() {
        $this->state = self::STATE_CONFIRMATION;
        $this->domains = new ArrayCollection();
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
     * @var User
     * @ORM\ManyToOne(targetEntity="User")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;
    
    /**
     * @return User
     */
    public function getUser() {
        return $this->user;
    }

    public function setUser(User $user) {
        $this->user = $user;
    }
    
    //--------------------------------------------------------------------------
    
    /**
     * @ORM\Column(type="string", length=50)
     */
    private $name;

    public function setName($name) {
        $this->name = $name;
    }

    public function getName() {
        return $this->name;
    }
    
    //--------------------------------------------------------------------------
    
    const STATE_CONFIRMATION = 'confirmation';
    const STATE_ACTIVATION = 'activation';
    const STATE_ACTIVE = 'active';
    const STATE_DEACTIVATION = 'deactivation';
    const STATE_INACTIVE = 'inactive';
    
    static function getStateVariants() {
        $prefix = 'STATE_';
        $variants = array();
        $refl = new \ReflectionClass(get_called_class());
        foreach ($refl->getConstants() as $name => $value) {
            if (strpos($name, $prefix) === 0) {
                $variants[] = $value;
            }
        }
        return $variants;
    }

    /**
     * @ORM\Column(type="string", length=100)
     */
    protected $state;

    public function getState() {
        return $this->state;
    }

    public function setState($state) {
        if (!in_array($state, self::getStateVariants())) {
            throw new \Exception(sprintf('Неправильный формат $state: %s.', $state));
        }
        $this->state = $state;
    }
    
    //--------------------------------------------------------------------------

    public function getConfirmationHost() {
        if ($this->getId() === NULL) {
            throw new \Exception('Пустой ID');
        }
        if ($this->getName() === NULL) {
            throw new \Exception('Пустой Name');
        }
        return "chwh-{$this->id}.{$this->getName()}";
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
