<?php

namespace CherezWeb\HostingBundle\TaskProcessor;

use Symfony\Component\DependencyInjection\ContainerAware;
use CherezWeb\HostingBundle\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Registry;
use CherezWeb\HostingBundle\Service\ServerCommander;

abstract class TaskProcessor extends ContainerAware {

    /**
     * Обрабатывает Task, ошибки и исключения записывает в переданный объект.
     * @throws \Exception
     */
    public function process(Task $task) {
        if ($task->getType() !== $this->getType()) {
            throw new \Exception(sprintf('Неправильный тип Task "%s".', $task->getType()));
        }
        $this->processAction($task);
    }
    
    /**
     * Обрабатывает Task, ошибки и исключения записывает в переданный объект.
     * @throws \Exception
     */
    abstract protected function processAction(Task $task);

    /**
     * @return string Тип Task, который обрабатывается этим обработчиком.
     */
    abstract public function getType();

    /**
     * Returns true if the service id is defined.
     *
     * @param string $id The service id
     *
     * @return bool    true if the service id is defined, false otherwise
     */
    public function has($id) {
        return $this->container->has($id);
    }

    /**
     * Gets a service by id.
     *
     * @param string $id The service id
     *
     * @return object The service
     */
    public function get($id) {
        return $this->container->get($id);
    }

    /**
     * Shortcut to return the Doctrine Registry service.
     *
     * @return Registry
     *
     * @throws \LogicException If DoctrineBundle is not available
     */
    public function getDoctrine() {
        if (!$this->container->has('doctrine')) {
            throw new \LogicException('The DoctrineBundle is not registered in your application.');
        }

        return $this->container->get('doctrine');
    }

    /**
     * @return ServerCommander
     */
    public function getServerCommander() {
        if (!$this->container->has('cherez_web.hosting.server_commander')) {
            throw new \LogicException('The ServerCommander is not registered in your application.');
        }

        return $this->container->get('cherez_web.hosting.server_commander');
    }
    
    /**
     * Помечает таск выполненным (использует flush для таска) и выполняет команду.
     * @param Task $task
     * @param string $command
     * @param array $parameters
     * @return mixed Ответ сервера.
     */
    public function executeCommand(Task $task, $command, array $parameters = array()) {
        if ($task->getExecuted() === NULL) {
            $task->setExecuted(new \DateTime());
            $task->setState(Task::STATE_EXECUTED);
            $em = $this->getDoctrine()->getManager();
            /* @var $em \Doctrine\ORM\EntityManager */
            $em->flush($task);
        }
        
        return $this->getServerCommander()->executeCommand(
            $task->getServer(),
            $command,
            $parameters
        );
    }

    /**
     * Returns a rendered view.
     *
     * @param string $view       The view name
     * @param array  $parameters An array of parameters to pass to the view
     *
     * @return string The rendered view
     */
    public function renderView($view, array $parameters = array()) {
        return $this->container->get('templating')->render($view, $parameters);
    }
    
    protected function handleUnexpectedCommandResult($commandResult, $message = '') {
        if ($message != '') {
            $message = $message . PHP_EOL;
        }
        throw new \Exception($message . sprintf('Неправильный ответ сервера (%s): %s', gettype($commandResult), $commandResult));
    }

}
