<?php

namespace Arnaud23\AttributeExecutionBundle\DependencyInjection;

use Arnaud23\AttributeExecutionBundle\Attribute\Cache;
use Arnaud23\AttributeExecutionBundle\Attribute\Security;
use Arnaud23\AttributeExecutionBundle\Attribute\Transactional;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AutoConfigureAttributeProxyCompilerPass implements CompilerPassInterface
{
    private const ATTRIBUTES = [
        Cache::class,
        Security::class,
        Transactional::class,
    ];

    public function process(ContainerBuilder $container): void
    {
        foreach ($container->getDefinitions() as $id => $definition) {
            if ($definition->hasTag('attribute_proxy')) {
                continue;
            }

            $class = $definition->getClass();
            if (!$class || !class_exists($class)) {
                continue;
            }

            // Fast pre-check: look for attribute names in the file content
            $reflectionClass = new ReflectionClass($class);
            $fileName = $reflectionClass->getFileName();
            
            if (!$fileName || !file_exists($fileName)) {
                continue;
            }

            $fileContent = file_get_contents($fileName);
            if (!$fileContent) {
                continue;
            }

            // Quick string check for attribute names before using reflection
            $hasAttributes = false;
            foreach (self::ATTRIBUTES as $attribute) {
                $shortName = (new ReflectionClass($attribute))->getShortName();
                if (str_contains($fileContent, $shortName)) {
                    $hasAttributes = true;
                    break;
                }
            }

            if (!$hasAttributes) {
                continue;
            }

            // Only use reflection if we found attribute names in the file
            foreach (self::ATTRIBUTES as $attribute) {
                if (!empty($reflectionClass->getAttributes($attribute))) {
                    $definition->addTag('attribute_proxy');
                    continue 2;
                }
            }

            foreach ($reflectionClass->getMethods() as $method) {
                foreach (self::ATTRIBUTES as $attribute) {
                    if (!empty($method->getAttributes($attribute))) {
                        $definition->addTag('attribute_proxy');
                        continue 3;
                    }
                }
            }
        }
    }
} 