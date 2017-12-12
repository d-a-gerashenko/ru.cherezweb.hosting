<?php

namespace CherezWeb\HostingBundle\Security\Authorization\Voter;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class DnsRecordVoter implements VoterInterface
{

    const EDIT = 'edit';
    const DELETE = 'delete';

    /**
     *
     * @var EntityManager
     */
    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function supportsAttribute($attribute)
    {
        return in_array($attribute, array(
            self::EDIT,
            self::DELETE,
        ));
    }

    public function supportsClass($class)
    {
        $supportedClass = 'CherezWeb\HostingBundle\Entity\DnsRecord';

        return $supportedClass === $class || is_subclass_of($class, $supportedClass);
    }

    /**
     * @var \CherezWeb\HostingBundle\Entity\DnsRecord $dnsRecord
     */
    public function vote(TokenInterface $token, $dnsRecord, array $attributes)
    {
        /* @var $dnsRecord \CherezWeb\HostingBundle\Entity\DnsRecord */

        // check if class of this object is supported by this voter
        if (!$this->supportsClass(get_class($dnsRecord))) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        // check if the voter is used correct, only allow one attribute
        // this isn't a requirement, it's just one easy way for you to
        // design your voter
        if (1 !== count($attributes)) {
            throw new \InvalidArgumentException(
            'Only one attribute is allowed'
            );
        }

        // set the attribute to check against
        $attribute = $attributes[0];

        // check if the given attribute is covered by this voter
        if (!$this->supportsAttribute($attribute)) {
            return VoterInterface::ACCESS_ABSTAIN;
        }

        // get current logged in user
        $user = $token->getUser();

        // make sure there is a user object (i.e. that the user is logged in)
        if (!$user instanceof UserInterface) {
            return VoterInterface::ACCESS_DENIED;
        }

        // Start checking
        $repo = $this->entityManager->getRepository('CherezWebHostingBundle:DomainBase');
        /* @var $repo \CherezWeb\HostingBundle\Repository\DomainBaseRepository */

        $domainBase = $repo->findOneActiveByDnsRecord($dnsRecord);
        if ($domainBase !== null && $user->getId() === $domainBase->getUser()->getId()) {
            switch ($attribute) {
                case self::EDIT:
                    if ($dnsRecord->getRestrictionLevel() < 20) {
                        return VoterInterface::ACCESS_GRANTED;
                    }
                    break;
                case self::DELETE:
                    if ($dnsRecord->getRestrictionLevel() < 10) {
                        return VoterInterface::ACCESS_GRANTED;
                    }
                    break;
            }
        }

        return VoterInterface::ACCESS_DENIED;
    }
}
