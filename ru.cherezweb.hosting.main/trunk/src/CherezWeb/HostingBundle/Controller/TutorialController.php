<?php

namespace CherezWeb\HostingBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use CherezWeb\HostingBundle\Entity\Tutorial;

class TutorialController extends Controller {

    public function indexAction() {
        $tutorials = $this->getDoctrine()->getManager()->getRepository('CherezWebHostingBundle:Tutorial')->findBy(array(),array('title' => 'ASC'));
        return $this->render('CherezWebHostingBundle:Tutorial:index.html.twig', array(
            'tutorials' => $tutorials
        ));
    }
    
    public function showAction(Tutorial $tutorial) {
        return $this->render('CherezWebHostingBundle:Tutorial:show.html.twig', array(
            'tutorial' => $tutorial,
        ));
    }
    
    public function sidebarBlockAction() {
        $tutorials = $this->getDoctrine()->getManager()->getRepository('CherezWebHostingBundle:Tutorial')->findRand(5);
        
        return $this->render('CherezWebHostingBundle:Tutorial:sidebar_block.html.twig', array(
            'tutorials' => $tutorials
        ));
    }
    
}
