<?php

namespace Arnaud23\AttributeExecutionBundle\Pipeline;

class AttributePipeline
{
    public function __construct(private array $middlewares) {}

    public function handle(object $instance, string $method, array $args): mixed
    {
        $core = fn($instance, $method, $args) => $instance->$method(...$args);

        $pipeline = array_reduce(
            array_reverse($this->middlewares),
            fn($next, $middleware) => fn($i, $m, $a) => $middleware->process($i, $m, $a, $next),
            $core
        );

        return $pipeline($instance, $method, $args);
    }
}