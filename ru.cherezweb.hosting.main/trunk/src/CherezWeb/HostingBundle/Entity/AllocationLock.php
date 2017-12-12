<?php

namespace CherezWeb\HostingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="chwh_allocation_lock")
 * @ORM\Entity(repositoryClass="CherezWeb\HostingBundle\Repository\AllocationLockRepository")
 */
class AllocationLock {
    
    public function __construct() {
        $this->created = new \DateTime();
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
     * @var Allocation
     * @ORM\ManyToOne(targetEntity="Allocation", inversedBy="locks")
     * @ORM\JoinColumn(nullable=false)
     */
    private $allocation;
    
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

    const TYPE_NO_PAYMENT = 'no_payment';
    const TYPE_DEPRECATED_CONTENT = 'deprecated_content';
    const TYPE_OVERLOAD = 'overload';
    
    static function getTypeVariants() {
        $prefix = 'TYPE_';
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
     * @ORM\Column(type="string", columnDefinition="ENUM('no_payment', 'deprecated_content', 'overload')")
     */
    protected $type;

    public function getType() {
        return $this->type;
    }

    public function setType($type) {
        if (!in_array($type, self::getTypeVariants())) {
            throw new \Exception(sprintf('Неправильный формат $type: %s.', $type));
        }
        $this->type = $type;
    }
    
    //--------------------------------------------------------------------------
    
    /**
     * @ORM\Column(type="string", length=300, nullable=true)
     */
    protected $description;

    public function getDescription() {
        return $this->description;
    }

    public function setDescription($description) {
        $this->description = $description;
    }
    
    //--------------------------------------------------------------------------

    /**
     * utcdatetime
     * @var \DateTime
     * @ORM\Column(type="utcdatetime")
     */
    protected $created;

	public function setCreated(\DateTime $created) {
		$this->created = $created;
	}

    /**
	 * @return \DateTime utcdatetime
	 */
	public function getCreated() {
        if ($this->created !== NULL) {
            $this->created->setTimeZone(new \DateTimeZone(date_default_timezone_get()));
        }
		return $this->created;
	}
    
}
