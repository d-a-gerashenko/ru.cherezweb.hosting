<?php

namespace CherezWeb\HostingBundle\Service;

use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use CherezWeb\HostingBundle\TaskProcessor\TaskProcessor;
use CherezWeb\HostingBundle\Entity\Task;
use CherezWeb\HostingBundle\Entity\Server;

class TaskManager {
    /**
     * @var Container;
     */
    private $container;
    
    public function __construct(Container $container) {
        $this->container = $container;
        $this->taskProcessors = array();
    }
    
    /**
     * Выполняет очередной таск сервера.
     * @param Server $server
     * @return Task Выполненный таск или NULL.
     */
    public function processNextTask(Server $server) {
        $nextTask = $this->container->get('doctrine')
            ->getRepository('CherezWebHostingBundle:Task')
            ->findNextTask($server);
        if ($nextTask !== NULL) {
            $this->processTask($nextTask);
        }
        return $nextTask;
    }
    
    private $taskProcessors;
    
    public function addTaskProcessor(TaskProcessor $taskProcessor) {
        if (isset($this->taskProcessors[$taskProcessor->getType()])) {
            throw new \Exception(sprintf('TaskProcessors с типом "%s" уже зарегистрирован.', $taskProcessor->getType()));
        }
        $taskProcessor->setContainer($this->container);
        $this->taskProcessors[$taskProcessor->getType()] = $taskProcessor;
    }
    
    public function processTask(Task $task) {
        try {
            if (!isset($this->taskProcessors[$task->getType()])) {
                throw new \Exception(sprintf('TaskProcessor с типом "%s" не зарегистрирован.', $task->getType()));
            }
            $this->taskProcessors[$task->getType()]->process($task);
        } catch (\Exception $exc) {
            $task->setCompleted(new \DateTime());
            $task->setState(Task::STATE_COMPLETED);
            $task->setResult(Task::RESULT_FAILURE);
            $task->setError($exc);
            $this->container->get('doctrine')->getManager()->flush();
            
            $this->container->get('cherez_web.default.mailer')->sendMail(
                'Уведомление администратора Hosting.CherezWeb.ru',
                'support@cherezweb.ru',
                'CherezWebHostingBundle:Email:admin_notification',
                array('message' => "Ошибка выполнения таска #{$task->getId()}:" . PHP_EOL . $exc)
            );
        }
    }

}