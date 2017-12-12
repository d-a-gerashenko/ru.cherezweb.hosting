<?php

namespace CherezWeb\HostingBundle\Security\Authorization\Voter;

use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class DomainBaseVoter implements VoterInterface {

    const EDIT = 'edit';

    public function supportsAttribute($attribute) {
        return in_array($attribute, array(
            self::EDIT,
        ));
    }

    public function supportsClass($class) {
        $supportedClass = 'CherezWeb\HostingBundle\Entity\DomainBase';

        return $supportedClass === $class || is_subclass_of($class, $supportedClass);
    }

    /**
     * @var \CherezWeb\HostingBundle\Entity\DomainBase $domainBase
     */
    public function vote(TokenInterface $token, $domainBase, array $attributes) {
        /* @var $domainBase \CherezWeb\HostingBundle\Entity\DomainBase */
        
        // check if class of this object is supported by this voter
        if (!$this->supportsClass(get_class($domainBase))) {
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

        switch ($attribute) {
            case self::EDIT:
                // we assume that our data object has a method getOwner() to
                // get the current owner user entity for this data object
                if ($user->getId() === $domainBase->getUser()->getId()) {
                    return VoterInterface::ACCESS_GRANTED;
                }
                break;
        }

        return VoterInterface::ACCESS_DENIED;
    }

}
