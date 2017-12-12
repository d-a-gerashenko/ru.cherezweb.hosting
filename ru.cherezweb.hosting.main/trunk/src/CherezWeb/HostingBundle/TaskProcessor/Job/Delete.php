<?php

namespace CherezWeb\HostingBundle\TaskProcessor\Job;

use CherezWeb\HostingBundle\Entity\Task;
use CherezWeb\HostingBundle\Service\ServerCommander;

class Delete extends JobTaskProcessor {
    
    protected function processAction(Task $task) {
        $job = $this->loadJob($task);

        $result = $this->executeCommand(
            $task,
            ServerCommander::COMMAND_JOB_DELETE,
            array(
                'jobId' => $job->getId(),
            )
        );
        if ($result !== TRUE) {
            $this->handleUnexpectedCommandResult($result);
        }

        $task->setCompleted(new \DateTime());
        $task->setState(Task::STATE_COMPLETED);
        $task->setResult(Task::RESULT_SUCCESS);
        
        $jobAllocation = $job->getAllocation();
        $jobId = $job->getId();
        $jobSchedule = $job->getSchedule();
        $jobScriptPath = $job->getScriptPath();
        
        $this->getDoctrine()->getManager()->remove($job);
        $this->getDoctrine()->getManager()->flush();
        
        $this->get('cherez_web.default.mailer')->sendMail(
            sprintf('Удалено задание по расписанию #%s на площадке "%s"', $jobId, $jobAllocation->getName()),
            $jobAllocation->getUser()->getEmail(),
            'CherezWebHostingBundle:TaskProcessorEmail:job_delete',
            array(
                'allocationName' => $jobAllocation->getName(),
                'jobId' => $jobId,
                'jobSchedule' => $jobSchedule,
                'jobScriptPath' => $jobScriptPath,
            )
        );
    }
    
    public function getType() {
        return Task::TYPE_JOB_DELETE;
    }
    
}