<?php

namespace CherezWeb\HostingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use CherezWeb\HostingBundle\Entity\Allocation;
use CherezWeb\HostingBundle\Entity\Domain;
use CherezWeb\HostingBundle\Entity\Task;

class DomainController extends Controller{
    
    /**
     * @Security("is_granted('edit', allocation)")
     */
	public function listAction(Allocation $allocation, Request $request) {
        if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            $domains = $this->getDoctrine()->getManager()->getRepository('CherezWebHostingBundle:Domain')->findByAllocation($allocation);
            return $this->render('CherezWebHostingBundle:Domain:list_ajax.html.twig', array(
                'domains' => $domains,
            ));
        } else {
            return $this->render('CherezWebHostingBundle:Domain:list.html.twig', array ('allocation' => $allocation));
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
        
        // Лимит на число domain на площадке.
        $domainNumLimit = 100;
        if (count($em->getRepository('CherezWebHostingBundle:Domain')->findByAllocation($allocation)) >= $domainNumLimit) {
            return $this->render('CherezWebDefaultBundle:AjaxResponse:message_warning.html.twig', array(
                'message' => sprintf('Вы достигли максимального числа доменов (%s) на площадке "%s".', $domainNumLimit, $allocation->getName())
            ));
        }
        
        $domain = new Domain();
        $em->persist($domain);
        $domain->setAllocation($allocation);
        
        $domainEditForm = $this->createForm(
            new \CherezWeb\HostingBundle\Form\DomainEditType('Создать'),
            $domain,
            array('action' => $request->getUri())
        );
        $domainEditForm->handleRequest($request);
        if ($domainEditForm->isValid()) {
            $em->getConnection()->beginTransaction();
            try {
                $allocation->getQuota()->getServer()->setVersionIncFlag(TRUE);
                
                $activeDomainBase = $this->get('cherez_web.hosting.domain_base_manager')->findOrInitActive($allocation->getUser(), $domain->getName());
                /* @var $activeDomainBase \CherezWeb\HostingBundle\Entity\DomainBase */
                
                if ($activeDomainBase === NULL) {
                    return $this->render('CherezWebHostingBundle:Domain:create.html.twig', array(
                        'showDomainBaseMessage' => TRUE,
                        'form' => $domainEditForm->createView(),
                    ));
                }
                
                $activeDomainBase->setVersionIncFlag(TRUE);
                
                $domain->setDomainBase($activeDomainBase);

                $task = new Task();
                $em->persist($task);
                $task->setServer($allocation->getQuota()->getServer());
                $task->setType(Task::TYPE_DOMAIN_CREATE);

                $domain->setTask($task);

                $em->flush();

                $task->setParameters(array('domainId' => $domain->getId()));

                $em->flush();
                
                // DNS RECORD WITH A TYPE
                $em->getRepository('CherezWebHostingBundle:DnsRecord')
                    ->createOrUpdateARecord(
                    $domain->getName(),
                    $domain->getAllocation()->getQuota()->getServer()->getIpAddress()
                );
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
                sprintf('Запущено создание домена "%s". После завершения на вашу почту будет отправлено уведомление.', $domain->getName())
            );

            return $this->render('CherezWebDefaultBundle:AjaxResponse:redirect.html.twig');
        }
        
        return $this->render('CherezWebHostingBundle:Domain:create.html.twig', array(
            'form' => $domainEditForm->createView(),
		));
    }
    
    /**
     * @Security("is_granted('edit', domain)")
     */
	public function editAction(Domain $domain, Request $request) {
        if (count($domain->getLockingTasks())) {
            return $this->render('CherezWebDefaultBundle:AjaxResponse:message_info.html.twig', array(
                'message' => 'Обновление домена не выполнено, дождитесь завершения предыдушей задачи.'
            ));
        }
        
        $domainEditForm = $this->createForm(
            new \CherezWeb\HostingBundle\Form\DomainEditType('Изменить'),
            $domain,
            array('action' => $request->getUri())
        );
        $domainEditForm->handleRequest($request);
        if ($domainEditForm->isValid()) {
            $em = $this->getDoctrine()->getManager();
            /* @var $em \Doctrine\ORM\EntityManager */

            $em->getConnection()->beginTransaction();
            try {
                $domain->getAllocation()->getQuota()->getServer()->setVersionIncFlag(TRUE);
                
                $activeDomainBase = $this->get('cherez_web.hosting.domain_base_manager')->findOrInitActive($domain->getAllocation()->getUser(), $domain->getName());
                /* @var $activeDomainBase \CherezWeb\HostingBundle\Entity\DomainBase */
                
                if ($activeDomainBase === NULL) {
                    return $this->render('CherezWebHostingBundle:Domain:edit.html.twig', array(
                        'showDomainBaseMessage' => TRUE,
                        'form' => $domainEditForm->createView(),
                    ));
                }
                
                $activeDomainBase->setVersionIncFlag(TRUE);
                
                $domain->setDomainBase($activeDomainBase);

                $task = new Task();
                $em->persist($task);
                $task->setServer($domain->getAllocation()->getQuota()->getServer());
                $task->setType(Task::TYPE_DOMAIN_UPDATE);

                $domain->setTask($task);

                $em->flush();

                $task->setParameters(array('domainId' => $domain->getId()));

                $em->flush();
                
                // DNS RECORD WITH A TYPE
                $em->getRepository('CherezWebHostingBundle:DnsRecord')
                    ->createOrUpdateARecord(
                    $domain->getName(),
                    $domain->getAllocation()->getQuota()->getServer()->getIpAddress()
                );
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
                sprintf('Запущено обновление домена "%s". После завершения на вашу почту будет отправлено уведомление.', $domain->getName())
            );

            return $this->render('CherezWebDefaultBundle:AjaxResponse:redirect.html.twig');
        }
        
        return $this->render('CherezWebHostingBundle:Domain:edit.html.twig', array(
            'form' => $domainEditForm->createView(),
		));
    }
    
    /**
     * @Security("is_granted('edit', domain)")
     */
	public function deleteAction(Domain $domain, Request $request) {
        $confirmed = (bool)$request->get('confirmed', FALSE);
        
        if ($confirmed === FALSE) {
            return $this->render('CherezWebHostingBundle:Domain:delete.html.twig', array('domain' => $domain));
        } elseif ($confirmed === TRUE) {
            if (count($domain->getLockingTasks())) {
                return $this->render('CherezWebDefaultBundle:AjaxResponse:message_info.html.twig', array(
                    'message' => 'Удаление домена не выполнено, дождитесь завершения предыдушей задачи.'
                ));
            }
            
            $em = $this->getDoctrine()->getManager();
            /* @var $em \Doctrine\ORM\EntityManager */

            $em->getConnection()->beginTransaction();
            try {
                $domain->getAllocation()->getQuota()->getServer()->setVersionIncFlag(TRUE);

                $task = new Task();
                $em->persist($task);
                $task->setServer($domain->getAllocation()->getQuota()->getServer());
                $task->setType(Task::TYPE_DOMAIN_DELETE);

                $domain->setTask($task);

                $em->flush();

                $task->setParameters(array('domainId' => $domain->getId()));
                
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
                sprintf('Запущено удаление домена "%s". После завершения на вашу почту будет отправлено уведомление.', $domain->getName())
            );

            return $this->render('CherezWebDefaultBundle:AjaxResponse:redirect.html.twig');
        }
    }
    
}
