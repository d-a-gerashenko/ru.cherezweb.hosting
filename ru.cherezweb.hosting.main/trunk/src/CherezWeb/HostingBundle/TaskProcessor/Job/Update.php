<?php

namespace CherezWeb\HostingBundle\TaskProcessor\Job;

use CherezWeb\HostingBundle\Entity\Task;
use CherezWeb\HostingBundle\Service\ServerCommander;

class Update extends JobTaskProcessor {
    
    protected function processAction(Task $task) {
        $job = $this->loadJob($task);

        $resultDelete = $this->executeCommand(
            $task,
            ServerCommander::COMMAND_JOB_DELETE,
            array(
                'jobId' => $job->getId(),
            )
        );
        if ($resultDelete !== TRUE) {
            $this->handleUnexpectedCommandResult($resultDelete, 'Удаление.');
        }
        
        $resultCreate = $this->executeCommand(
            $task,
            ServerCommander::COMMAND_JOB_CRATE,
            array(
                'allocationName' => $job->getAllocation()->getName(),
                'jobId' => $job->getId(),
                'jobSchedule' => $job->getSchedule(),
                'jobScriptPath' => $job->getScriptPath(),
            )
        );
        if ($resultCreate !== TRUE) {
            $this->handleUnexpectedCommandResult($resultCreate, 'Создание.');
        }

        $task->setCompleted(new \DateTime());
        $task->setState(Task::STATE_COMPLETED);
        $task->setResult(Task::RESULT_SUCCESS);
        $job->setTask(NULL);
        $this->getDoctrine()->getManager()->flush();
        
        $this->get('cherez_web.default.mailer')->sendMail(
            sprintf('Обновлено задание #%s на площадке "%s"', $job->getId(), $job->getAllocation()->getName()),
            $job->getAllocation()->getUser()->getEmail(),
            'CherezWebHostingBundle:TaskProcessorEmail:job_update',
            array(
                'allocationName' => $job->getAllocation()->getName(),
                'jobId' => $job->getId(),
                'jobSchedule' => $job->getSchedule(),
                'jobScriptPath' => $job->getScriptPath(),
            )
        );
    }
    
    public function getType() {
        return Task::TYPE_JOB_UPDATE;
    }
    
}