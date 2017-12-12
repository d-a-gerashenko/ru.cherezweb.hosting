<?php

namespace CherezWeb\Hosting\Dns;

class DnsRecord
{
    public static function createFromArray(array $params)
    {
        $keys = array('id', 'syncPos', 'updated', 'type', 'domainBaseName', 'host', 'value', 'priority', 'isDeleted');
        $unfoundKeys = array_diff($keys, array_keys($params));
        if (count($unfoundKeys)) {
            throw new \Exception(sprintf('Can\'t create DnsRecord, unfound keys: %s.', implode(',', $unfoundKeys)));
        }
        $newInstance = new DnsRecord();
        $newInstance->setId($params['id']);
        $newInstance->setSyncPos($params['syncPos']);
        $newInstance->setUpdated($params['updated']);
        $newInstance->setType($params['type']);
        $newInstance->setDomainBaseName($params['domainBaseName']);
        $newInstance->setHost($params['host']);
        $newInstance->setValue($params['value']);
        $newInstance->setPriority($params['priority']);
        $newInstance->setIsDeleted($params['isDeleted']);
        return $newInstance;
    }

    private $id;

    public function getId()
    {
        return $this->id;
    }
    
    public function setId($id)
    {
        $this->id = $id;
    }
    
    //--------------------------------------------------------------------------

    private $domainBaseName;

    public function setDomainBaseName($domainBaseName)
    {
        $this->domainBaseName = strtolower($domainBaseName);
    }

    public function getDomainBaseName()
    {
        return $this->domainBaseName;
    }
    
    //--------------------------------------------------------------------------

    private $isDeleted;

    public function setIsDeleted($isDeleted)
    {
        $this->isDeleted = $isDeleted;
    }

    public function getIsDeleted()
    {
        return $this->isDeleted;
    }
    
    //--------------------------------------------------------------------------

    private $host;

    public function setHost($host)
    {
        $this->host = $host;
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
    }
    
    //--------------------------------------------------------------------------

    private $value;

    public function setValue(array $value)
    {
        $this->value = self::normalizeValue($value, $this->getType());
    }

    public function getValue()
    {
        return $this->value;
    }
    
    public function getValueOption($option)
    {
        if (in_array($option, self::typeRequiredOptions($this->getType()))) {
            if ($this->value === null) {
                throw new \Exception('Dns record value isn\'t set.');
            }
            return $this->value[$option];
        }
        throw new \Exception(sprintf('Unexpected option "%s" for dns type "%s".', $option, $this->getType()));
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

    private $priority;

    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    public function getPriority()
    {
        return $this->priority;
    }
    //--------------------------------------------------------------------------

    private $syncPos;

    public function getSyncPos()
    {
        return $this->syncPos;
    }
    
    public function setSyncPos($syncPos)
    {
        $this->syncPos = $syncPos;
    }
    //--------------------------------------------------------------------------

    private $updated;

    public function getUpdated()
    {
        return $this->updated;
}
    
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }
}
