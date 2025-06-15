<?php

namespace Arnaud23\AttributeExecutionBundle\Pipeline;

class AttributePipeline
{
    /**
     * @param array<AttributeMiddlewareInterface> $middlewares
     */
    public function __construct(private readonly array $middlewares) {}

    public function handle(object $instance, string $method, array $args): mixed
    {
        // Add the core middleware at the end of the pipeline
        $pipeline = array_reduce(
            array_reverse([...$this->middlewares, new CoreMiddleware()]),
            static fn($next, $middleware) => static fn($i, $m, $a) => $middleware->process($i, $m, $a, $next),
            static fn($i, $m, $a) => null // This will never be called as CoreMiddleware is last
        );

        return $pipeline($instance, $method, $args);
    }
}