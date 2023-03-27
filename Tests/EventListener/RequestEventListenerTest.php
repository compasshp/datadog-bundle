<?php

namespace Compass\DatadogBundle\Tests\EventListener;

use Compass\DatadogBundle\EventListener\RequestEventListener;
use Compass\DatadogBundle\Service\DatadogService;
use Compass\DatadogBundle\Tests\Mock\DummyUserClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class RequestEventListenerTest extends TestCase
{
    private RequestEventListener $listener;

    private DatadogService $dataDogService;

    public function setUp(): void
    {
        $eventDispatcher = new EventDispatcher();
        $security = $this->createMock(TokenStorageInterface::class);
        $security->method('getToken')->willReturn(
            new PreAuthenticatedToken(new DummyUserClass(), 'main')
        );

        $this->dataDogService = $this->createMock(DatadogService::class);
        $this->dataDogService->method('addTraceMetadata')->willReturn(null);

        $this->listener = new RequestEventListener($this->dataDogService, $security, $eventDispatcher, ['first_name', 'lastName']);
    }

    public function testUpdateRootSpan()
    {
        $request = new Request();

        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->any())
            ->method('getId')
            ->willReturn('123456');

        $this->dataDogService->expects($this->once())->method('addTraceMetadata');

        $request->setSession($session);

        $this->listener->updateRootSpan(
            new RequestEvent($this->createMock(HttpKernelInterface::class),
                $request,
                HttpKernelInterface::MAIN_REQUEST)
        );
    }
}
