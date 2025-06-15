<?php

namespace Arnaud23\AttributeExecutionBundle\Pipeline;

use Symfony\Contracts\Service\ServiceSubscriberInterface;

class AttributeProxy implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly object $inner,
        private readonly AttributeDispatcher $dispatcher
    ) {}

    public function __call(string $method, array $args): mixed
    {
        return $this->dispatcher->dispatch($this->inner, $method, $args);
    }

    public static function getSubscribedServices(): array
    {
        return [];
    }
}