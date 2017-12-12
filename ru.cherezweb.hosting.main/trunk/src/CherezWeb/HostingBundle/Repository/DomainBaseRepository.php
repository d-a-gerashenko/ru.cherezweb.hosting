<?php

namespace CherezWeb\HostingBundle\Repository;

use Doctrine\ORM\EntityRepository;
use CherezWeb\HostingBundle\Entity\DomainBase;
use CherezWeb\HostingBundle\Entity\User;

class DomainBaseRepository extends EntityRepository {

    /**
     * Возвращает базовый домен для в состоянии active указанного пользователя,
     * если не находит, ищет по всем пользователям,
     * если упоминаний о таком домене не было, создает новый базовый
     * домен в активном состоянии для указанного пользователя
     * (первый заявил - занчит владелец).
     * @param User $user
     * @param string $domainName
     * @return DomainBase NULL - если домен уже занят.
     */
    public function findOrInitActive(User $user, $domainName) {
        $pieces = explode('.', $domainName);
        $domainBaseName = implode('.', array_slice($pieces, count($pieces) - 2));
        
        $domainBase = $this->findOneBy(array('user' => $user, 'name' => $domainBaseName, 'state' => DomainBase::STATE_ACTIVE));
        
        // Если домен встречается впервые, выдаем права на домен указанному пользователю.
        if ($domainBase === NULL && $this->findOneBy(array('name' => $domainBaseName)) === NULL) {
            $domainBase = new DomainBase();
            $this->getEntityManager()->persist($domainBase);
            $domainBase->setName($domainBaseName);
            $domainBase->setState(DomainBase::STATE_ACTIVE);
            $domainBase->setUser($user);
        }
        
        return $domainBase;
    }
    
    public function findByUser(User $user) {
        return $this->findBy(array('user' => $user), array('id' => 'ASC'));
    }
    
    /**
     * 
     * @param \CherezWeb\HostingBundle\Entity\DnsRecord $dnsRecord
     * @return DomainBase
     */
    public function findOneActiveByDnsRecord(\CherezWeb\HostingBundle\Entity\DnsRecord $dnsRecord) {
        return $this
            ->createQueryBuilder('db')
            ->where('db.name = :name')
            ->andWhere('db.state = :state')
            ->setParameter('name', $dnsRecord->getDomainBaseName())
            ->setParameter('state', DomainBase::STATE_ACTIVE)
            ->getQuery()
            ->getOneOrNullResult(); // There could be only one active base domain.
    }

}
