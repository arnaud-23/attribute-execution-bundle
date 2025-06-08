<?php

namespace Arnaud23\AttributeExecutionBundle\DependencyInjection;

use Arnaud23\AttributeExecutionBundle\Pipeline\AttributeProxy;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AttributeProxyCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->findTaggedServiceIds('attribute_proxy') as $id => $tags) {
            $decoratorId = $id . '.attribute_proxy';

            $container->register($decoratorId, AttributeProxy::class)
                ->setDecoratedService($id)
                ->setArguments([
                    new Reference($decoratorId . '.inner'),
                    new Reference('Arnaud23\AttributeExecutionBundle\Pipeline\AttributeDispatcher'),
                ]);
        }
    }
}