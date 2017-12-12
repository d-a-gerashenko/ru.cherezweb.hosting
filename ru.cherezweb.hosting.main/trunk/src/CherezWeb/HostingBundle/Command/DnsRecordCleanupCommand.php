<?php

namespace CherezWeb\HostingBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Запускать раз в день.
 */
class DnsRecordCleanupCommand extends ContainerAwareCommand {

    protected function configure() {
        $this
            ->setName('cherez_web:hosting:dns_record_cleanup')
            ->setDescription('Removing old dleted and unused DNS records.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $output->writeln('Begin' . PHP_EOL);
        
        
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        /* @var $em \Doctrine\ORM\EntityManager */
        
        $repo = $em->getRepository('CherezWebHostingBundle:DnsRecord');
        /* @var $repo \CherezWeb\HostingBundle\Repository\DnsRecordRepository */
        
        $output->writeln('Cleanup unused records.' . PHP_EOL);
        $repo->cleanupUnused();
        $output->writeln('Cleanup deleted records.' . PHP_EOL);
        $repo->cleanupDeleted();
        
        $output->writeln('End' . PHP_EOL);
    }
    
}
