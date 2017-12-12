<?php

namespace CherezWeb\HostingBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * DNS записи существуют независимо от аккаунтов, и если домен переносится с
 * одного аккаунта на другой, то записи переходят вместе с доменом.
 * @ORM\Table(name="chwh_dns_record")
 * @ORM\Entity(repositoryClass="CherezWeb\HostingBundle\Repository\DnsRecordRepository")
 */
class DnsRecord
{

    public function __construct()
    {
        $this->updateSyncPos();
        $this->isDeleted = false;
        $this->restrictionLevel = 0;
        $this->isDeletable = true;
        $this->isEditable = true;
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
    public function getId()
    {
        return $this->id;
    }
    //--------------------------------------------------------------------------

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $domainBaseName;

    public function setDomainBaseName($domainBaseName)
    {
        $this->domainBaseName = $domainBaseName;
        $this->updateSyncPos();
    }

    public function getDomainBaseName()
    {
        return $this->domainBaseName;
    }
    //--------------------------------------------------------------------------

    /**
     * @ORM\Column(type="boolean")
     */
    private $isDeleted;

    public function setIsDeleted($isDeleted)
    {
        $this->isDeleted = $isDeleted;
        $this->updateSyncPos();
    }

    public function getIsDeleted()
    {
        return $this->isDeleted;
    }
    //--------------------------------------------------------------------------

    /**
     * 0 - no restrictions
     * 10 - can't delete but can edit
     * 20 - can't edit
     * @ORM\Column(type="integer")
     */
    private $restrictionLevel;

    public function setRestrictionLevel($restrictionLevel)
    {
        $this->restrictionLevel = $restrictionLevel;
        $this->updateSyncPos();
    }

    public function getRestrictionLevel()
    {
        return $this->restrictionLevel;
    }
    //--------------------------------------------------------------------------

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $host;

    public function setHost($host)
    {
        $this->host = $host;
        $this->updateSyncPos();
    }

    public function getHost()
    {
        return $this->host;
    }

    public function setHostInDomainStyle($hostInDomainStyle)
    {
        $this->setHost(self::hostFromDomainStyle($this->getDomainBaseName(), $hostInDomainStyle));
    }

    public function getHostInDomainStyle()
    {
        if ($this->getHost() === null) {
            return $this->getHost();
        }
        return self::hostToDomainStyle($this->getDomainBaseName(), $this->getHost());
    }

    public static function hostToDomainStyle($domainBaseName, $host)
    {
        if ($domainBaseName == '' || $host == '') {
            throw new \Exception('Неправильный формат данных.');
        }

        if ($host === '@') {
            return $domainBaseName;
        }
        return $host . '.' . $domainBaseName;
    }

    public static function hostFromDomainStyle($domainBaseName, $domain)
    {
        if ($domainBaseName == '' || $domain == '') {
            throw new \Exception('Неправильный формат данных.');
        }

        if ($domain === $domainBaseName) {
            return '@';
        }
        return str_replace('.' . $domainBaseName, '', $domain);
    }

    //--------------------------------------------------------------------------

    const TYPE_A = 'A';
    const TYPE_CNAME = 'CNAME';
    const TYPE_TXT = 'TXT';
    const TYPE_NS = 'NS';
    const TYPE_MX = 'MX';
    const TYPE_SOA = 'SOA';

    static function getTypeVariants()
    {
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
     * @ORM\Column(type="string", length=10)
     */
    protected $type;

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        if (!in_array($type, self::getTypeVariants())) {
            throw new \Exception(sprintf('Неправильный формат $type: %s.', $type));
        }
        $this->type = $type;
        $this->updateSyncPos();
    }
    //--------------------------------------------------------------------------

    /**
     * @ORM\Column(type="array")
     */
    private $value;

    public function setValue(array $value)
    {
        $this->value = self::normalizeValue($value, $this->getType());
        $this->updateSyncPos();
    }

    public function getValue()
    {
        return $this->value;
    }

    public static function normalizeValue(array $value, $type)
    {
        if (!in_array($type, self::getTypeVariants())) {
            throw new \Exception(sprintf('Неправильный формат $type: %s.', $type));
        }
        $normalizedValue = array();
        foreach (self::typeRequiredOptions($type) as $key) {
            if (!key_exists($key, $value)) {
                throw new \Exception(sprintf('Неправильный формат value для dns записи с типом %s, ключ %s не найден.', $type, $key));
            }
            $normalizedValue[$key] = trim((string) $value[$key]);
        }
        return $normalizedValue;
    }

    public static function typesRequiredOptions()
    {
        return array(
            self::TYPE_A => array('ip'),
            self::TYPE_CNAME => array('hostForAlias'),
            self::TYPE_MX => array('mailServer'),
            self::TYPE_NS => array('nsHost'),
            self::TYPE_SOA => array('nsMaster', 'email', 'refresh', 'retry', 'expire', 'ttl'),
            self::TYPE_TXT => array('txt')
        );
    }

    public static function typeRequiredOptions($type)
    {
        if (!in_array($type, self::getTypeVariants())) {
            throw new \Exception(sprintf('Неправильный формат $type: %s.', $type));
        }
        $typesRequiredOptions = self::typesRequiredOptions();
        return $typesRequiredOptions[$type];
    }
    //--------------------------------------------------------------------------

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $priority;

    public function setPriority($priority)
    {
        $this->priority = $priority;
        $this->updateSyncPos();
    }

    public function getPriority()
    {
        return $this->priority;
    }
    //--------------------------------------------------------------------------

    /**
     * @var AutoIncrement
     * @ORM\OneToOne(targetEntity="AutoIncrement", cascade={"persist","remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $syncPos;

    /**
     * @return AutoIncrement
     */
    public function getSyncPos()
    {
        return $this->syncPos;
    }

    /**
     * @return AutoIncrement
     */
    public function updateSyncPos()
    {
        $this->syncPos = new AutoIncrement;
    }
}
