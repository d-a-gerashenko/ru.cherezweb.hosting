<?php

namespace CherezWeb\HostingBundle\TaskProcessor\Domain;

use CherezWeb\HostingBundle\Entity\Task;
use CherezWeb\HostingBundle\Service\ServerCommander;

class Update extends DomainTaskProcessor {
    
    protected function processAction(Task $task) {
        $domain = $this->loadDomain($task);

        $deleteResult = $this->executeCommand(
            $task,
            ServerCommander::COMMAND_DOMAIN_DELETE,
            array(
                'domainId' => $domain->getId(),
            )
        );
        if ($deleteResult !== TRUE) {
            $this->handleUnexpectedCommandResult($deleteResult, 'Ошибка при удалении.');
        }
        
        $createResult = $this->executeCommand(
            $task,
            ServerCommander::COMMAND_DOMAIN_CREATE,
            array(
                'allocationName' => $domain->getAllocation()->getName(),
                'domainId' => $domain->getId(),
                'domainName' => $domain->getName(),
                'domainDirPath' => $domain->getDirPath(),
            )
        );
        if ($createResult !== TRUE) {
            $this->handleUnexpectedCommandResult($createResult, 'Ошибка при создании.');
        }

        $task->setCompleted(new \DateTime());
        $task->setState(Task::STATE_COMPLETED);
        $task->setResult(Task::RESULT_SUCCESS);
        $domain->setTask(NULL);
        $this->getDoctrine()->getManager()->flush();
        
        $this->get('cherez_web.default.mailer')->sendMail(
            sprintf('Обновлен домен "%s" на площадке "%s"', $domain->getName(), $domain->getAllocation()->getName()),
            $domain->getAllocation()->getUser()->getEmail(),
            'CherezWebHostingBundle:TaskProcessorEmail:domain_update',
            array(
                'allocationName' => $domain->getAllocation()->getName(),
                'domainName' => $domain->getName(),
                'domainDirPath' => $domain->getDirPath(),
            )
        );
    }
    
    public function getType() {
        return Task::TYPE_DOMAIN_UPDATE;
    }
    
}