<?php

namespace CherezWeb\HostingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use CherezWeb\HostingBundle\Entity\User;
use CherezWeb\HostingBundle\Entity\DomainBase;

class DomainBaseController extends Controller{
    
    /**
     * @Security("is_authenticated()")
     */
	public function listAction(Request $request) {
        $user = $this->getUser();
        /* @var $user User */
        $em = $this->getDoctrine()->getManager();
        /* @var $em \Doctrine\ORM\EntityManager */
        if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            $domainBases = $em->getRepository('CherezWebHostingBundle:DomainBase')->findByUser($user);
            
            // При выводе списка каждый раз пытаемся подтвердить требующие подтверждения домены.
            foreach ($domainBases as $domainBase) {
                /* @var $domainBase DomainBase */
                if ($domainBase->getState() === DomainBase::STATE_CONFIRMATION) {
                    $dns = @dns_get_record($domainBase->getConfirmationHost(), DNS_CNAME);
                    $dnsHost = @$dns[0]['host'];
                    $dnsTarget = @$dns[0]['target'];
                    $dnsType = @$dns[0]['type'];

                    if (
                            strcasecmp($dnsHost, $domainBase->getConfirmationHost()) === 0
                        &&
                            strcasecmp($dnsTarget, 'hosting.cherezweb.ru') === 0
                        &&
                            strcasecmp($dnsType, 'CNAME') === 0
                    ) {
                        // Если домен подтвержден, деактивируем активные и активирующиеся домены.
                        $domainBasesToDeactivate = $em->getRepository('CherezWebHostingBundle:DomainBase')
                            ->findBy(array(
                                'name' => $domainBase->getName(),
                                'state' => array(
                                    DomainBase::STATE_ACTIVATION,
                                    DomainBase::STATE_ACTIVE
                                )
                            ));
                        foreach ($domainBasesToDeactivate as $domainBaseToDeactivate) {
                            /* @var $domainBaseToDeactivate DomainBase */
                            $domainBaseToDeactivate->setState(DomainBase::STATE_DEACTIVATION);
                        }
                        // Сам домен переводим в состояние активации.
                        $domainBase->setState(DomainBase::STATE_ACTIVATION);
                        
                        // Обновляем базовые DNS записи.
                        $this->get('cherez_web.hosting.dns_record_manager')
                            ->initBaseDomainRecords($domainBase);
                        
                        try {
                            $em->flush();
                        } catch(\Doctrine\ORM\OptimisticLockException $e) {
                            // Если очередной domainBase не удалось изменить,
                            // возвращаем его в прежнее состояние и выводим список,
                            // обновление должно будет пройти в следующее обновление списка.
                            $em->refresh($domainBase);
                            return $this->render('CherezWebHostingBundle:DomainBase:list_ajax.html.twig', array(
                                'domainBases' => $domainBases,
                            ));
                        }
                    }
                }
            }
            return $this->render('CherezWebHostingBundle:DomainBase:list_ajax.html.twig', array(
                'domainBases' => $domainBases,
            ));
        } else {
            return $this->render('CherezWebHostingBundle:DomainBase:list.html.twig');
        }
    }
    
    /**
     * @Security("is_authenticated()")
     */
	public function createAction(Request $request) {
        $user = $this->getUser();
        /* @var $user User */
        $em = $this->getDoctrine()->getManager();
        /* @var $em \Doctrine\ORM\EntityManager */
        
        // Лимит на число domainBase на площадке.
        $domainBaseNumLimit = 100;
        if (count($em->getRepository('CherezWebHostingBundle:DomainBase')->findByUser($user)) >= $domainBaseNumLimit) {
            return $this->render('CherezWebDefaultBundle:AjaxResponse:message_warning.html.twig', array(
                'message' => sprintf('Вы достигли максимального числа доменов (%s) на своем аккаунте.', $domainBaseNumLimit)
            ));
        }
        
        $domainBase = new DomainBase();
        $domainBase->setUser($user);
        
        $domainBaseEditForm = $this->createForm(
            new \CherezWeb\HostingBundle\Form\DomainBaseEditType(),
            $domainBase,
            array('action' => $request->getUri())
        );
        $domainBaseEditForm->handleRequest($request);
        if ($domainBaseEditForm->isValid()) {
            // Если домен встречается впервые в ситсеме, то добавляем сразу активный домен.
            // Активный домен получим только в том случае, если домен встречается впервые,
            // так как в валидатор не пустит нас сюда, если у пользователя уже есть такой домен.
            $activeDomainBase = $this->get('cherez_web.hosting.domain_base_manager')->findOrInitActive($user, $domainBase->getName());
            /* @var $activeDomainBase \CherezWeb\HostingBundle\Entity\DomainBase */

            if ($activeDomainBase === NULL) {
                // Если домен в системе уже присутствует, то пользуемся тем доменом,
                // который создали.
                $em->persist($domainBase);
            } else {
                // Если создался новый подтвержденный домен, то далее пользуемся им.
                $domainBase = $activeDomainBase;
            }
            
            // Создается новый базовый домен, потому прблем с одновременным
            // редактированием нет.
            $em->flush();

            $this->get('session')->getFlashBag()->add(
                'notice_success',
                sprintf('Домен "%s" добавлен на ваш аккаунт.', $domainBase->getName())
            );

            return $this->render('CherezWebDefaultBundle:AjaxResponse:redirect.html.twig');
        }
        
        return $this->render('CherezWebHostingBundle:DomainBase:create.html.twig', array(
            'form' => $domainBaseEditForm->createView(),
		));
    }
    
    /**
     * @Security("is_granted('edit', domainBase)")
     */
	public function deleteAction(DomainBase $domainBase, Request $request) {
        $confirmed = (bool)$request->get('confirmed', FALSE);
        
        if ($confirmed === FALSE) {
            return $this->render('CherezWebHostingBundle:DomainBase:delete.html.twig', array('domainBase' => $domainBase));
        } elseif ($confirmed === TRUE) {
            // TODO: Проверка подходящего состояния.
            
            $em = $this->getDoctrine()->getManager();
            /* @var $em \Doctrine\ORM\EntityManager */
            
            if ($domainBase->getState() === DomainBase::STATE_INACTIVE) {
                $em->remove($domainBase);
                $message = sprintf('Домен "%s" удален с вашего аккаунта.', $domainBase->getName());
            } else {
                $domainBase->setState(DomainBase::STATE_DEACTIVATION);
                $message = sprintf('Домен "%s" отправлен на деактивацию (будет отвязан от всех площадок). После завершения вы сможете его удалить.', $domainBase->getName());
            }
            
            try {
                $em->flush();
            } catch(\Doctrine\ORM\OptimisticLockException $e) {
                return $this->render('CherezWebDefaultBundle:AjaxResponse:message_warning.html.twig', array(
                    'message' => 'Произошла попытка одновременного доступа к одной записи. Повторите вашу попытку.'
                ));
            }

            $this->get('session')->getFlashBag()->add(
                'notice_success',
                $message
            );

            return $this->render('CherezWebDefaultBundle:AjaxResponse:redirect.html.twig');
        }
    }
    
}
