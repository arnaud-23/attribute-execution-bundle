<?php

namespace Arnaud23\AttributeExecutionBundle\Middleware;

use Arnaud23\AttributeExecutionBundle\Attribute\Security;
use Arnaud23\AttributeExecutionBundle\Pipeline\AttributeMiddlewareInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use ReflectionClass;

class SecurityMiddleware implements AttributeMiddlewareInterface
{
    public function __construct(private readonly AuthorizationCheckerInterface $checker) {}

    /**
     * @param array<string, mixed> $args
     *
     * @throws \ReflectionException
     */
    public function process(object $instance, string $method, array $args, callable $next): mixed
    {
        $refClass = new ReflectionClass($instance);
        $refMethod = $refClass->getMethod($method);

        $attributes = array_merge(
            $refMethod->getAttributes(Security::class),
            $refClass->getAttributes(Security::class),
        );

        if (empty($attributes)) {
            return $next($instance, $method, $args);
        }

        $security = $attributes[0]->newInstance();

        if (!$this->checker->isGranted($security->role)) {
            throw new AccessDeniedException("Access denied: requires role {$security->role}");
        }

        return $next($instance, $method, $args);
    }
}