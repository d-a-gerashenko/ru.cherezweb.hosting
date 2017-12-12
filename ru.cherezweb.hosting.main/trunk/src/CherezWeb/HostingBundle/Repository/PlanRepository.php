<?php

namespace CherezWeb\HostingBundle\Repository;

use Doctrine\ORM\EntityRepository;

class PlanRepository extends EntityRepository {

    public function findActive() {
        return $this->findBy(array('isActive' => TRUE), array('order' => 'ASC'));
    }

}
