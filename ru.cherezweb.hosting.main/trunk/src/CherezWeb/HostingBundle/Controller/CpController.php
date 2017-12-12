<?php

namespace CherezWeb\HostingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

class CpController extends Controller{
    
    /**
     * @Security("is_authenticated()")
     */
	public function indexAction(Request $request) {
        return $this->redirect($this->generateUrl('cherez_web_hosting_cp_allocation_list'));
    }
    
    /**
     * @Security("is_authenticated()")
     */
    public function accountSettingsAction() {
        return $this->render('CherezWebHostingBundle:Cp:account_settings.html.twig');
    }
    
    /**
     * @Security("is_authenticated()")
     */
    public function billingAction() {
        return $this->render('CherezWebHostingBundle:Cp:billing.html.twig');
    }

}
