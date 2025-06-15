<?php

namespace Arnaud23\AttributeExecutionBundle\Tests\Middleware;

use Arnaud23\AttributeExecutionBundle\Attribute\Security;
use Arnaud23\AttributeExecutionBundle\Middleware\SecurityMiddleware;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SecurityMiddlewareTest extends TestCase
{
    private AuthorizationCheckerInterface|MockObject $checker;
    private SecurityMiddleware $middleware;

    protected function setUp(): void
    {
        $this->checker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->middleware = new SecurityMiddleware($this->checker);
    }

    #[Test]
    public function security_wrapping_on_class_with_default_role(): void
    {
        $this->checker->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn(true);

        $service = new #[Security()] class {
            public function run(): string { return 'return'; }
        };

        $result = $this->middleware->process(instance:$service, method:'run', args:[], next:fn () => 'next');
        $this->assertEquals('next', $result);
    }

    #[Test]
    public function security_wrapping_on_method_with_custom_role(): void
    {
        $this->checker->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(true);

        $service = new class {
            #[Security('ROLE_ADMIN')]
            public function run(): string { return 'done'; }
        };

        $result = $this->middleware->process(instance: $service, method: 'run', args: [], next: fn () => 'next');
        $this->assertEquals('next', $result);
    }

    #[Test]
    public function no_security_check_when_no_attribute(): void
    {
        $this->checker->expects($this->never())
            ->method('isGranted');

        $service = new class {
            public function run(): string { return 'done'; }
        };

        $result = $this->middleware->process(instance: $service, method: 'run', args: [], next: fn () => 'next');
        $this->assertEquals('next', $result);
    }

    #[Test]
    public function access_denied_when_not_granted(): void
    {
        $this->checker->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(false);

        $service = new #[Security('ROLE_ADMIN')] class {
            public function run(): string { return 'done'; }
        };

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access denied: requires role ROLE_ADMIN');

        $result = $this->middleware->process(instance: $service, method: 'run', args: [], next: fn () => 'next');
        $this->assertEquals('next', $result);
    }

    #[Test]
    public function method_attribute_takes_precedence_over_class(): void
    {
        $this->checker->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(true);

        $service = new #[Security('ROLE_USER')] class {
            #[Security('ROLE_ADMIN')]
            public function run(): string { return 'done'; }
        };

        $result = $this->middleware->process(instance: $service, method: 'run', args: [], next: fn () => 'next');
        $this->assertEquals('next', $result);
    }

    #[Test]
    public function multiple_attributes_throw_attribute_not_repeatable_exception(): void
    {
        $this->checker->expects($this->never())
            ->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn(true);

        $service = new class {
            #[Security('ROLE_USER')]
            #[Security('ROLE_ADMIN')]
            public function run(): string { return 'done'; }
        };

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Attribute "Arnaud23\AttributeExecutionBundle\Attribute\Security" must not be repeated');

        $result = $this->middleware->process(instance: $service, method: 'run', args: [], next: fn () => 'next');
        $this->assertEquals('next', $result);
    }
}