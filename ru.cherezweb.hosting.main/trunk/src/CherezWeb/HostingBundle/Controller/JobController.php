<?php

namespace CherezWeb\HostingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use CherezWeb\HostingBundle\Entity\Allocation;
use CherezWeb\HostingBundle\Entity\Job;
use CherezWeb\HostingBundle\Entity\Task;

class JobController extends Controller{
    
    /**
     * @Security("is_granted('edit', allocation)")
     */
	public function listAction(Allocation $allocation, Request $request) {
        if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            $jobs = $this->getDoctrine()->getManager()->getRepository('CherezWebHostingBundle:Job')->findByAllocation($allocation);
            return $this->render('CherezWebHostingBundle:Job:list_ajax.html.twig', array(
                'jobs' => $jobs,
            ));
        } else {
            return $this->render('CherezWebHostingBundle:Job:list.html.twig', array ('allocation' => $allocation));
        }
    }
    
    /**
     * @Security("is_granted('edit', allocation)")
     */
	public function createAction(Allocation $allocation, Request $request) {
        if (count($allocation->getLockingTasks())) {
            return $this->render('CherezWebDefaultBundle:AjaxResponse:message_info.html.twig', array(
                'message' => 'Площадка содержит выполняющиеся задачи, дождитесь их завершения.'
            ));
        }
        
        $em = $this->getDoctrine()->getManager();
        /* @var $em \Doctrine\ORM\EntityManager */
        
        // Лимит на число job на площадке.
        $jobNumLimit = 20;
        if (count($em->getRepository('CherezWebHostingBundle:Job')->findByAllocation($allocation)) >= $jobNumLimit) {
            return $this->render('CherezWebDefaultBundle:AjaxResponse:message_warning.html.twig', array(
                'message' => sprintf('Вы достигли максимального числа job доступов (%s) на площадке "%s".', $jobNumLimit, $allocation->getName())
            ));
        }
        
        $job = new Job();
        $em->persist($job);
        $job->setSchedule('0 0 * * *');
        $job->setAllocation($allocation);
        
        $jobEditForm = $this->createForm(
            new \CherezWeb\HostingBundle\Form\JobEditType('Создать'),
            $job,
            array('action' => $request->getUri())
        );
        $jobEditForm->handleRequest($request);
        if ($jobEditForm->isValid()) {
            $em->getConnection()->beginTransaction();
            try {
                $allocation->getQuota()->getServer()->setVersionIncFlag(TRUE);

                $task = new Task();
                $em->persist($task);
                $task->setServer($allocation->getQuota()->getServer());
                $task->setType(Task::TYPE_JOB_CREATE);

                $job->setTask($task);

                $em->flush();

                $task->setParameters(array('jobId' => $job->getId()));

                $em->flush();
                $em->getConnection()->commit();
            } catch(\Doctrine\ORM\OptimisticLockException $e) {
                return $this->render('CherezWebDefaultBundle:AjaxResponse:message_warning.html.twig', array(
                    'message' => 'Произошла попытка одновременного доступа к одной записи. Повторите вашу попытку.'
                ));
            } catch (\Exception $e) {
                $em->getConnection()->rollback();
                return $this->render('CherezWebDefaultBundle:AjaxResponse:message_danger.html.twig', array(
                    'message' => 'Не удалось обработать запрос. Обратитесь в техническую поддержку.'
                ));
            }

            $this->get('session')->getFlashBag()->add(
                'notice_success',
                sprintf('Запущено создание задания по расписанию "%s". После завершения на вашу почту будет отправлено уведомление.', $job->getId())
            );

            return $this->render('CherezWebDefaultBundle:AjaxResponse:redirect.html.twig');
        }
        
        return $this->render('CherezWebHostingBundle:Job:create.html.twig', array(
            'form' => $jobEditForm->createView(),
		));
    }
    
    /**
     * @Security("is_granted('edit', job)")
     */
	public function editAction(Job $job, Request $request) {
        if (count($job->getLockingTasks())) {
            return $this->render('CherezWebDefaultBundle:AjaxResponse:message_info.html.twig', array(
                'message' => 'Обновление задания по расписанию не выполнено, дождитесь завершения предыдушей задачи.'
            ));
        }
        
        $jobEditForm = $this->createForm(
            new \CherezWeb\HostingBundle\Form\JobEditType('Изменить'),
            $job,
            array('action' => $request->getUri())
        );
        $jobEditForm->handleRequest($request);
        if ($jobEditForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            /* @var $em \Doctrine\ORM\EntityManager */

            $em->getConnection()->beginTransaction();
            try {
                $job->getAllocation()->getQuota()->getServer()->setVersionIncFlag(TRUE);

                $task = new Task();
                $em->persist($task);
                $task->setServer($job->getAllocation()->getQuota()->getServer());
                $task->setType(Task::TYPE_JOB_UPDATE);

                $job->setTask($task);

                $em->flush();

                $task->setParameters(array('jobId' => $job->getId()));

                $em->flush();
                $em->getConnection()->commit();
            } catch(\Doctrine\ORM\OptimisticLockException $e) {
                return $this->render('CherezWebDefaultBundle:AjaxResponse:message_warning.html.twig', array(
                    'message' => 'Произошла попытка одновременного доступа к одной записи. Повторите вашу попытку.'
                ));
            } catch (\Exception $e) {
                $em->getConnection()->rollback();
                return $this->render('CherezWebDefaultBundle:AjaxResponse:message_danger.html.twig', array(
                    'message' => 'Не удалось обработать запрос. Обратитесь в техническую поддержку.'
                ));
            }

            $this->get('session')->getFlashBag()->add(
                'notice_success',
                sprintf('Запущено обновление задания по расписанию "%s". После завершения на вашу почту будет отправлено уведомление.', $job->getId())
            );

            return $this->render('CherezWebDefaultBundle:AjaxResponse:redirect.html.twig');
        }
        
        return $this->render('CherezWebHostingBundle:Job:edit.html.twig', array(
            'form' => $jobEditForm->createView(),
		));
    }
    
    /**
     * @Security("is_granted('edit', job)")
     */
	public function deleteAction(Job $job, Request $request) {
        $confirmed = (bool)$request->get('confirmed', FALSE);
        
        if ($confirmed === FALSE) {
            return $this->render('CherezWebHostingBundle:Job:delete.html.twig', array('job' => $job));
        } elseif ($confirmed === TRUE) {
            if (count($job->getLockingTasks())) {
                return $this->render('CherezWebDefaultBundle:AjaxResponse:message_info.html.twig', array(
                    'message' => 'Удаление задания по расписанию не выполнено, дождитесь завершения предыдушей задачи.'
                ));
            }
            
            $em = $this->getDoctrine()->getManager();
            /* @var $em \Doctrine\ORM\EntityManager */

            $em->getConnection()->beginTransaction();
            try {
                $job->getAllocation()->getQuota()->getServer()->setVersionIncFlag(TRUE);

                $task = new Task();
                $em->persist($task);
                $task->setServer($job->getAllocation()->getQuota()->getServer());
                $task->setType(Task::TYPE_JOB_DELETE);

                $job->setTask($task);

                $em->flush();

                $task->setParameters(array('jobId' => $job->getId()));
                
                $em->flush();
                $em->getConnection()->commit();
            } catch(\Doctrine\ORM\OptimisticLockException $e) {
                return $this->render('CherezWebDefaultBundle:AjaxResponse:message_warning.html.twig', array(
                    'message' => 'Произошла попытка одновременного доступа к одной записи. Повторите вашу попытку.'
                ));
            } catch (\Exception $e) {
                $em->getConnection()->rollback();
                return $this->render('CherezWebDefaultBundle:AjaxResponse:message_danger.html.twig', array(
                    'message' => 'Не удалось обработать запрос. Обратитесь в техническую поддержку.'
                ));
            }

            $this->get('session')->getFlashBag()->add(
                'notice_success',
                sprintf('Запущено удаление задания по расписанию "%s". После завершения на вашу почту будет отправлено уведомление.', $job->getId())
            );

            return $this->render('CherezWebDefaultBundle:AjaxResponse:redirect.html.twig');
        }
    }
    
}
