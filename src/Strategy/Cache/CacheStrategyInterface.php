<?php

namespace Arnaud23\AttributeExecutionBundle\Strategy\Cache;

interface CacheStrategyInterface
{
    public function supports(string $name): bool;
    public function get(string $key): mixed;
    public function set(string $key, mixed $value, int $ttl): void;
}