<?php

namespace Arnaud23\AttributeExecutionBundle\Middleware;

use Arnaud23\AttributeExecutionBundle\Attribute\Cache;
use Arnaud23\AttributeExecutionBundle\Pipeline\AttributeMiddlewareInterface;
use Arnaud23\AttributeExecutionBundle\Strategy\Cache\CacheStrategyResolver;
use ReflectionClass;
use ReflectionMethod;

class CacheMiddleware implements AttributeMiddlewareInterface
{
    public function __construct(private CacheStrategyResolver $resolver) {}

    public function process(object $instance, string $method, array $args, callable $next): mixed
    {
        $refClass = new ReflectionClass($instance);
        $refMethod = $refClass->getMethod($method);

        $attributes = array_merge(
            $refClass->getAttributes(Cache::class),
            $refMethod->getAttributes(Cache::class)
        );

        if (empty($attributes)) {
            return $next($instance, $method, $args);
        }

        /** @var Cache $cacheAttr */
        $cacheAttr = $attributes[0]->newInstance();
        $strategy = $this->resolver->resolve($cacheAttr->strategy);

        $cacheKey = get_class($instance) . "::" . $method . ":" . md5(serialize($args));
        if ($result = $strategy->get($cacheKey)) {
            return $result;
        }

        $result = $next($instance, $method, $args);
        $strategy->set($cacheKey, $result, $cacheAttr->ttl);
        return $result;
    }
}