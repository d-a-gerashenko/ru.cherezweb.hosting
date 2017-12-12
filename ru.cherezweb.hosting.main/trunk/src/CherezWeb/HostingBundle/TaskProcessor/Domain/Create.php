<?php

namespace CherezWeb\HostingBundle\TaskProcessor\Domain;

use CherezWeb\HostingBundle\Entity\Task;
use CherezWeb\HostingBundle\Service\ServerCommander;

class Create extends DomainTaskProcessor {
    
    protected function processAction(Task $task) {
        $domain = $this->loadDomain($task);

        $result = $this->executeCommand(
            $task,
            ServerCommander::COMMAND_DOMAIN_CREATE,
            array(
                'allocationName' => $domain->getAllocation()->getName(),
                'domainId' => $domain->getId(),
                'domainName' => $domain->getName(),
                'domainDirPath' => $domain->getDirPath(),
            )
        );
        if ($result !== TRUE) {
            $this->handleUnexpectedCommandResult($result);
        }

        $task->setCompleted(new \DateTime());
        $task->setState(Task::STATE_COMPLETED);
        $task->setResult(Task::RESULT_SUCCESS);
        $domain->setTask(NULL);
        $this->getDoctrine()->getManager()->flush();
        
        $this->get('cherez_web.default.mailer')->sendMail(
            sprintf('Добавлен домен "%s" на площадку "%s"', $domain->getName(), $domain->getAllocation()->getName()),
            $domain->getAllocation()->getUser()->getEmail(),
            'CherezWebHostingBundle:TaskProcessorEmail:domain_create',
            array(
                'allocationName' => $domain->getAllocation()->getName(),
                'domainName' => $domain->getName(),
                'domainDirPath' => $domain->getDirPath(),
            )
        );
    }
    
    public function getType() {
        return Task::TYPE_DOMAIN_CREATE;
    }
    
}