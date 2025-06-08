<?php

namespace Arnaud23\AttributeExecutionBundle\Pipeline;

interface AttributeMiddlewareInterface
{
    public function process(object $instance, string $method, array $args, callable $next): mixed;
}