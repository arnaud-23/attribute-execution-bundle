<?php

namespace Arnaud23\AttributeExecutionBundle\Strategy\Transaction;

class TransactionStrategyResolver
{
    public function __construct(private iterable $strategies) {}

    public function resolve(string $name): TransactionStrategyInterface
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($name)) {
                return $strategy;
            }
        }

        throw new \RuntimeException("No transaction strategy found for '{$name}'");
    }
}