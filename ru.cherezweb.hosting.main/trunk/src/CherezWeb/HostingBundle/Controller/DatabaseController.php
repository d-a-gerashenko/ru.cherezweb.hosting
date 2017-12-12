<?php

namespace CherezWeb\HostingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use CherezWeb\HostingBundle\Entity\Allocation;
use CherezWeb\HostingBundle\Entity\Database;
use CherezWeb\HostingBundle\Entity\Task;

class DatabaseController extends Controller{
    
    /**
     * @Security("is_granted('edit', allocation)")
     */
	public function listAction(Allocation $allocation, Request $request) {
        if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            $databases = $this->getDoctrine()->getManager()->getRepository('CherezWebHostingBundle:Database')->findByAllocation($allocation);
            return $this->render('CherezWebHostingBundle:Database:list_ajax.html.twig', array(
                'databases' => $databases,
            ));
        } else {
            return $this->render('CherezWebHostingBundle:Database:list.html.twig', array ('allocation' => $allocation));
        }
    }
    
    /**
     * @Security("is_granted('edit', allocation)")
     */
	public function createAction(Allocation $allocation) {
        if (count($allocation->getLockingTasks())) {
            return $this->render('CherezWebDefaultBundle:AjaxResponse:message_info.html.twig', array(
                'message' => 'Площадка содержит выполняющиеся задачи, дождитесь их завершения.'
            ));
        }
        
        $em = $this->getDoctrine()->getManager();
        /* @var $em \Doctrine\ORM\EntityManager */
        
        // Лимит на число баз на площадке.
        $batabaseNumLimit = 20;
        if (count($em->getRepository('CherezWebHostingBundle:Database')->findByAllocation($allocation)) >= $batabaseNumLimit) {
            return $this->render('CherezWebDefaultBundle:AjaxResponse:message_warning.html.twig', array(
                'message' => sprintf('Вы достигли максимального числа баз данных (%s) на площадке "%s".', $batabaseNumLimit, $allocation->getName())
            ));
        }
        
        $em->getConnection()->beginTransaction();
        try {
            $allocation->getQuota()->getServer()->setVersionIncFlag(TRUE);

            $database = new Database();
            $em->persist($database);
            $database->setAllocation($allocation);

            $task = new Task();
            $em->persist($task);
            $task->setServer($allocation->getQuota()->getServer());
            $task->setType(Task::TYPE_DATABASE_CREATE);

            $database->setTask($task);

            $em->flush();
            
            $task->setParameters(array('databaseId' => $database->getId()));
            
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
            sprintf('Запущено создание базы данных "%s". После завершения на вашу почту будет отправлено уведомление.', $database->getName())
        );
        
        return $this->render('CherezWebDefaultBundle:AjaxResponse:redirect.html.twig');
    }
    
    /**
     * @Security("is_granted('edit', database)")
     */
	public function changePasswordAction(Database $database) {
        if (count($database->getLockingTasks())) {
            return $this->render('CherezWebDefaultBundle:AjaxResponse:message_info.html.twig', array(
                'message' => 'Изменение пароля базы данных не выполнено, дождитесь завершения предыдушей задачи.'
            ));
        }

        $em = $this->getDoctrine()->getManager();
        /* @var $em \Doctrine\ORM\EntityManager */

        $em->getConnection()->beginTransaction();
        try {
            $database->getAllocation()->getQuota()->getServer()->setVersionIncFlag(TRUE);

            $task = new Task();
            $em->persist($task);
            $task->setServer($database->getAllocation()->getQuota()->getServer());
            $task->setType(Task::TYPE_DATABASE_UPDATE_PASSWORD);

            $database->setTask($task);

            $em->flush();

            $task->setParameters(array('databaseId' => $database->getId()));

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
            sprintf('Запущено изменение пароля базы данных "%s". После завершения на вашу почту будет отправлено уведомление.', $database->getName())
        );

        return $this->render('CherezWebDefaultBundle:AjaxResponse:redirect.html.twig');
    }
    
    /**
     * @Security("is_granted('edit', database)")
     */
	public function deleteAction(Database $database, Request $request) {
        $confirmed = (bool)$request->get('confirmed', FALSE);
        
        if ($confirmed === FALSE) {
            return $this->render('CherezWebHostingBundle:Database:delete.html.twig', array('database' => $database));
        } elseif ($confirmed === TRUE) {
            if (count($database->getLockingTasks())) {
                return $this->render('CherezWebDefaultBundle:AjaxResponse:message_info.html.twig', array(
                    'message' => 'Удаление базы данных не выполнено, дождитесь завершения предыдушей задачи.'
                ));
            }
            
            $em = $this->getDoctrine()->getManager();
            /* @var $em \Doctrine\ORM\EntityManager */

            $em->getConnection()->beginTransaction();
            try {
                $database->getAllocation()->getQuota()->getServer()->setVersionIncFlag(TRUE);

                $task = new Task();
                $em->persist($task);
                $task->setServer($database->getAllocation()->getQuota()->getServer());
                $task->setType(Task::TYPE_DATABASE_DELETE);

                $database->setTask($task);

                $em->flush();

                $task->setParameters(array('databaseId' => $database->getId()));
                
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
                sprintf('Запущено удаление базы данных "%s". После завершения на вашу почту будет отправлено уведомление.', $database->getName())
            );

            return $this->render('CherezWebDefaultBundle:AjaxResponse:redirect.html.twig');
        }
    }
    
    /**
     * @Security("is_granted('edit', database)")
     */
	public function phpmyadminAction(Database $database) {
        if (count($database->getLockingTasks())) {
            return $this->render('CherezWebDefaultBundle:AjaxResponse:message_info.html.twig', array(
                'message' => 'Изменение пароля базы данных не выполнено, дождитесь завершения предыдушей задачи.'
            ));
        }
        
        return $this->render('CherezWebHostingBundle:Database:phpmyadmin.html.twig', array(
            'database' => $database,
		));
    }
}
