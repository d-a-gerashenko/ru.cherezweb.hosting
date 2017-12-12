<?php

namespace CherezWeb\HostingBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Инициализирует записи для базовых доменов, так как раньше записей не было.
 * Потом надо удалить этот метод.ы
 */
class TempSoaInitCommand extends ContainerAwareCommand {

    protected function configure() {
        $this
            ->setName('cherez_web:hosting:temp_soa_init')
            ->setDescription('Removing old unused increments.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $output->writeln('Begin' . PHP_EOL);
        
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        /* @var $em \Doctrine\ORM\EntityManager */
        
        $repoDB = $em->getRepository('CherezWebHostingBundle:DomainBase');
        /* @var $repo \CherezWeb\HostingBundle\Repository\DomainBaseRepository */
        $activeDomainBaseList = $repoDB->createQueryBuilder('db')
            ->where('db.state = :state')
            ->setParameter('state', \CherezWeb\HostingBundle\Entity\DomainBase::STATE_ACTIVE)
            ->getQuery()
            ->getResult();
        
        foreach ($activeDomainBaseList as $activeDomainBase) {
            /* @var $activeDomainBase \CherezWeb\HostingBundle\Entity\DomainBase */
            $this->getContainer()->get('cherez_web.hosting.dns_record_manager')
                            ->initBaseDomainRecords($activeDomainBase);
        }
        $em->flush();
        
        $output->writeln('End' . PHP_EOL);
    }
    
}
