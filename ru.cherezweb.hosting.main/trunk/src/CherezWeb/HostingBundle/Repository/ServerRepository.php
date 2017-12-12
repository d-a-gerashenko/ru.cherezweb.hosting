<?php

namespace CherezWeb\HostingBundle\Repository;

use Doctrine\ORM\EntityRepository;
use CherezWeb\HostingBundle\Entity\Task;
use CherezWeb\HostingBundle\Entity\Server;

class ServerRepository extends EntityRepository {

    /**
     * @param Task $task
     * @return Server
     */
    public function findOneByTask(Task $task) {
        return $this->findOneBy(array('task' => $task));
    }

}
