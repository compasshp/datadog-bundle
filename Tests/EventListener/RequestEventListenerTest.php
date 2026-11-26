<?php

namespace Compass\DatadogBundle\Tests\EventListener;

use Compass\DatadogBundle\EventListener\RequestEventListener;
use Compass\DatadogBundle\Service\DatadogService;
use Compass\DatadogBundle\Tests\Mock\DummyApiClientClass;
use Compass\DatadogBundle\Tests\Mock\DummyUserClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class RequestEventListenerTest extends TestCase
{
    private RequestEventListener $listener;

    private DatadogService $dataDogService;

    public function setUp(): void
    {
        $this->dataDogService = $this->createMock(DatadogService::class);

        $this->listener = $this->createListener(new DummyUserClass());
    }

    private function createListener(UserInterface $user, ?string $userEntity = null): RequestEventListener
    {
        $security = $this->createMock(TokenStorageInterface::class);
        $security->method('getToken')->willReturn(new PreAuthenticatedToken($user, 'main'));

        return new RequestEventListener($this->dataDogService, $security, new EventDispatcher(), ['first_name', 'lastName'], true, $userEntity);
    }

    private function createEvent(): RequestEvent
    {
        $session = $this->createMock(SessionInterface::class);
        $session->method('getId')->willReturn('123456');

        $request = new Request();
        $request->setSession($session);

        return new RequestEvent($this->createMock(HttpKernelInterface::class), $request, HttpKernelInterface::MAIN_REQUEST);
    }

    public function testUserPropertiesAreTracedForTheConfiguredUserEntity()
    {
        $this->dataDogService->expects($this->once())->method('addTraceMetadata')->with([
            'usr.session_id' => '123456',
            'usr.first_name' => 'John',
            'usr.lastName' => 'Doe',
        ]);

        $this->createListener(new DummyUserClass(), DummyUserClass::class)->updateRootSpan($this->createEvent());
    }

    /**
     * A principal that is not the configured user entity does not have the configured properties.
     * Reading them used to throw NoSuchPropertyException and fail the request.
     */
    public function testUserPropertiesAreSkippedForAnotherKindOfPrincipal()
    {
        $this->dataDogService->expects($this->once())->method('addTraceMetadata')->with([
            'usr.session_id' => '123456',
        ]);

        $this->createListener(new DummyApiClientClass(), DummyUserClass::class)->updateRootSpan($this->createEvent());
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
