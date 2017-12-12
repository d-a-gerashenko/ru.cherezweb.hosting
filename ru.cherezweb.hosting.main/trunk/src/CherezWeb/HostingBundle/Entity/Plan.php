<?php

namespace CherezWeb\HostingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Table(name="chwh_plan")
 * @ORM\Entity(repositoryClass="CherezWeb\HostingBundle\Repository\PlanRepository")
 */
class Plan {
    
    public function __construct() {
        $this->quotas = new ArrayCollection();
        $this->diskQuota = 0;
        $this->isActive = TRUE;
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
     * @var ArrayCollection ArrayCollection of Quota objects.
     * @ORM\OneToMany(targetEntity="Quota", mappedBy="plan")
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
     * Размер в байтах.
     * @ORM\Column(type="bigint")
     */
    protected $diskQuota;

    /**
     * 
     * @return integer Размер в байтах.
     */
    public function getDiskQuota() {
        return $this->diskQuota;
    }
    
    /**
     * @param integer $diskQuota Размер в байтах.
     */
    public function setDiskQuota($diskQuota) {
        $this->diskQuota = $diskQuota;
    }
    
    //--------------------------------------------------------------------------
    
    /**
     * @ORM\Column(type="boolean")
     */
    private $isActive;

    public function setIsActive($isActive) {
        $this->isActive = $isActive;
    }

    public function getIsActive() {
        return $this->isActive;
    }
    
    //--------------------------------------------------------------------------
    
    /**
     * Стоимость в копейках.
     * @ORM\Column(type="integer")
     */
    protected $price;

    /**
     * @param integer $price Стоимость в копейках.
     */
	public function setPrice($price) {
		$this->price = $price;
	}

    /**
     * @return integer Стоимость в копейках.
     */
	public function getPrice() {
		return $this->price;
	}
    
    //--------------------------------------------------------------------------
    
    /**
     * @ORM\Column(type="string", length=60)
     */
    private $title;

    public function setTitle($title) {
        $this->title = $title;
    }

    public function getTitle() {
        return $this->title;
    }
    
    //--------------------------------------------------------------------------
    
    /**
     * @ORM\Column(type="text")
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
     * @ORM\Column(name="_order", type="integer")
     */
    private $order;

    public function setOrder($order) {
        $this->order = $order;
    }

    public function getOrder() {
        return $this->order;
    }
}