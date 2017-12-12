<?php

namespace CherezWeb\HostingBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use CherezWeb\HostingBundle\DependencyInjection\Compiler\TaskProcessorCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CherezWebHostingBundle extends Bundle {

    public function build(ContainerBuilder $container) {
        parent::build($container);

        $container->addCompilerPass(new TaskProcessorCompilerPass());
    }

}
