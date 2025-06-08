<?php

namespace Arnaud23\AttributeExecutionBundle\Middleware;

use Arnaud23\AttributeExecutionBundle\Attribute\Secure;
use Arnaud23\AttributeExecutionBundle\Pipeline\AttributeMiddlewareInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use ReflectionClass;

class SecurityMiddleware implements AttributeMiddlewareInterface
{
    public function __construct(private AuthorizationCheckerInterface $checker) {}

    public function process(object $instance, string $method, array $args, callable $next): mixed
    {
        $refClass = new ReflectionClass($instance);
        $refMethod = $refClass->getMethod($method);

        $attributes = array_merge(
            $refClass->getAttributes(Secure::class),
            $refMethod->getAttributes(Secure::class)
        );

        if (empty($attributes)) {
            return $next($instance, $method, $args);
        }

        $secure = $attributes[0]->newInstance();

        if (!$this->checker->isGranted($secure->role)) {
            throw new AccessDeniedException("Access denied: requires role {$secure->role}");
        }

        return $next($instance, $method, $args);
    }
}