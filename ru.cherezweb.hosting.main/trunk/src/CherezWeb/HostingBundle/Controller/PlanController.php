<?php

namespace CherezWeb\HostingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

class PlanController extends Controller{
    
    /**
     * @Security("is_authenticated()")
     */
	public function listAction() {
        $plans = $this->getDoctrine()->getManager()->getRepository('CherezWebHostingBundle:Plan')->findActive();
        return $this->render('CherezWebHostingBundle:Plan:list.html.twig', array(
            'plans' => $plans
        ));
    }
    
}
