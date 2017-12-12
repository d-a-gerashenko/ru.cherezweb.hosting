<?php

namespace CherezWeb\HostingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Table(name="chwh_allocation")
 * @ORM\Entity(repositoryClass="CherezWeb\HostingBundle\Repository\AllocationRepository")
 */
class Allocation extends TaskObjectAbstract {
    
    public function __construct() {
        $this->locks = new ArrayCollection();
        $this->created = new \DateTime();
        $this->daysLeftNotified = NULL;
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
     * @var Quota
     * @ORM\ManyToOne(targetEntity="Quota", inversedBy="allocations")
     * @ORM\JoinColumn(nullable=false)
     */
    private $quota;
    
    /**
     * @return Quota
     */
    public function getQuota() {
        return $this->quota;
    }

    public function setQuota(Quota $quota) {
        $this->quota = $quota;
    }
    
    //--------------------------------------------------------------------------
    
    /**
     * @var ArrayCollection ArrayCollection of Allocation objects.
     * @ORM\OneToMany(targetEntity="AllocationLock", mappedBy="allocation", cascade={"remove"})
     */
    private $locks;
    
    /**
     * @return ArrayCollection
     */
    public function getLocks() {
        return $this->locks;
    }
    
    /**
     * @return bool
     */
    public function getIsLocked() {
        return $this->getLocks()->count() > 0;
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
     * utcdatetime
     * @var \DateTime
     * @ORM\Column(type="utcdatetime")
     */
    protected $paidTill;

	public function setPaidTill(\DateTime $paidTill) {
		$this->paidTill = $paidTill;
        $this->daysLeftNotified = NULL;
	}

    /**
	 * @return \DateTime utcdatetime
	 */
	public function getPaidTill() {
        if ($this->paidTill !== NULL) {
            $this->paidTill->setTimeZone(new \DateTimeZone(date_default_timezone_get()));
        }
		return $this->paidTill;
	}
    
    //--------------------------------------------------------------------------
    
    public function getLockingTasks() {
        return array_merge(
            $this->getQuota()->getServer()->getLockingTasks(),
            parent::getLockingTasks()
        );
    }
    
    //--------------------------------------------------------------------------
    
    public function getName() {
        if ($this->getId() === NULL) {
            throw new \Exception('Пустой ID');
        }
        return 'al_'.$this->getId();
    }
    
    //--------------------------------------------------------------------------
    
    /**
     * Разница в днях между датой уведомления и окончанием оплаты, если
     * уведомление было после окончания оплаты, число будет отрицательным.
     * @ORM\Column(type="integer", nullable=true)
     */
    private $daysLeftNotified;
    
    public function getDaysLeftNotified() {
        return $this->daysLeftNotified;
    }

    public function setDaysLeftNotified($daysLeftNotified) {
        $this->daysLeftNotified = $daysLeftNotified;
    }
    
}
