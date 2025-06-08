<?php

namespace Arnaud23\AttributeExecutionBundle\Strategy\Transaction;

use Doctrine\ORM\EntityManagerInterface;

class DoctrineTransactionStrategy implements TransactionStrategyInterface
{
    public function __construct(private EntityManagerInterface $em, private string $name) {}

    public function supports(string $name): bool
    {
        return $this->name === $name;
    }

    public function begin(): void
    {
        $this->em->beginTransaction();
    }

    public function commit(): void
    {
        $this->em->commit();
    }

    public function rollback(): void
    {
        $this->em->rollback();
    }
}