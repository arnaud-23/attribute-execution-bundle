<?php

namespace Arnaud23\AttributeExecutionBundle\Tests\Middleware;

use Arnaud23\AttributeExecutionBundle\Attribute\Transactional;
use Arnaud23\AttributeExecutionBundle\Middleware\TransactionMiddleware;
use Arnaud23\AttributeExecutionBundle\Strategy\Transaction\TransactionStrategyInterface;
use Arnaud23\AttributeExecutionBundle\Strategy\Transaction\TransactionStrategyResolver;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TransactionMiddlewareTest extends TestCase
{
    private TransactionStrategyInterface|MockObject $strategy;
    private TransactionMiddleware $middleware;

    protected function setUp(): void
    {
        $this->strategy = $this->createMock(TransactionStrategyInterface::class);
        $this->middleware = new TransactionMiddleware(
            resolver: new TransactionStrategyResolver([$this->strategy])
        );
    }

    #[Test]
    public function transactional_wrapping_on_class(): void
    {
        $this->strategy->method('supports')->willReturn(true);
        $this->strategy->expects($this->once())->method('begin');
        $this->strategy->expects($this->once())->method('commit');
        $this->strategy->expects($this->never())->method('rollback');

        $service = new #[Transactional()] class {
            public function run(): string { return 'done'; }
        };

        $result = $this->middleware->process(instance:$service, method:'run', args:[], next:fn () => 'next');
        $this->assertEquals('next', $result);
    }

    #[Test]
    public function transactional_wrapping_on_method(): void
    {
        $this->strategy->method('supports')->willReturn(true);
        $this->strategy->expects($this->once())->method('begin');
        $this->strategy->expects($this->once())->method('commit');
        $this->strategy->expects($this->never())->method('rollback');

        $service = new class {
            #[Transactional()]
            public function run(): string { return 'done'; }
        };

        $result = $this->middleware->process(instance:$service, method:'run', args:[], next:fn () => 'next');
        $this->assertEquals('next', $result);
    }

    #[Test]
    public function no_transaction_when_no_attribute(): void
    {
        $this->strategy->expects($this->never())->method('begin');
        $this->strategy->expects($this->never())->method('commit');
        $this->strategy->expects($this->never())->method('rollback');

        $service = new class {
            public function run(): string { return 'done'; }
        };

        $result = $this->middleware->process(instance:$service, method:'run', args:[], next:fn () => 'next');
        $this->assertEquals('next', $result);
    }

    #[Test]
    public function rollback_on_exception(): void
    {
        $this->strategy->method('supports')->willReturn(true);
        $this->strategy->expects($this->once())->method('begin');
        $this->strategy->expects($this->never())->method('commit');
        $this->strategy->expects($this->once())->method('rollback');

        $service = new #[Transactional()] class {
            public function run(): never { throw new \RuntimeException('Test exception'); }
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Test exception');

        $this->middleware->process(instance:$service, method:'run', args:[], next:fn () => throw new \RuntimeException('Test exception'));
    }

    #[Test]
    public function custom_connection_name(): void
    {
        $this->strategy->method('supports')->willReturn(true);
        $this->strategy->expects($this->once())->method('begin');
        $this->strategy->expects($this->once())->method('commit');
        $this->strategy->expects($this->never())->method('rollback');

        $service = new #[Transactional('custom_connection')] class {
            public function run(): string { return 'done'; }
        };

        $result = $this->middleware->process(instance:$service, method:'run', args:[], next:fn () => 'next');
        $this->assertEquals('next', $result);
    }

    #[Test]
    public function method_attribute_takes_precedence_over_class(): void
    {
        $this->strategy->expects($this->once())
            ->method('supports')
            ->with('method_connection')
            ->willReturn(true);
        $this->strategy->expects($this->once())->method('begin');
        $this->strategy->expects($this->once())->method('commit');
        $this->strategy->expects($this->never())->method('rollback');

        $service = new #[Transactional('class_connection')] class {
            #[Transactional('method_connection')]
            public function run(): string { return 'done'; }
        };

        $result = $this->middleware->process(instance:$service, method:'run', args:[], next:fn () => 'next');
        $this->assertEquals('next', $result);
    }
}