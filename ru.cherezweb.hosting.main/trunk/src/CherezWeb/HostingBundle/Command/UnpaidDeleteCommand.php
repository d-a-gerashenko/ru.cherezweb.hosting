<?php

namespace CherezWeb\HostingBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use CherezWeb\HostingBundle\Entity\Allocation;
use CherezWeb\HostingBundle\Entity\Task;

/**
 * Запускать раз в 10 минут.
 */
class UnpaidDeleteCommand extends ContainerAwareCommand {

    protected function configure() {
        $this
            ->setName('cherez_web:hosting:unpaid_delete')
            ->setDescription('Delete unpaid allocation.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $output->writeln('Start' . PHP_EOL);
        
        $maxAllocationNum = 10;
        
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        /* @var $em \Doctrine\ORM\EntityManager */
        
        $deleteLimit = new \DateTime;
        $deleteLimit->sub(new \DateInterval('P15D'));
        
        $allocationsToDelete = $em->getRepository('CherezWebHostingBundle:Allocation')
            ->createQueryBuilder('a')
            ->where('a.paidTill < :deleteLimit')
            ->setParameter('deleteLimit', $deleteLimit, 'utcdatetime')
            ->getQuery()
            ->setMaxResults($maxAllocationNum)
            ->getResult();
        
        foreach ($allocationsToDelete as $allocation) {
            /* @var $allocation Allocation */
            if (count($allocation->getLockingTasks())) {
                $output->writeln(sprintf('Can\'t delete Allocation #%s', $allocation->getId()));
                continue;
            }

            $em->getConnection()->beginTransaction();
            try {
                // Понижение числа серверов на квоте не критично, потому её
                // версию не меняем.

                // Если во время удаления площадки какой-то из подобъектов
                // сервера был изменен, то операция не пройдет.
                $allocation->getQuota()->getServer()->setVersionIncFlag(TRUE);

                $task = new Task();
                $em->persist($task);
                $task->setServer($allocation->getQuota()->getServer());
                $task->setType(Task::TYPE_ALLOCATION_DELETE);
                $task->setParameters(array('allocationId' => $allocation->getId()));

                $allocation->setTask($task);

                $em->flush(array(
                    $allocation,
                    $task,
                ));
                
                $em->getConnection()->commit();
            } catch(\Doctrine\ORM\OptimisticLockException $e) {
                $output->writeln(sprintf('Same time access attempt to Allocation #%s', $allocation->getId()));
                continue;
            } catch (\Exception $e) {
                $em->getConnection()->rollback();
                $output->writeln(sprintf('Cant\'t process request to delete Allocation #%s: %s', $allocation->getId(), $e));
                continue;
            }
        }
        
        $output->writeln('End' . PHP_EOL);
    }
}
