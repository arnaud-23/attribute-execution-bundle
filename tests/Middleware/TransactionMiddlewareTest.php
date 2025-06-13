<?php

namespace Arnaud23\AttributeExecutionBundle\Tests\Middleware;

use Arnaud23\AttributeExecutionBundle\Attribute\Transactional;
use Arnaud23\AttributeExecutionBundle\Middleware\TransactionMiddleware;
use Arnaud23\AttributeExecutionBundle\Strategy\Transaction\TransactionStrategyInterface;
use Arnaud23\AttributeExecutionBundle\Strategy\Transaction\TransactionStrategyResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class TransactionMiddlewareTest extends TestCase
{
    private TransactionStrategyInterface|MockObject $strategy;
    private TransactionStrategyResolver $resolver;
    private TransactionMiddleware $middleware;

    protected function setUp(): void
    {
        $this->strategy = $this->createMock(TransactionStrategyInterface::class);
        $this->resolver = new TransactionStrategyResolver([$this->strategy]);
        $this->middleware = new TransactionMiddleware($this->resolver);
    }

    public function test_transactional_wrapping_on_class()
    {
        $this->strategy->method('supports')->willReturn(true);
        $this->strategy->expects($this->once())->method('begin');
        $this->strategy->expects($this->once())->method('commit');
        $this->strategy->expects($this->never())->method('rollback');

        $service = new #[Transactional()] class {
            public function run() { return 'done'; }
        };

        $result = $this->middleware->process($service, 'run', [], fn () => 'done');
        $this->assertEquals('done', $result);
    }

    public function test_transactional_wrapping_on_method()
    {
        $this->strategy->method('supports')->willReturn(true);
        $this->strategy->expects($this->once())->method('begin');
        $this->strategy->expects($this->once())->method('commit');
        $this->strategy->expects($this->never())->method('rollback');

        $service = new class {
            #[Transactional()]
            public function run() { return 'done'; }
        };

        $result = $this->middleware->process($service, 'run', [], fn () => 'done');
        $this->assertEquals('done', $result);
    }

    public function test_no_transaction_when_no_attribute()
    {
        $this->strategy->expects($this->never())->method('begin');
        $this->strategy->expects($this->never())->method('commit');
        $this->strategy->expects($this->never())->method('rollback');

        $service = new class {
            public function run() { return 'done'; }
        };

        $result = $this->middleware->process($service, 'run', [], fn () => 'done');
        $this->assertEquals('done', $result);
    }

    public function test_rollback_on_exception()
    {
        $this->strategy->method('supports')->willReturn(true);
        $this->strategy->expects($this->once())->method('begin');
        $this->strategy->expects($this->never())->method('commit');
        $this->strategy->expects($this->once())->method('rollback');

        $service = new #[Transactional()] class {
            public function run() { throw new \RuntimeException('Test exception'); }
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Test exception');

        $this->middleware->process($service, 'run', [], fn () => throw new \RuntimeException('Test exception'));
    }

    public function test_custom_connection_name()
    {
        $this->strategy->method('supports')->willReturn(true);
        $this->strategy->expects($this->once())->method('begin');
        $this->strategy->expects($this->once())->method('commit');

        $service = new #[Transactional('custom_connection')] class {
            public function run() { return 'done'; }
        };

        $result = $this->middleware->process($service, 'run', [], fn () => 'done');
        $this->assertEquals('done', $result);
    }

    public function test_method_attribute_takes_precedence_over_class()
    {
        $this->strategy->method('supports')->willReturn(true);
        $this->strategy->expects($this->once())->method('begin');
        $this->strategy->expects($this->once())->method('commit');

        $service = new #[Transactional('class_connection')] class {
            #[Transactional('method_connection')]
            public function run() { return 'done'; }
        };

        $result = $this->middleware->process($service, 'run', [], fn () => 'done');
        $this->assertEquals('done', $result);
    }
}