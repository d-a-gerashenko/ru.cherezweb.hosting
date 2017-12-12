<?php

namespace CherezWeb\HostingBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class TaskProcessorCompilerPass implements CompilerPassInterface {

    public function process(ContainerBuilder $container) {
        if (!$container->has('cherez_web.hosting.task_manager')) {
            return;
        }

        $definition = $container->findDefinition(
                'cherez_web.hosting.task_manager'
        );

        $taggedServices = $container->findTaggedServiceIds(
                'cherez_web.hosting.task_processor'
        );
        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall(
                'addTaskProcessor',
                array(new Reference($id))
            );
        }
    }

}
