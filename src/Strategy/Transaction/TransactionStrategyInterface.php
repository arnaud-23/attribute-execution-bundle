<?php

namespace Arnaud23\AttributeExecutionBundle\Strategy\Transaction;

interface TransactionStrategyInterface
{
    public function supports(string $name): bool;
    public function begin(): void;
    public function commit(): void;
    public function rollback(): void;
}