<?php

namespace CherezWeb\HostingBundle\Repository;

use Doctrine\ORM\EntityRepository;
use CherezWeb\HostingBundle\Entity\Tutorial;

class TutorialRepository extends EntityRepository {

    public function findRand($count) {
        $allCount = $this->createQueryBuilder('t')
            ->select('COUNT(t)')
            ->getQuery()
            ->getSingleScalarResult();
        
        $tutorials = array();
        $tutorialQuery = $this->createQueryBuilder('t')
            ->setMaxResults(1)
            ->getQuery();
        
        $firstResultNums = array();
        while (count($firstResultNums) < min($count, $allCount)) { 
            $firstResultNums[rand(0, $allCount - 1)] = 1; 
        }
        
        foreach (array_keys($firstResultNums) as $firstResultNum) {
            $tutorial = $tutorialQuery
                ->setFirstResult($firstResultNum)
                ->getSingleResult();
            /* @var $tutorial Tutorial */
            
            $tutorials[$tutorial->getId()] = $tutorial;
        }
        return $tutorials;
    }

}
