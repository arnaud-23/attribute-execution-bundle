<?php

namespace Arnaud23\AttributeExecutionBundle\Strategy\Cache;

class ArrayCacheStrategy implements CacheStrategyInterface
{
    private array $cache = [];

    public function supports(string $name): bool
    {
        return $name === 'array';
    }

    public function get(string $key): mixed
    {
        return $this->cache[$key]['value'] ?? null;
    }

    public function set(string $key, mixed $value, int $ttl): void
    {
        $this->cache[$key] = [
            'value' => $value,
            'expires_at' => time() + $ttl
        ];
    }
}