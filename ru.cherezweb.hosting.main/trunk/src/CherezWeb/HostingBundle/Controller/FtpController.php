<?php

namespace CherezWeb\HostingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use CherezWeb\HostingBundle\Entity\Allocation;
use CherezWeb\HostingBundle\Entity\Ftp;
use CherezWeb\HostingBundle\Entity\Task;

class FtpController extends Controller{
    
    /**
     * @Security("is_granted('edit', allocation)")
     */
	public function listAction(Allocation $allocation, Request $request) {
        if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            $ftps = $this->getDoctrine()->getManager()->getRepository('CherezWebHostingBundle:Ftp')->findByAllocation($allocation);
            return $this->render('CherezWebHostingBundle:Ftp:list_ajax.html.twig', array(
                'ftps' => $ftps,
            ));
        } else {
            return $this->render('CherezWebHostingBundle:Ftp:list.html.twig', array ('allocation' => $allocation));
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
        
        // Лимит на число ftp на площадке.
        $ftpNumLimit = 20;
        if (count($em->getRepository('CherezWebHostingBundle:Ftp')->findByAllocation($allocation)) >= $ftpNumLimit) {
            return $this->render('CherezWebDefaultBundle:AjaxResponse:message_warning.html.twig', array(
                'message' => sprintf('Вы достигли максимального числа ftp доступов (%s) на площадке "%s".', $ftpNumLimit, $allocation->getName())
            ));
        }
        
        $ftp = new Ftp();
        $ftp->setDirPath('/');
        $em->persist($ftp);
        $ftp->setAllocation($allocation);
        
        $ftpEditForm = $this->createForm(
            new \CherezWeb\HostingBundle\Form\FtpEditType('Создать'),
            $ftp,
            array('action' => $request->getUri())
        );
        $ftpEditForm->handleRequest($request);
        if ($ftpEditForm->isValid()) {
            $em->getConnection()->beginTransaction();
            try {
                $allocation->getQuota()->getServer()->setVersionIncFlag(TRUE);

                $task = new Task();
                $em->persist($task);
                $task->setServer($allocation->getQuota()->getServer());
                $task->setType(Task::TYPE_FTP_CREATE);

                $ftp->setTask($task);

                $em->flush();

                $task->setParameters(array('ftpId' => $ftp->getId()));

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
                sprintf('Запущено создание ftp доступа "%s". После завершения на вашу почту будет отправлено уведомление.', $ftp->getName())
            );

            return $this->render('CherezWebDefaultBundle:AjaxResponse:redirect.html.twig');
        }
        
        return $this->render('CherezWebHostingBundle:Ftp:create.html.twig', array(
            'form' => $ftpEditForm->createView(),
		));
    }
    
    /**
     * @Security("is_granted('edit', ftp)")
     */
	public function changePasswordAction(Ftp $ftp) {
        if (count($ftp->getLockingTasks())) {
            return $this->render('CherezWebDefaultBundle:AjaxResponse:message_info.html.twig', array(
                'message' => 'Изменение пароля ftp доступа не выполнено, дождитесь завершения предыдушей задачи.'
            ));
        }

        $em = $this->getDoctrine()->getManager();
        /* @var $em \Doctrine\ORM\EntityManager */

        $em->getConnection()->beginTransaction();
        try {
            $ftp->getAllocation()->getQuota()->getServer()->setVersionIncFlag(TRUE);

            $task = new Task();
            $em->persist($task);
            $task->setServer($ftp->getAllocation()->getQuota()->getServer());
            $task->setType(Task::TYPE_FTP_UPDATE_PASSWORD);

            $ftp->setTask($task);

            $em->flush();

            $task->setParameters(array('ftpId' => $ftp->getId()));

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
            sprintf('Запущено изменение пароля ftp доступа "%s". После завершения на вашу почту будет отправлено уведомление.', $ftp->getName())
        );

        return $this->render('CherezWebDefaultBundle:AjaxResponse:redirect.html.twig');
    }
    
    /**
     * @Security("is_granted('edit', ftp)")
     */
	public function changeDirAction(Ftp $ftp, Request $request) {
        if (count($ftp->getLockingTasks())) {
            return $this->render('CherezWebDefaultBundle:AjaxResponse:message_info.html.twig', array(
                'message' => 'Изменение директории ftp доступа не выполнено, дождитесь завершения предыдушей задачи.'
            ));
        }
        
        $ftpEditForm = $this->createForm(
            new \CherezWeb\HostingBundle\Form\FtpEditType('Изменить'),
            $ftp,
            array('action' => $request->getUri())
        );
        $ftpEditForm->handleRequest($request);
        if ($ftpEditForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            /* @var $em \Doctrine\ORM\EntityManager */

            $em->getConnection()->beginTransaction();
            try {
                $ftp->getAllocation()->getQuota()->getServer()->setVersionIncFlag(TRUE);

                $task = new Task();
                $em->persist($task);
                $task->setServer($ftp->getAllocation()->getQuota()->getServer());
                $task->setType(Task::TYPE_FTP_UPDATE_DIR_PATH);

                $ftp->setTask($task);

                $em->flush();

                $task->setParameters(array('ftpId' => $ftp->getId()));

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
                sprintf('Запущено изменение пароля ftp доступа "%s". После завершения на вашу почту будет отправлено уведомление.', $ftp->getName())
            );

            return $this->render('CherezWebDefaultBundle:AjaxResponse:redirect.html.twig');
        }
        
        return $this->render('CherezWebHostingBundle:Ftp:edit.html.twig', array(
            'form' => $ftpEditForm->createView(),
		));
    }
    
    /**
     * @Security("is_granted('edit', ftp)")
     */
	public function deleteAction(Ftp $ftp, Request $request) {
        $confirmed = (bool)$request->get('confirmed', FALSE);
        
        if ($confirmed === FALSE) {
            return $this->render('CherezWebHostingBundle:Ftp:delete.html.twig', array('ftp' => $ftp));
        } elseif ($confirmed === TRUE) {
            if (count($ftp->getLockingTasks())) {
                return $this->render('CherezWebDefaultBundle:AjaxResponse:message_info.html.twig', array(
                    'message' => 'Удаление ftp доступа не выполнено, дождитесь завершения предыдушей задачи.'
                ));
            }
            
            $em = $this->getDoctrine()->getManager();
            /* @var $em \Doctrine\ORM\EntityManager */

            $em->getConnection()->beginTransaction();
            try {
                $ftp->getAllocation()->getQuota()->getServer()->setVersionIncFlag(TRUE);

                $task = new Task();
                $em->persist($task);
                $task->setServer($ftp->getAllocation()->getQuota()->getServer());
                $task->setType(Task::TYPE_FTP_DELETE);

                $ftp->setTask($task);

                $em->flush();

                $task->setParameters(array('ftpId' => $ftp->getId()));
                
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
                sprintf('Запущено удаление ftp доступа "%s". После завершения на вашу почту будет отправлено уведомление.', $ftp->getName())
            );

            return $this->render('CherezWebDefaultBundle:AjaxResponse:redirect.html.twig');
        }
    }
    
}
