<?php

namespace CherezWeb\HostingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="chwh_tutorial")
 * @ORM\Entity(repositoryClass="CherezWeb\HostingBundle\Repository\TutorialRepository")
  */
class Tutorial {
    
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
    
    //--------------------------------------------------------------------------

    /**
     * @ORM\Column(type="text")
     */
    private $content;

    public function setContent($content) {
        $this->content = $content;
    }

    public function getContent() {
        return $this->content;
    }
    
    //--------------------------------------------------------------------------

    /**
     * @ORM\Column(type="text")
     */
    private $description;

    public function setDescription($description) {
        $this->description = $description;
    }

    public function getDescription() {
        return $this->description;
    }
    
    //--------------------------------------------------------------------------
    
    /**
     * @ORM\Column(type="string", length=1000)
     */
    private $title;

    public function setTitle($title) {
        $this->title = $title;
    }

    public function getTitle() {
        return $this->title;
    }
    
}
