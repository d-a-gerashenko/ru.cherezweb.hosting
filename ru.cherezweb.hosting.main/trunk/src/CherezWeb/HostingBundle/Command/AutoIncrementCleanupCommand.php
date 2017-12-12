<?php

namespace CherezWeb\HostingBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Запускать раз в день.
 */
class AutoIncrementCleanupCommand extends ContainerAwareCommand {

    protected function configure() {
        $this
            ->setName('cherez_web:hosting:auto_increment_cleanup')
            ->setDescription('Removing old unused increments.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $output->writeln('Begin' . PHP_EOL);
        
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        /* @var $em \Doctrine\ORM\EntityManager */
        
        $repo = $em->getRepository('CherezWebHostingBundle:AutoIncrement');
        /* @var $repo \CherezWeb\HostingBundle\Repository\AutoIncrementRepository */
        
        $repo->cleanup();
        
        $output->writeln('End' . PHP_EOL);
    }
    
}
