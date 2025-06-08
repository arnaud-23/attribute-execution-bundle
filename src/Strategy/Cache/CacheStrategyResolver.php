<?php

namespace Arnaud23\AttributeExecutionBundle\Strategy\Cache;

class CacheStrategyResolver
{
    public function __construct(private iterable $strategies) {}

    public function resolve(string $name): CacheStrategyInterface
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($name)) {
                return $strategy;
            }
        }

        throw new \RuntimeException("No cache strategy found for '{$name}'");
    }
}