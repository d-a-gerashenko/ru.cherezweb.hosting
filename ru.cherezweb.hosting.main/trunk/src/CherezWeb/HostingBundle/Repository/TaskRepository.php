<?php

namespace CherezWeb\HostingBundle\Repository;

use Doctrine\ORM\EntityRepository;
use CherezWeb\HostingBundle\Entity\Server;
use CherezWeb\HostingBundle\Entity\Task;

class TaskRepository extends EntityRepository {
    
    /**
     * Возвращает следующий такс на выполнение из очереди тасков сервера.
     * @param Server $server
     * @return Task
     */
    public function findNextTask(Server $server) {
        return $this->createQueryBuilder('t')
            ->where('t.server = :server')
            ->andWhere('t.state != :state')
            ->setParameter('server', $server)
            ->setParameter('state', Task::STATE_COMPLETED)
            ->orderBy('t.id', 'ASC')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

}
