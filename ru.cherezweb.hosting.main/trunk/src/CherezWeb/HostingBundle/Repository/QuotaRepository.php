<?php

namespace CherezWeb\HostingBundle\Repository;

use Doctrine\ORM\EntityRepository;
use CherezWeb\HostingBundle\Entity\Plan;
use CherezWeb\HostingBundle\Entity\Quota;

class QuotaRepository extends EntityRepository {

    /**
     * Подбираем наиболее заполненную квоту, но не полную.
     * Чтобы освободиться от какого-то сервера, можно проставить size всех его
     * квот в ноль.
     * @param Plan $plan
     * @return Quota
     */
    public function findQuotaForNewAllocation(Plan $plan) {
        return $this->createQueryBuilder('q')
            ->select('q, q.size - count(a.id) AS HIDDEN available')
            ->leftJoin('q.allocations', 'a')
            ->where('q.plan = :plan')
            ->setParameter('plan', $plan)
            ->groupBy('q')
            ->orderBy('available', 'ASC')
            ->having('available > 0')
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult();
    }

}
