<?php

namespace CherezWeb\HostingBundle\Command;

use CherezWeb\DefaultBundle\Command\LockingCommandAbstract;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use CherezWeb\HostingBundle\Entity\Server;
use CherezWeb\HostingBundle\Entity\Task;

/**
 * Запускать раз в минуту.
 * Для разных серверов таски можно выполнять параллельно, для одного сервера
 * таски должны выполняться последовательно одним обработчиком, иначе все сломается.
 * Чтобы ускорить обработку, можно параллельно запускать обработчики для разных серверов.
 * Нужно селдить, чтобы для одного и того же сервера новая обработка таска не зупскалась,
 * пока не завершится старая.
 */
class TaskProcessingCommand extends LockingCommandAbstract {

    protected function configure() {
        $this
            ->setName('cherez_web:hosting:task_processing')
            ->setDescription('Process Tasks.');
    }

    protected function executeWithLocking(InputInterface $input, OutputInterface $output) {
        $output->writeln('Start' . PHP_EOL);
        
        $taskManager = $this->getContainer()->get('cherez_web.hosting.task_manager');
        /* @var $taskManager \CherezWeb\HostingBundle\Service\TaskManager */
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        /* @var $em \Doctrine\ORM\EntityManager */
        
        for ($i = 0; $i < 50; $i++) {
            $output->writeln(sprintf('Iteration #%s' . PHP_EOL, $i));
            
            // Проверяем наличие ошибок.
            $errors = $em->getRepository('CherezWebHostingBundle:Task')
                ->createQueryBuilder('t')
                ->select('count(t.id)')
                ->where('t.result = :result')
                ->setParameter('result', Task::RESULT_FAILURE)
                ->getQuery()
                ->getSingleScalarResult();
            
            if ($errors > 0) {
                throw new \Exception('Task with RESULT_FAILURE exists.' . PHP_EOL);
            }
            
            // Получаем сервер с тасками.
            $server = $em->getRepository('CherezWebHostingBundle:Server')
                ->createQueryBuilder('s')
                ->join('CherezWebHostingBundle:Task', 't', 'WITH','t.server = s.id')
                ->where('t.state != :completed')
                ->setParameter('completed', Task::STATE_COMPLETED)
                ->groupBy('s')
                ->getQuery()
                ->setMaxResults(1)
                ->getOneOrNullResult();
            /* @var $server Server */
            if ($server === NULL) {
                $output->writeln('No more Servers with Tasks.' . PHP_EOL);
                break;
            }
            $output->writeln(sprintf('Server #%s' . PHP_EOL, $server->getId()));
            $taskManager->processNextTask($server);
        }
        
        $output->writeln('End' . PHP_EOL);
    }
    
}
