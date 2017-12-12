<?php

namespace CherezWeb\HostingBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use CherezWeb\HostingBundle\Entity\Allocation;
use CherezWeb\HostingBundle\Entity\Task;
use CherezWeb\HostingBundle\Entity\AllocationLock;

/**
 * Запускать раз в 10 минут.
 */
class UnpaidBlockCommand extends ContainerAwareCommand {

    protected function configure() {
        $this
            ->setName('cherez_web:hosting:unpaid_block')
            ->setDescription('Block unpaid allocation.');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $output->writeln('Start' . PHP_EOL);
        
        $maxAllocationNum = 10;
        
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        /* @var $em \Doctrine\ORM\EntityManager */
        
        $blockedAllocationsDQL = $em->getRepository('CherezWebHostingBundle:AllocationLock')
            ->createQueryBuilder('al')
            ->select('IDENTITY(al.allocation)')
            ->where('al.type = :noPayment')
            ->getDQL();
        
        $allocationsToBlockQB = $em->getRepository('CherezWebHostingBundle:Allocation')
            ->createQueryBuilder('a');
        $allocationsToBlock = $allocationsToBlockQB
            ->where('a.paidTill < :now')
            ->setParameter('now', new \DateTime, 'utcdatetime')
            ->andWhere($allocationsToBlockQB->expr()->notIn('a.id', $blockedAllocationsDQL))
            ->setParameter('noPayment', AllocationLock::TYPE_NO_PAYMENT)
            ->getQuery()
            ->setMaxResults($maxAllocationNum)
            ->getResult();

        foreach ($allocationsToBlock as $allocation) {
            /* @var $allocation Allocation */
            if (count($allocation->getLockingTasks())) {
                $output->writeln(sprintf('Can\'t block Allocation #%s', $allocation->getId()));
                continue;
            }

            $em->getConnection()->beginTransaction();
            try {
                // Понижение числа серверов на квоте не критично, потому её
                // версию не меняем.

                // Если во время удаления площадки какой-то из подобъектов
                // сервера был изменен, то операция не пройдет.
                $allocation->getQuota()->getServer()->setVersionIncFlag(TRUE);

                $allocationLock = new AllocationLock();
                $em->persist($allocationLock);
                $allocationLock->setAllocation($allocation);
                $allocationLock->setType(AllocationLock::TYPE_NO_PAYMENT);
                
                $task = new Task();
                $em->persist($task);
                $task->setServer($allocation->getQuota()->getServer());
                $task->setType(Task::TYPE_ALLOCATION_UPDATE_LOCK);
                $task->setParameters(array('allocationId' => $allocation->getId()));

                $allocation->setTask($task);

                $em->flush(array(
                    $allocation,
                    $task,
                    $allocationLock,
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
