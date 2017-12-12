<?php

namespace CherezWeb\HostingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ServiceController extends Controller {

    public function indexAction() {
        return $this->render('CherezWebHostingBundle:Service:index.html.twig');
    }
    
    public function termsOfServiceAction() {
        return $this->render('CherezWebHostingBundle:Service:terms_of_service.html.twig');
    }
    
    public function rulesOfServiceAction() {
        return $this->render('CherezWebHostingBundle:Service:rules_of_service.html.twig');
    }
    
    public function aboutAction() {
        return $this->render('CherezWebHostingBundle:Service:about.html.twig');
    }
    
    public function relocationAction() {
        return $this->render('CherezWebHostingBundle:Service:relocation.html.twig');
    }
    
    public function pricingAction() {
        $plans = $this->getDoctrine()->getManager()->getRepository('CherezWebHostingBundle:Plan')->findActive();
        return $this->render('CherezWebHostingBundle:Service:pricing.html.twig', array(
            'plans' => $plans
        ));
    }
    
    public function supportAction(Request $request) {
        $user = $this->getUser();
        /* @var $user \CherezWeb\HostingBundle\Entity\User */
        $supportForm = $this->createForm(
            new \CherezWeb\HostingBundle\Form\SupportType($user !== NULL),
            null,
            array('action' => $request->getUri())
        );
        
        $supportForm->handleRequest($request);
        
        if ($supportForm->isValid()) {
            $formData = $supportForm->getData();
            
            $this->get('cherez_web.default.mailer')->sendMail(
                'Сообщение через форму обратной связи с хостинга',
                'support@cherezweb.ru',
                'CherezWebHostingBundle:Email:support',
                array(
                    'name' => $formData['name'],
                    'email' => (isset($formData['email']))?$formData['email']:$user->getEmail(),
                    'message' => $formData['message'],
                )
            );

            $this->get('session')->getFlashBag()->add(
                'notice_info',
                'Ваше сообщение отправлено.'
            );
            return $this->redirect($request->headers->get('referer'));
        }
        return $this->render('CherezWebHostingBundle:Service:support.html.twig', array (
            'form' => $supportForm->createView(),
        ));
    }
    
}
