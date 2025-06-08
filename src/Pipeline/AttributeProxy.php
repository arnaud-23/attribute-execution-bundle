<?php

namespace Arnaud23\AttributeExecutionBundle\Pipeline;

use Symfony\Contracts\Service\ServiceSubscriberInterface;

class AttributeProxy implements ServiceSubscriberInterface
{
    public function __construct(
        private object $inner,
        private AttributeDispatcher $dispatcher
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