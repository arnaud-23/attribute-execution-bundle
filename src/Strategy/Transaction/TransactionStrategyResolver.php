<?php

namespace Arnaud23\AttributeExecutionBundle\Strategy\Transaction;

class TransactionStrategyResolver
{
    /**
     * @param iterable<TransactionStrategyInterface> $strategies
     */
    public function __construct(private readonly iterable $strategies) {}

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