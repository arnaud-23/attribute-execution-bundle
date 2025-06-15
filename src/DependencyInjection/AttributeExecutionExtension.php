<?php

namespace Arnaud23\AttributeExecutionBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class AttributeExecutionExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $container->addCompilerPass(new AutoConfigureAttributeProxyCompilerPass());
        $container->addCompilerPass(new AttributeProxyCompilerPass());
    }
} 