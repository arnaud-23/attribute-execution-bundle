<?php

namespace Arnaud23\AttributeExecutionBundle\Tests\Middleware;

use Arnaud23\AttributeExecutionBundle\Attribute\Security;
use Arnaud23\AttributeExecutionBundle\Middleware\SecurityMiddleware;
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

    public function test_secure_wrapping_on_class_with_default_role()
    {
        $this->checker->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn(true);

        $service = new #[Security()] class {
            public function run() { return 'done'; }
        };

        $result = $this->middleware->process($service, 'run', [], fn () => 'done');
        $this->assertEquals('done', $result);
    }

    public function test_secure_wrapping_on_method_with_custom_role()
    {
        $this->checker->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(true);

        $service = new class {
            #[Security('ROLE_ADMIN')]
            public function run() { return 'done'; }
        };

        $result = $this->middleware->process($service, 'run', [], fn () => 'done');
        $this->assertEquals('done', $result);
    }

    public function test_no_security_check_when_no_attribute()
    {
        $this->checker->expects($this->never())
            ->method('isGranted');

        $service = new class {
            public function run() { return 'done'; }
        };

        $result = $this->middleware->process($service, 'run', [], fn () => 'done');
        $this->assertEquals('done', $result);
    }

    public function test_access_denied_when_not_granted()
    {
        $this->checker->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(false);

        $service = new #[Security('ROLE_ADMIN')] class {
            public function run() { return 'done'; }
        };

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access denied: requires role ROLE_ADMIN');

        $this->middleware->process($service, 'run', [], fn () => 'done');
    }

    public function test_method_attribute_takes_precedence_over_class()
    {
        $this->checker->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_ADMIN')
            ->willReturn(true);

        $service = new #[Security('ROLE_USER')] class {
            #[Security('ROLE_ADMIN')]
            public function run() { return 'done'; }
        };

        $result = $this->middleware->process($service, 'run', [], fn () => 'done');
        $this->assertEquals('done', $result);
    }

    public function test_multiple_attributes_uses_first_one()
    {
        $this->checker->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_USER')
            ->willReturn(true);

        $service = new class {
            #[Security('ROLE_USER')]
            #[Security('ROLE_ADMIN')]
            public function run() { return 'done'; }
        };

        $result = $this->middleware->process($service, 'run', [], fn () => 'done');
        $this->assertEquals('done', $result);
    }
}