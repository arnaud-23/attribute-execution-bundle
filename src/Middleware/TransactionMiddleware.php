<?php

namespace Arnaud23\AttributeExecutionBundle\Middleware;

use Arnaud23\AttributeExecutionBundle\Attribute\Transactional;
use Arnaud23\AttributeExecutionBundle\Pipeline\AttributeMiddlewareInterface;
use Arnaud23\AttributeExecutionBundle\Strategy\Transaction\TransactionStrategyResolver;
use ReflectionClass;

class TransactionMiddleware implements AttributeMiddlewareInterface
{
    public function __construct(private readonly TransactionStrategyResolver $resolver) {}

    /**
     * @param array<string, mixed> $args
     *
     * @throws \ReflectionException
     * @throws \Throwable
     */
    public function process(object $instance, string $method, array $args, callable $next): mixed
    {
        $refClass = new ReflectionClass($instance);
        $refMethod = $refClass->getMethod($method);

        $attributes = array_merge(
            $refMethod->getAttributes(Transactional::class),
            $refClass->getAttributes(Transactional::class),
        );

        if (empty($attributes)) {
            return $next($instance, $method, $args);
        }

        /** @var Transactional $transactional */
        $transactional = $attributes[0]->newInstance();
        $strategy = $this->resolver->resolve($transactional->connection);

        $strategy->begin();
        try {
            $result = $next($instance, $method, $args);
            $strategy->commit();
            return $result;
        } catch (\Throwable $e) {
            $strategy->rollback();
            throw $e;
        }
    }
}