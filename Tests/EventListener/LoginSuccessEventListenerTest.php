<?php

namespace Compass\DatadogBundle\Tests\EventListener;

use Compass\DatadogBundle\Event\DatadogLoginFailureEvent;
use Compass\DatadogBundle\EventListener\LoginSuccessEventListener;
use Compass\DatadogBundle\Service\DatadogService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\NullToken;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class LoginSuccessEventListenerTest extends TestCase
{
    private LoginSuccessEventListener $listener;

    private DatadogService $dataDogService;

    private EventDispatcherInterface $eventDispatcher;

    public function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->eventDispatcher->method('dispatch')->willReturn(new DatadogLoginFailureEvent());

        $this->dataDogService = $this->createMock(DatadogService::class);
        $this->dataDogService->method('addTraceMetadata')->willReturn(null);

        $this->listener = new LoginSuccessEventListener($this->dataDogService, $this->eventDispatcher, true);
    }

    public function testLoginSuccessTracked()
    {
        $this->dataDogService->expects($this->once())->method('trackLoginSuccess');

        $this->listener->loginSuccessEvent($this->createLoginSuccessEvent($this->createPassport('john_doe')));
    }

    private function createLoginSuccessEvent($passport)
    {
        return new LoginSuccessEvent(
            $this->createMock(AuthenticatorInterface::class),
            $passport,
            $this->createMock(NullToken::class),
            new Request(),
            null,
            'main'
        );
    }

    private function createPassport($username)
    {
        return new SelfValidatingPassport(new UserBadge($username, function ($username) {
            return new InMemoryUser($username, null);
        }));
    }

    public function testEventDispatched()
    {
        $this->eventDispatcher->expects($this->once())->method('dispatch');

        $this->listener->loginSuccessEvent($this->createLoginSuccessEvent($this->createPassport('john_doe')));
    }
}
