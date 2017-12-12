<?php

namespace CherezWeb\HostingBundle\TaskProcessor\Domain;

use CherezWeb\HostingBundle\Entity\Task;
use CherezWeb\HostingBundle\Service\ServerCommander;

class Delete extends DomainTaskProcessor {
    
    protected function processAction(Task $task) {
        $domain = $this->loadDomain($task);

        $result = $this->executeCommand(
            $task,
            ServerCommander::COMMAND_DOMAIN_DELETE,
            array(
                'domainId' => $domain->getId(),
            )
        );
        if ($result !== TRUE) {
            $this->handleUnexpectedCommandResult($result);
        }

        $task->setCompleted(new \DateTime());
        $task->setState(Task::STATE_COMPLETED);
        $task->setResult(Task::RESULT_SUCCESS);
        
        $domainAllocation = $domain->getAllocation();
        $domainName = $domain->getName();
        
        $this->getDoctrine()->getManager()->remove($domain);
        $this->getDoctrine()->getManager()->flush();
        
        $this->get('cherez_web.default.mailer')->sendMail(
            sprintf('Домен "%s" отвязан от площадки "%s"', $domainName, $domainAllocation->getName()),
            $domainAllocation->getUser()->getEmail(),
            'CherezWebHostingBundle:TaskProcessorEmail:domain_delete',
            array(
                'allocationName' => $domainAllocation->getName(),
                'domainName' => $domainName,
            )
        );
    }
    
    public function getType() {
        return Task::TYPE_DOMAIN_DELETE;
    }
    
}