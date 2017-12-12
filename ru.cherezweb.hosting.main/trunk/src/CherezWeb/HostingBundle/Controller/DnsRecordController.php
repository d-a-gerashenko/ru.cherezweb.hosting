<?php

namespace CherezWeb\HostingBundle\Controller;

use CherezWeb\HostingBundle\Entity\DnsRecord;
use CherezWeb\HostingBundle\Entity\DomainBase;
use CherezWeb\HostingBundle\Form\DnsRecordEditType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DnsRecordController extends Controller{
    
    /**
     * @Security("is_granted('edit', domainBase)")
     */
	public function listAction(DomainBase $domainBase, Request $request) {
        if ($domainBase->getState() !== DomainBase::STATE_ACTIVE) {
            $this->get('session')->getFlashBag()->add(
                'notice_warning',
                sprintf('Для редактирования DNS записей домена "%s", домен должен быть активен.', $domainBase->getName())
            );
            $redirectUrl = $this->generateUrl('cherez_web_hosting_cp_domainbase_list');
            if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
                return $this->render('CherezWebDefaultBundle:AjaxResponse:redirect.html.twig', array(
                    'redirectUrl' => $redirectUrl
                ));
            } else {
                return $this->redirect($redirectUrl);
            }
        }
        
        if ($request->headers->get('X-Requested-With') === 'XMLHttpRequest') {
            $dnsRecords = $this->getDoctrine()->getManager()->getRepository('CherezWebHostingBundle:DnsRecord')->findByDomainBase($domainBase);
            return $this->render('CherezWebHostingBundle:DnsRecord:list_ajax.html.twig', array(
                'dnsRecords' => $dnsRecords,
            ));
        } else {
            return $this->render('CherezWebHostingBundle:DnsRecord:list.html.twig', array ('domainBase' => $domainBase));
        }
    }
    
    /**
     * @Security("is_granted('edit', domainBase)")
     */
	public function createAction(DomainBase $domainBase, $recordType, Request $request) {
        if ($domainBase->getState() !== DomainBase::STATE_ACTIVE) {
            return $this->render('CherezWebDefaultBundle:AjaxResponse:message_warning.html.twig', array(
                'message' => sprintf('Для редактирования DNS записей домена "%s", домен должен быть активен.', $domainBase->getName())
            ));
        }
        
        $em = $this->getDoctrine()->getManager();
        /* @var $em \Doctrine\ORM\EntityManager */
        
        $dnsRecord = new DnsRecord();
        $dnsRecord->setDomainBaseName($domainBase->getName());
        $dnsRecord->setType($recordType);

        $dnsRecordEditForm = $this->createForm(
            new DnsRecordEditType($domainBase->getName(), $recordType),
            $dnsRecord,
            array('action' => $request->getUri())
        );
        $dnsRecordEditForm->handleRequest($request);
        if ($dnsRecordEditForm->isValid()) {
            $em->persist($dnsRecord);
            $em->flush();
            $this->get('session')->getFlashBag()->add(
                'notice_success',
                sprintf('DNS запись #%s успешно добавлена.', $dnsRecord->getId())
            );

            return $this->render('CherezWebDefaultBundle:AjaxResponse:redirect.html.twig');
        }

        return $this->render('CherezWebHostingBundle:DnsRecord:create.html.twig', array(
            'form' => $dnsRecordEditForm->createView(),
            'domainBaseName' => $domainBase->getName(),
            'recordType' => $recordType,
		));
    }
    
    /**
     * @Security("is_granted('edit', dnsRecord)")
     */
	public function editAction(DnsRecord $dnsRecord, Request $request) {
        $dnsRecordEditForm = $this->createForm(
            new DnsRecordEditType($dnsRecord->getDomainBaseName(), $dnsRecord->getType()),
            $dnsRecord,
            array('action' => $request->getUri())
        );
        $dnsRecordEditForm->handleRequest($request);
        if ($dnsRecordEditForm->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $this->get('session')->getFlashBag()->add(
                'notice_success',
                sprintf('DNS запись #%s успешно отредактирована.', $dnsRecord->getId())
            );
            
            return $this->render('CherezWebDefaultBundle:AjaxResponse:redirect.html.twig');
        }

        return $this->render('CherezWebHostingBundle:DnsRecord:edit.html.twig', array(
            'form' => $dnsRecordEditForm->createView(),
            'dnsRecord' => $dnsRecord,
            'recordType' => $dnsRecord->getType(),
		));
    }
    
    /**
     * @Security("is_granted('delete', dnsRecord)")
     */
	public function deleteAction(DnsRecord $dnsRecord, Request $request) {
        $confirmed = (bool)$request->get('confirmed', FALSE);
        
        if ($confirmed === FALSE) {
            return $this->render('CherezWebHostingBundle:DnsRecord:delete.html.twig', array('dnsRecord' => $dnsRecord));
        } elseif ($confirmed === TRUE) {
            $em = $this->getDoctrine()->getManager();
            /* @var $em \Doctrine\ORM\EntityManager */
            
            $dnsRecord->setIsDeleted(true);
            $dnsRecord->updateSyncPos();
            
            $message = sprintf('DNS запись #%s для хоста "%s" удален с вашего аккаунта.', $dnsRecord->getId(), $dnsRecord->getHost());
            
            $em->flush();

            $this->get('session')->getFlashBag()->add(
                'notice_success',
                $message
            );

            return $this->render('CherezWebDefaultBundle:AjaxResponse:redirect.html.twig');
        }
    }
    
}
