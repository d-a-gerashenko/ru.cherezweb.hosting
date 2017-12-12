<?php

namespace CherezWeb\HostingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use CherezWeb\HostingBundle\Entity\User;
use CherezWeb\HostingBundle\Entity\Task;
use CherezWeb\HostingBundle\Entity\Allocation;
use CherezWeb\HostingBundle\Entity\AllocationLock;
use CherezWeb\HostingBundle\Entity\Plan;
use CherezWeb\HostingBundle\Entity\Quota;
use Symfony\Component\HttpFoundation\Request;

class AllocationController extends Controller{
    
    /**
     * @Security("is_authenticated()")
     */
	public function createAction(Plan $plan) {
        if (!$plan->getIsActive()) {
            return $this->render('CherezWebDefaultBundle:AjaxResponse:message_warning.html.twig', array(
                'message' => 'Запрашиваемый тариф заблокирован.'
            ));
        }
        
        $em = $this->getDoctrine()->getManager();
        /* @var $em \Doctrine\ORM\EntityManager */
        
        $user = $this->getUser();
        /* @var $user User */
        
        // Подбираем наиболее заполненную квоту, которая относится к нашему тарифу.
        $quota = $em->getRepository('CherezWebHostingBundle:Quota')->findQuotaForNewAllocation($plan);
        /* @var $quota Quota */
        
        if ($quota === NULL) {
            $mailer = $this->get('cherez_web.default.mailer');
            /* @var $mailer \CherezWeb\DefaultBundle\Service\Mailer  */
            $mailer->sendMail(
                'Уведомление администратора Hosting.CherezWeb.ru',
                'support@cherezweb.ru',
                'CherezWebHostingBundle:Email:admin_notification',
                array('message' => sprintf('На тарифе #%s закончились квоты, пытался зарегистрироваться пользователь #%s.', $plan->getId(), $user->getId()))
            );
            return $this->render('CherezWebDefaultBundle:AjaxResponse:message_info.html.twig', array(
                'message' => 'Создание площадки на данном тарифе временно не доступно, ожидается запуск новых серверов. Повторите попытку позднее.'
            ));
        }
        
        if (count($quota->getServer()->getLockingTasks())) {
            return $this->render('CherezWebDefaultBundle:AjaxResponse:message_info.html.twig', array(
                'message' => 'Создание площадки временно не доступно, на сервере ведутся технические работы. Повторите попытку позднее.'
            ));
        }
        
        $sum = $plan->getPrice();
        
        if ($user->getWallet()->getBalance() < $sum) {
            return $this->render('CherezWebDefaultBundle:AjaxResponse:message_warning.html.twig', array(
                'message' => 'На вашем счете недостаточно средств.'
            ));
        }
        
        $em->getConnection()->beginTransaction();
        
        try {
            // Чтобы не создать площадок больше допустимого числа, выполняем
            // последовательно, т.е. пока создается одна площадка, другая
            // получает предложение подождать.
            $quota->setVersionIncFlag(TRUE);
            
            // Если во время создания площадки сервер был, например,
            // заблокирован, то будет предложено подождать.
            $quota->getServer()->setVersionIncFlag(TRUE);

            $allocation = new Allocation();
            $em->persist($allocation);
            $allocation->setUser($this->getUser());
            $allocation->setQuota($quota);
            $allocation->setPaidTill(new \DateTime);
            $allocation->getPaidTill()->add(new \DateInterval('P1M'));

            $task = new Task();
            $em->persist($task);
            $task->setServer($allocation->getQuota()->getServer());
            $task->setType(Task::TYPE_ALLOCATION_CREATE);

            $allocation->setTask($task);

            $em->flush();
            
            $task->setParameters(array('allocationId' => $allocation->getId()));
            $billing = $this->get('cherez_web.billing.billing');
            /* @var $billing \CherezWeb\BillingBundle\Service\Billing */
            $billing->makeTransaction($user->getWallet(), -$sum, sprintf('Списание за создание площадки "%s".', $allocation->getName()));
            
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
            sprintf('Запущено создание площадки "%s". После завершения на вашу почту будет отправлено уведомление.', $allocation->getName())
        );
        
        return $this->render('CherezWebDefaultBundle:AjaxResponse:redirect.html.twig');
    }
    
    /**
     * @Security("is_authenticated()")
     */
	public function listAction(Request $request) {
        if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            $allocations = $this->getDoctrine()->getManager()->getRepository('CherezWebHostingBundle:Allocation')->findByUser($this->getUser());
            return $this->render('CherezWebHostingBundle:Allocation:list_ajax.html.twig', array(
                'allocations' => $allocations
            ));
        } else {
            return $this->render('CherezWebHostingBundle:Allocation:list.html.twig');
        }
    }
    
    /**
     * @Security("is_granted('edit', allocation)")
     */
	public function prolongationAction(Allocation $allocation, Request $request) {
        if (count($allocation->getLockingTasks())) {
            return $this->render('CherezWebDefaultBundle:AjaxResponse:message_info.html.twig', array(
                'message' => 'Продление временно недоступно, дождитесь завершения предыдушей задачи.'
            ));
        }
        
        $prolongationForm = $this->createForm(
            new \CherezWeb\HostingBundle\Form\ProlongationType($allocation->getQuota()->getPlan()->getPrice()),
            null,
            array('action' => $request->getUri())
        );
        $prolongationForm->handleRequest($request);
        
        if ($prolongationForm->isValid()) {
            $formData = $prolongationForm->getData();
            $period = $formData['period'];
            
            $newPaidTill = clone $allocation->getPaidTill();
            $newPaidTill->add(new \DateInterval("P{$period}M"));
            
            $prolongationAvailabilityDate = new \DateTime('+13 month');

            if ($newPaidTill > $prolongationAvailabilityDate) {
                $prolongationForm->get('period')->addError(new \Symfony\Component\Form\FormError('Максимальный период оплаты услуги - 1 год.'));
            }
            
            $sum = $allocation->getQuota()->getPlan()->getPrice() * $period;

            $user = $this->getUser();
            /* @var $user User */

            if ($user->getWallet()->getBalance() < $sum) {
                $prolongationForm->addError(new \Symfony\Component\Form\FormError('На вашем счете недостаточно средств.'));
            }
            
            if ($prolongationForm->isValid()) {
                $em = $this->getDoctrine()->getManager();
                /* @var $em \Doctrine\ORM\EntityManager */

                $billing = $this->get('cherez_web.billing.billing');
                /* @var $billing \CherezWeb\BillingBundle\Service\Billing */

                $em->getConnection()->beginTransaction();

                try {
                    $allocation->getQuota()->getServer()->setVersionIncFlag(TRUE);

                    // Снимаем блокировку за неуплату.
                    $lock = $em->getRepository('CherezWebHostingBundle:AllocationLock')
                        ->findOneByAllocationAndType($allocation, AllocationLock::TYPE_NO_PAYMENT);
                    if ($lock !== NULL) {
                        $em->remove($lock);

                        $task = new Task();
                        $em->persist($task);
                        $task->setServer($allocation->getQuota()->getServer());
                        $task->setType(Task::TYPE_ALLOCATION_UPDATE_LOCK);
                        $task->setParameters(array('allocationId' => $allocation->getId()));

                        $allocation->setTask($task);
                    }

                    // Продлеваем.
                    $allocation->setPaidTill($newPaidTill);

                    $billing->makeTransaction($user->getWallet(), -$sum, sprintf('Списание за продление площадки #%s.', $allocation->getId()));

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
                    sprintf('Площадка "%s" успешно продлена.', $allocation->getName())
                );
                
                return $this->render('CherezWebDefaultBundle:AjaxResponse:redirect.html.twig');
            }
        }
        return $this->render('CherezWebHostingBundle:Allocation:prolongation.html.twig', array(
            'allocation' => $allocation,
            'form' => $prolongationForm->createView(),
        ));
    }
    
    /**
     * @Security("is_granted('edit', allocation)")
     */
	public function changePasswordAction(Allocation $allocation) {
        if (count($allocation->getLockingTasks())) {
            return $this->render('CherezWebDefaultBundle:AjaxResponse:message_info.html.twig', array(
                'message' => 'Изменение пароля ssh доступа не выполнено, дождитесь завершения предыдушей задачи.'
            ));
        }

        $em = $this->getDoctrine()->getManager();
        /* @var $em \Doctrine\ORM\EntityManager */

        $em->getConnection()->beginTransaction();
        try {
            $allocation->getQuota()->getServer()->setVersionIncFlag(TRUE);

            $task = new Task();
            $em->persist($task);
            $task->setServer($allocation->getQuota()->getServer());
            $task->setType(Task::TYPE_ALLOCATION_UPDATE_PASSWORD);

            $allocation->setTask($task);

            $em->flush();

            $task->setParameters(array('allocationId' => $allocation->getId()));

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
            sprintf('Запущено изменение пароля ssh доступа на площадке "%s". После завершения на вашу почту будет отправлено уведомление.', $allocation->getName())
        );

        return $this->render('CherezWebDefaultBundle:AjaxResponse:redirect.html.twig');
    }
    
    /**
     * @Security("is_granted('edit', allocation)")
     */
	public function deleteAction(Allocation $allocation, Request $request) {
        $confirmed = (bool)$request->get('confirmed', FALSE);
        
        $now = new \DateTime();
        if ($allocation->getPaidTill() > $now) {
            // При расчете возвратных дней период получается на 1 день меньше,
            // так как текущий день уже не учитывается, он уже не полный.
            $daysLeft = $now->diff($allocation->getPaidTill())->format('%a');
            // Чтобы рассчитать стоимость дня при возврате, нужно делить стоимость
            // периода на максимально возможное число дней в этом периоде.
            $sumToReturn = $allocation->getQuota()->getPlan()->getPrice() / 31 * $daysLeft;
        } else {
            $daysLeft = 0;
            $sumToReturn = 0;
        }
        
        if ($confirmed === FALSE) {
            return $this->render('CherezWebHostingBundle:Allocation:delete.html.twig', array(
                'allocation' => $allocation,
                'daysLeft' => $daysLeft,
                'sumToReturn' => $sumToReturn,
            ));
        } elseif ($confirmed === TRUE) {
            if (count($allocation->getLockingTasks())) {
                return $this->render('CherezWebDefaultBundle:AjaxResponse:message_info.html.twig', array(
                    'message' => 'Удаление площадки не выполнено, дождитесь завершения предыдушей задачи.'
                ));
            }
            
            $em = $this->getDoctrine()->getManager();
            /* @var $em \Doctrine\ORM\EntityManager */

            $em->getConnection()->beginTransaction();
            try {
                // Понижение числа серверов на квоте не критично, потому её
                // версию не меняем.

                // Если во время удаления площадки какой-то из подобъектов
                // сервера был изменен, то операция не пройдет.
                $allocation->getQuota()->getServer()->setVersionIncFlag(TRUE);

                $task = new Task();
                $em->persist($task);
                $task->setServer($allocation->getQuota()->getServer());
                $task->setType(Task::TYPE_ALLOCATION_DELETE);

                $allocation->setTask($task);

                $em->flush();

                $task->setParameters(array('allocationId' => $allocation->getId()));
                if ($sumToReturn > 0) {
                    $billing = $this->get('cherez_web.billing.billing');
                    /* @var $billing \CherezWeb\BillingBundle\Service\Billing */
                    $billing->makeTransaction($allocation->getUser()->getWallet(), $sumToReturn, sprintf('Возврат средств за неиспользованные дни (%s дн.) на площадке "%s".', $daysLeft, $allocation->getName()));
                }
                
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
                sprintf('Запущено удаление площадки "%s". После завершения на вашу почту будет отправлено уведомление.', $allocation->getName())
            );

            return $this->render('CherezWebDefaultBundle:AjaxResponse:redirect.html.twig');
        }
    }
    
    /**
     * @Security("is_granted('edit', allocation)")
     */
	public function editAction(Allocation $allocation) {
        return $this->redirect($this->generateUrl('cherez_web_hosting_cp_database_list',array('allocation' => $allocation->getId())));
    }

}
