<?php

namespace Arnaud23\AttributeExecutionBundle\Pipeline;

class AttributeDispatcher
{
    /**
     * @param iterable<AttributeMiddlewareInterface> $middlewares
     */
    public function __construct(private readonly iterable $middlewares) {}

    /**
     * @param array<string, mixed> $args
     */
    public function dispatch(object $instance, string $method, array $args = []): mixed
    {
        $pipeline = new AttributePipeline(iterator_to_array($this->middlewares));

        return $pipeline->handle($instance, $method, $args);
    }
}