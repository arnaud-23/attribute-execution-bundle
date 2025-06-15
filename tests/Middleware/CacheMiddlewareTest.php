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
    private CacheMiddleware $middleware;

    protected function setUp(): void
    {
        $this->strategy = $this->createMock(CacheStrategyInterface::class);
        $this->middleware = new CacheMiddleware(
            resolver: new CacheStrategyResolver([$this->strategy])
        );
    }

    public function test_cache_wrapping_on_class_with_default_values(): void
    {
        $this->strategy->method('supports')->willReturn(true);
        $this->strategy->expects($this->once())
            ->method('get')
            ->willReturn(null);
        $this->strategy->expects($this->once())
            ->method('set')
            ->with(
                $this->stringContains('::run:'),
                'value_to_cache',
                300
            );

        $service = new #[Cache()] class {
            public function run() { return 'value_to_cache'; }
        };

        $coreMiddleware = new \Arnaud23\AttributeExecutionBundle\Pipeline\CoreMiddleware();
        $result = $this->middleware->process(
            instance: $service,
            method: 'run',
            args: [],
            next: fn ($i, $m, $a) => $coreMiddleware->process($i, $m, $a, fn () => null)
        );
        $this->assertEquals('value_to_cache', $result);
    }

    public function test_cache_wrapping_on_method_with_custom_values(): void
    {
        $this->strategy->method('supports')->willReturn(true);
        $this->strategy->expects($this->once())
            ->method('get')
            ->willReturn(null);
        $this->strategy->expects($this->once())
            ->method('set')
            ->with(
                $this->stringContains('::run:'),
                'done',
                600
            );

        $service = new class {
            #[Cache(strategy: 'redis', ttl: 600)]
            public function run() { return 'done'; }
        };

        $coreMiddleware = new \Arnaud23\AttributeExecutionBundle\Pipeline\CoreMiddleware();
        $result = $this->middleware->process(
            instance: $service,
            method: 'run',
            args: [],
            next: fn ($i, $m, $a) => $coreMiddleware->process($i, $m, $a, fn () => null)
        );
        $this->assertEquals('done', $result);
    }

    public function test_cache_hit_returns_cached_value(): void
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

        $coreMiddleware = new \Arnaud23\AttributeExecutionBundle\Pipeline\CoreMiddleware();
        $result = $this->middleware->process(
            instance: $service,
            method: 'run',
            args: [],
            next: fn ($i, $m, $a) => $coreMiddleware->process($i, $m, $a, fn () => null)
        );
        $this->assertEquals('cached_value', $result);
    }

    public function test_no_cache_when_no_attribute(): void
    {
        $this->strategy->expects($this->never())
            ->method('get');
        $this->strategy->expects($this->never())
            ->method('set');

        $service = new class {
            public function run() { return 'done'; }
        };

        $coreMiddleware = new \Arnaud23\AttributeExecutionBundle\Pipeline\CoreMiddleware();
        $result = $this->middleware->process(
            instance: $service,
            method: 'run',
            args: [],
            next: fn ($i, $m, $a) => $coreMiddleware->process($i, $m, $a, fn () => null)
        );
        $this->assertEquals('done', $result);
    }

    public function test_method_attribute_takes_precedence_over_class(): void
    {
        $this->strategy->method('supports')->willReturn(true);
        $this->strategy->expects($this->once())
            ->method('get')
            ->willReturn(null);
        $this->strategy->expects($this->once())
            ->method('set')
            ->with(
                $this->stringContains('::run:'),
                'done',
                600  // Method's ttl (600) takes precedence over class's ttl (300)
            );

        // Class has a cache attribute with ttl 300
        $service = new #[Cache(ttl: 300)] class {
            // Method has a cache attribute with ttl 600 that should take precedence
            #[Cache(ttl: 600)]
            public function run() { return 'done'; }
        };

        $coreMiddleware = new \Arnaud23\AttributeExecutionBundle\Pipeline\CoreMiddleware();
        $result = $this->middleware->process(
            instance: $service,
            method: 'run',
            args: [],
            next: fn ($i, $m, $a) => $coreMiddleware->process($i, $m, $a, fn () => null)
        );
        $this->assertEquals('done', $result);
    }

    public function test_cache_key_includes_serialized_arguments(): void
    {
        $this->strategy->method('supports')->willReturn(true);
        $this->strategy->expects($this->once())
            ->method('get')
            ->willReturn(null);
        $this->strategy->expects($this->once())
            ->method('set')
            ->with(
                $this->stringContains('::run:'),
                'done',
                300
            );

        $service = new #[Cache()] class {
            public function run($arg1, $arg2) { return 'done'; }
        };

        $args = ['arg1' => 'value1', 'arg2' => 'value2'];
        $coreMiddleware = new \Arnaud23\AttributeExecutionBundle\Pipeline\CoreMiddleware();
        $result = $this->middleware->process(
            instance: $service,
            method: 'run',
            args: $args,
            next: fn ($i, $m, $a) => $coreMiddleware->process($i, $m, $a, fn () => null)
        );
        $this->assertEquals('done', $result);
    }

    public function test_middleware_before_cache_processes_original_result(): void
    {
        $this->strategy->method('supports')->willReturn(true);
        $this->strategy->expects($this->once())
            ->method('get')
            ->willReturn(null);
        $this->strategy->expects($this->once())
            ->method('set')
            ->with(
                $this->stringContains('::run:'),
                'original_value',
                300
            );

        $service = new #[Cache()] class {
            public function run() { return 'original_value'; }
        };

        // Simulate a middleware that processes the result before caching
        $preCacheMiddleware = new class implements \Arnaud23\AttributeExecutionBundle\Pipeline\AttributeMiddlewareInterface {
            public function process(object $instance, string $method, array $args, callable $next): mixed
            {
                $result = $next($instance, $method, $args);
                return 'processed_' . $result;
            }
        };

        // Create a pipeline with pre-cache middleware and core middleware
        $pipeline = new \Arnaud23\AttributeExecutionBundle\Pipeline\AttributePipeline([
            $preCacheMiddleware,
            $this->middleware,
            new \Arnaud23\AttributeExecutionBundle\Pipeline\CoreMiddleware()
        ]);

        $result = $pipeline->handle($service, 'run', []);
        $this->assertEquals('processed_original_value', $result);
    }

    public function test_middleware_after_cache_processes_original_result(): void
    {
        $this->strategy->method('supports')->willReturn(true);
        $this->strategy->expects($this->once())
            ->method('get')
            ->willReturn(null);
        $this->strategy->expects($this->once())
            ->method('set')
            ->with(
                $this->stringContains('::run:'),
                'processed_original_value',
                300
            );

        $service = new #[Cache()] class {
            public function run() { return 'original_value'; }
        };

        // Simulate a middleware that processes the result after caching
        $postCacheMiddleware = new class implements \Arnaud23\AttributeExecutionBundle\Pipeline\AttributeMiddlewareInterface {
            public function process(object $instance, string $method, array $args, callable $next): mixed
            {
                $result = $next($instance, $method, $args);
                return 'processed_' . $result;
            }
        };

        // Create a pipeline with post-cache middleware and core middleware
        $pipeline = new \Arnaud23\AttributeExecutionBundle\Pipeline\AttributePipeline([
            $this->middleware,
            $postCacheMiddleware,
            new \Arnaud23\AttributeExecutionBundle\Pipeline\CoreMiddleware()
        ]);

        $result = $pipeline->handle($service, 'run', []);
        $this->assertEquals('processed_original_value', $result);
    }

    public function test_middleware_after_cache_processes_cached_value(): void
    {
        $this->strategy->method('supports')->willReturn(true);
        $this->strategy->expects($this->once())
            ->method('get')
            ->willReturn('cached_value');
        $this->strategy->expects($this->never())
            ->method('set');

        $service = new #[Cache()] class {
            public function run() { return 'original_value'; }
        };

        // Simulate a middleware that processes the result after caching
        $postCacheMiddleware = new class implements \Arnaud23\AttributeExecutionBundle\Pipeline\AttributeMiddlewareInterface {
            public function process(object $instance, string $method, array $args, callable $next): mixed
            {
                $result = $next($instance, $method, $args);
                return 'processed_' . $result;
            }
        };

        // Create a pipeline with post-cache middleware and core middleware
        $pipeline = new \Arnaud23\AttributeExecutionBundle\Pipeline\AttributePipeline([
            $this->middleware,
            $postCacheMiddleware,
            new \Arnaud23\AttributeExecutionBundle\Pipeline\CoreMiddleware()
        ]);

        $result = $pipeline->handle($service, 'run', []);
        // assert that we return the cached value immediately after the middleware execution
        $this->assertEquals('cached_value', $result);
        // assert that we don't go through the post cache middleware if previous cache value existed
        $this->assertNotEquals('processed_cached_value', $result);
    }

    public function test_multiple_middlewares_in_different_orders(): void
    {
        $this->strategy->method('supports')->willReturn(true);
        $this->strategy->expects($this->once())
            ->method('get')
            ->willReturn(null);
        $this->strategy->expects($this->once())
            ->method('set')
            ->with(
                $this->stringContains('::run:'),
                'post_processed_original_value',
                300
            );

        $service = new #[Cache()] class {
            public function run() { return 'original_value'; }
        };

        // Create two middlewares that process the result
        $preCacheMiddleware = new class implements \Arnaud23\AttributeExecutionBundle\Pipeline\AttributeMiddlewareInterface {
            public function process(object $instance, string $method, array $args, callable $next): mixed
            {
                $result = $next($instance, $method, $args);
                return 'pre_processed_' . $result;
            }
        };

        $postCacheMiddleware = new class implements \Arnaud23\AttributeExecutionBundle\Pipeline\AttributeMiddlewareInterface {
            public function process(object $instance, string $method, array $args, callable $next): mixed
            {
                $result = $next($instance, $method, $args);
                return 'post_processed_' . $result;
            }
        };

        // Create a pipeline with both middlewares and core middleware
        $pipeline = new \Arnaud23\AttributeExecutionBundle\Pipeline\AttributePipeline([
            $preCacheMiddleware,
            $this->middleware,
            $postCacheMiddleware,
            new \Arnaud23\AttributeExecutionBundle\Pipeline\CoreMiddleware()
        ]);

        $result = $pipeline->handle($service, 'run', []);
        $this->assertEquals('pre_processed_post_processed_original_value', $result);
    }

    public function test_multiple_middlewares_in_different_orders_with_cache_hit(): void
    {
        $this->strategy->method('supports')->willReturn(true);
        // Test cache hit with the same pipeline
        $this->strategy->expects($this->once())
            ->method('get')
            ->willReturn('pre_processed_original_value');
        $this->strategy->expects($this->never())
            ->method('set');

        $service = new #[Cache()] class {
            public function run() { return 'original_value'; }
        };

        // Create two middlewares that process the result
        $preCacheMiddleware = new class implements \Arnaud23\AttributeExecutionBundle\Pipeline\AttributeMiddlewareInterface {
            public function process(object $instance, string $method, array $args, callable $next): mixed
            {
                $result = $next($instance, $method, $args);
                return 'pre_processed_' . $result;
            }
        };

        $postCacheMiddleware = new class implements \Arnaud23\AttributeExecutionBundle\Pipeline\AttributeMiddlewareInterface {
            public function process(object $instance, string $method, array $args, callable $next): mixed
            {
                $result = $next($instance, $method, $args);
                return 'post_processed_' . $result;
            }
        };

        // Create a pipeline with both middlewares and core middleware
        $pipeline = new \Arnaud23\AttributeExecutionBundle\Pipeline\AttributePipeline([
            $preCacheMiddleware,
            $this->middleware,
            $postCacheMiddleware,
            new \Arnaud23\AttributeExecutionBundle\Pipeline\CoreMiddleware()
        ]);

        $result = $pipeline->handle($service, 'run', []);
        $this->assertEquals('pre_processed_pre_processed_original_value', $result);
    }
}