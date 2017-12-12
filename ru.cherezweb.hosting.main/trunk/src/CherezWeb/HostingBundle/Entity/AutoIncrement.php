<?php

namespace CherezWeb\HostingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="chwh_auto_increment")
 * @ORM\Entity(repositoryClass="CherezWeb\HostingBundle\Repository\AutoIncrementRepository")
 */
class AutoIncrement {
    
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
