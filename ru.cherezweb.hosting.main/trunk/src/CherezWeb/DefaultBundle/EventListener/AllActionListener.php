<?php

namespace CherezWeb\DefaultBundle\EventListener;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AllActionListener {

    public function onKernelController(FilterControllerEvent $event) {
        if (!$this->filter($event)) {
            return;
        }

        //Перенаправляем домены 2-го уровня на "www.<домен>".
        $host = mb_strtolower($event->getRequest()->getHost());
        $hostPices = mb_split('\.', $host);
        $requestUri = $event->getRequest()->getRequestUri();

        if(count($hostPices) == 2) {
            $redirectUrl  = 'http://www.'.$host.$requestUri;
            $event->setController(function() use ($redirectUrl) {
                return new RedirectResponse($redirectUrl);
            });
        }
    }

    public function onKernelResponse(FilterResponseEvent $event) {}
    
    private function filter($event){
        if (!method_exists($event, 'getController')) {
            return false;
        }
        $controller = $event->getController();

        /*
         * $controller passed can be either a class or a Closure. This is not usual in Symfony2 but it may happen.
         * If it is a class, it comes in array format
         */
        if (!is_array($controller)) {
            return false;
        }

        if (!($controller[0] instanceof Controller)) {
            return false;
        }
        return true;
    }
}

?>
