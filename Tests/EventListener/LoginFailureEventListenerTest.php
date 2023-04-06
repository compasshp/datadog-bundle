<?php

namespace Compass\DatadogBundle\Tests\EventListener;

use Compass\DatadogBundle\Event\DatadogLoginFailureEvent;
use Compass\DatadogBundle\EventListener\LoginFailureEventListener;
use Compass\DatadogBundle\Service\DatadogService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class LoginFailureEventListenerTest extends TestCase
{
    private LoginFailureEventListener $listener;

    private DatadogService $dataDogService;

    private EventDispatcherInterface $eventDispatcher;

    public function setUp(): void
    {


        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->eventDispatcher->method('dispatch')->willReturn(new DatadogLoginFailureEvent());

        $this->dataDogService = $this->createMock(DatadogService::class);
        $this->dataDogService->method('addTraceMetadata')->willReturn(null);

        $this->listener = new LoginFailureEventListener($this->dataDogService, $this->eventDispatcher, true);
    }

    public function testLoginFailureTracked()
    {
        $this->dataDogService->expects($this->once())->method('trackLoginFailure');

        $this->listener->loginFailureEvent($this->createLoginFailedEvent($this->createPassport('john_doe')));
    }

    private function createLoginFailedEvent($passport)
    {
        $requestStack = new RequestStack();

        $request = new Request();

        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->any())
            ->method('getId')
            ->willReturn('123456');

        $requestStack->push($request);

        return new LoginFailureEvent(
            $this->createMock(AuthenticationException::class),
            $this->createMock(AuthenticatorInterface::class),
            $requestStack->getCurrentRequest(),
            null,
            'main',
            $passport
        );
    }

    private function createPassport($username)
    {
        return new SelfValidatingPassport(new UserBadge($username, function () {
        }));
    }

    public function testEventDispatched()
    {
        $this->eventDispatcher->expects($this->once())->method('dispatch');

        $this->listener->loginFailureEvent($this->createLoginFailedEvent($this->createPassport('john_doe')));
    }
}
