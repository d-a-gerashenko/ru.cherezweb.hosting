<?php

namespace CherezWeb\HostingBundle\TaskProcessor\Allocation;

use CherezWeb\HostingBundle\Entity\Task;
use CherezWeb\HostingBundle\Service\ServerCommander;
use CherezWeb\HostingBundle\Entity\Database;
use CherezWeb\HostingBundle\Entity\Ftp;
use CherezWeb\HostingBundle\Entity\Domain;
use CherezWeb\HostingBundle\Entity\Job;

class Delete extends AllocationTaskProcessor {
    
    protected function processAction(Task $task) {
        $allocation = $this->loadAllocation($task);
        
        $jobs = $this->getDoctrine()->getRepository('CherezWebHostingBundle:Job')
            ->findByAllocation($allocation);
        foreach ($jobs as $job) {
            /* @var $job Job */
            $jobDeleteResult = $this->executeCommand(
                $task,
                ServerCommander::COMMAND_JOB_DELETE,
                array(
                    'jobId' => $job->getId(),
                )
            );
            if ($jobDeleteResult !== TRUE) {
                $this->handleUnexpectedCommandResult($jobDeleteResult, sprintf('Ошибка удаления Job #%s.', $job->getId()));
            }
        }
        
        $ftps = $this->getDoctrine()->getRepository('CherezWebHostingBundle:Ftp')
            ->findByAllocation($allocation);
        foreach ($ftps as $ftp) {
            /* @var $ftp Ftp */
            $ftpDeleteResult = $this->executeCommand(
                $task,
                ServerCommander::COMMAND_FTP_DELETE,
                array(
                    'ftpName' => $ftp->getName(),
                )
            );
            if ($ftpDeleteResult !== TRUE) {
                $this->handleUnexpectedCommandResult($ftpDeleteResult, sprintf('Ошибка удаления Ftp #%s.', $ftp->getId()));
            }
        }
        
        $domains = $this->getDoctrine()->getRepository('CherezWebHostingBundle:Domain')
            ->findByAllocation($allocation);
        foreach ($domains as $domain) {
            /* @var $domain Domain */
            $domainDeleteResult = $this->executeCommand(
                $task,
                ServerCommander::COMMAND_DOMAIN_DELETE,
                array(
                    'domainId' => $domain->getId(),
                )
            );
            if ($domainDeleteResult !== TRUE) {
                $this->handleUnexpectedCommandResult($domainDeleteResult, sprintf('Ошибка удаления Domain #%s.', $domain->getId()));
            }
        }
        
        $databases = $this->getDoctrine()->getRepository('CherezWebHostingBundle:Database')
            ->findByAllocation($allocation);
        foreach ($databases as $database) {
            /* @var $database Database */
            $databaseDeleteResult = $this->executeCommand(
                $task,
                ServerCommander::COMMAND_DATABASE_DELETE,
                array(
                    'databaseName' => $database->getName(),
                )
            );
            if ($databaseDeleteResult !== TRUE) {
                $this->handleUnexpectedCommandResult($databaseDeleteResult, sprintf('Ошибка удаления Database #%s.', $database->getId()));
            }
        }
        
        $allocationDeleteResult = $this->executeCommand(
            $task,
            ServerCommander::COMMAND_ALLOCATION_DELETE,
            array(
                'allocationName' => $allocation->getName(),
            )
        );
        if ($allocationDeleteResult !== TRUE) {
            $this->handleUnexpectedCommandResult($allocationDeleteResult, 'Ошибка удаления Allocation.');
        }

        $task->setCompleted(new \DateTime());
        $task->setState(Task::STATE_COMPLETED);
        $task->setResult(Task::RESULT_SUCCESS);
        
        $allocationUser = $allocation->getUser();
        $allocationName = $allocation->getName();
        
        foreach ($jobs as $job) {
            $this->getDoctrine()->getManager()->remove($job);
        }
        foreach ($ftps as $ftp) {
            $this->getDoctrine()->getManager()->remove($ftp);
        }
        foreach ($domains as $domain) {
            $this->getDoctrine()->getManager()->remove($domain);
        }
        foreach ($databases as $database) {
            $this->getDoctrine()->getManager()->remove($database);
        }
        $this->getDoctrine()->getManager()->remove($allocation);
        $this->getDoctrine()->getManager()->flush();
        
        $this->get('cherez_web.default.mailer')->sendMail(
            sprintf('Площадка "%s" удалена', $allocationName),
            $allocationUser->getEmail(),
            'CherezWebHostingBundle:TaskProcessorEmail:allocation_delete',
            array(
                'allocationName' => $allocationName,
            )
        );
    }
    
    public function getType() {
        return Task::TYPE_ALLOCATION_DELETE;
    }
    
}