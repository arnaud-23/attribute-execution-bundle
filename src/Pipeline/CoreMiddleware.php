<?php

namespace Arnaud23\AttributeExecutionBundle\Pipeline;

class CoreMiddleware implements AttributeMiddlewareInterface
{
    /**
     * @param array<string, mixed> $args
     */
    public function process(object $instance, string $method, array $args, callable $next): mixed
    {
        return $instance->$method(...$args);
    }
} 