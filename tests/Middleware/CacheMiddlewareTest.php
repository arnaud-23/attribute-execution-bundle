<?php

namespace Arnaud23\AttributeExecutionBundle\Tests\Middleware;

use Arnaud23\AttributeExecutionBundle\Attribute\Cache;
use Arnaud23\AttributeExecutionBundle\Middleware\CacheMiddleware;
use Arnaud23\AttributeExecutionBundle\Strategy\Cache\CacheStrategyInterface;
use Arnaud23\AttributeExecutionBundle\Strategy\Cache\CacheStrategyResolver;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CacheMiddlewareTest extends TestCase
{
    private CacheStrategyInterface|MockObject $strategy;
    private CacheStrategyResolver $resolver;
    private CacheMiddleware $middleware;

    protected function setUp(): void
    {
        $this->strategy = $this->createMock(CacheStrategyInterface::class);
        $this->resolver = new CacheStrategyResolver([$this->strategy]);
        $this->middleware = new CacheMiddleware($this->resolver);
    }

    public function test_cache_wrapping_on_class_with_default_values()
    {
        $this->strategy->method('supports')->willReturn(true);
        $this->strategy->expects($this->once())
            ->method('get')
            ->willReturn(null);
        $this->strategy->expects($this->once())
            ->method('set')
            ->with(
                $this->stringContains('CacheMiddlewareTest\TestClass::run:'),
                'done',
                300
            );

        $service = new #[Cache()] class {
            public function run() { return 'done'; }
        };

        $result = $this->middleware->process($service, 'run', [], fn () => 'done');
        $this->assertEquals('done', $result);
    }

    public function test_cache_wrapping_on_method_with_custom_values()
    {
        $this->strategy->method('supports')->willReturn(true);
        $this->strategy->expects($this->once())
            ->method('get')
            ->willReturn(null);
        $this->strategy->expects($this->once())
            ->method('set')
            ->with(
                $this->stringContains('CacheMiddlewareTest\TestClass::run:'),
                'done',
                600
            );

        $service = new class {
            #[Cache(strategy: 'redis', ttl: 600)]
            public function run() { return 'done'; }
        };

        $result = $this->middleware->process($service, 'run', [], fn () => 'done');
        $this->assertEquals('done', $result);
    }

    public function test_cache_hit_returns_cached_value()
    {
        $this->strategy->method('supports')->willReturn(true);
        $this->strategy->expects($this->once())
            ->method('get')
            ->willReturn('cached_value');
        $this->strategy->expects($this->never())
            ->method('set');

        $service = new #[Cache()] class {
            public function run() { return 'done'; }
        };

        $result = $this->middleware->process($service, 'run', [], fn () => 'done');
        $this->assertEquals('cached_value', $result);
    }

    public function test_no_cache_when_no_attribute()
    {
        $this->strategy->expects($this->never())
            ->method('get');
        $this->strategy->expects($this->never())
            ->method('set');

        $service = new class {
            public function run() { return 'done'; }
        };

        $result = $this->middleware->process($service, 'run', [], fn () => 'done');
        $this->assertEquals('done', $result);
    }

    public function test_method_attribute_takes_precedence_over_class()
    {
        $this->strategy->method('supports')->willReturn(true);
        $this->strategy->expects($this->once())
            ->method('get')
            ->willReturn(null);
        $this->strategy->expects($this->once())
            ->method('set')
            ->with(
                $this->stringContains('CacheMiddlewareTest\TestClass::run:'),
                'done',
                600
            );

        $service = new #[Cache(ttl: 300)] class {
            #[Cache(ttl: 600)]
            public function run() { return 'done'; }
        };

        $result = $this->middleware->process($service, 'run', [], fn () => 'done');
        $this->assertEquals('done', $result);
    }

    public function test_cache_key_includes_serialized_arguments()
    {
        $this->strategy->method('supports')->willReturn(true);
        $this->strategy->expects($this->once())
            ->method('get')
            ->willReturn(null);
        $this->strategy->expects($this->once())
            ->method('set')
            ->with(
                $this->stringContains('CacheMiddlewareTest\TestClass::run:'),
                'done',
                300
            );

        $service = new #[Cache()] class {
            public function run($arg1, $arg2) { return 'done'; }
        };

        $args = ['arg1' => 'value1', 'arg2' => 'value2'];
        $result = $this->middleware->process($service, 'run', $args, fn () => 'done');
        $this->assertEquals('done', $result);
    }
}