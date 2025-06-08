<?php

namespace Arnaud23\AttributeExecutionBundle\Pipeline;

class AttributeDispatcher
{
    public function __construct(private iterable $middlewares) {}

    public function dispatch(object $instance, string $method, array $args = []): mixed
    {
        $pipeline = new AttributePipeline(iterator_to_array($this->middlewares));
        return $pipeline->handle($instance, $method, $args);
    }
}