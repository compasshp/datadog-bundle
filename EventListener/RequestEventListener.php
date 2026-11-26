<?php

namespace Compass\DatadogBundle\EventListener;

use Compass\DatadogBundle\Event\DatadogRequestEvent;
use Compass\DatadogBundle\Service\DatadogService;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class RequestEventListener implements EventSubscriberInterface
{
    private DatadogService $dataDogService;

    private TokenStorageInterface $tokenStorage;

    private EventDispatcherInterface $eventDispatcher;

    private array $userTraceProperties = [];

    private bool $traceEnabled;

    /**
     * @var string|null The configured trace.user_entity class. The user properties are only read
     *                  from instances of it.
     */
    private ?string $userEntity;

    /**
     * @param DatadogService $dataDogService
     * @param TokenStorageInterface $tokenStorage
     * @param EventDispatcherInterface $eventDispatcher
     * @param array $userTraceProperties
     * @param bool $traceEnabled
     * @param string|null $userEntity
     */
    public function __construct(DatadogService $dataDogService, TokenStorageInterface $tokenStorage, EventDispatcherInterface $eventDispatcher, array $userTraceProperties, bool $traceEnabled, ?string $userEntity = null)
    {
        $this->userEntity = $userEntity;
        $this->dataDogService = $dataDogService;
        $this->tokenStorage = $tokenStorage;
        $this->eventDispatcher = $eventDispatcher;
        $this->userTraceProperties = $userTraceProperties;
        $this->traceEnabled = $traceEnabled;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'updateRootSpan',
        ];
    }

    public function updateRootSpan(RequestEvent $event)
    {
        if (!$this->traceEnabled) {
            return;
        }

        $meta = [];

        $requestEvent = $this->eventDispatcher->dispatch(new DatadogRequestEvent());

        $meta['usr.session_id'] = $event->getRequest()->getSession()?->getId();

        if (null !== $this->tokenStorage->getToken()?->getUser()) {
            $user = $this->tokenStorage->getToken()->getUser();

            // user_properties are properties of user_entity. An application can authenticate other kinds
            // of principal (an API client on a second firewall, for example) that do not have them, and
            // reading a missing property throws, which would fail the request.
            if (false === empty($this->userTraceProperties) && $this->isTraceableUser($user)) {
                $propertyAccessor = PropertyAccess::createPropertyAccessor();
                foreach ($this->userTraceProperties as $traceProperty) {
                    $meta['usr.' . $traceProperty] = $propertyAccessor->getValue($user, $traceProperty);
                }
            }

        }

        foreach ($requestEvent->getMeta() as $key => $value) {
            $meta[$key] = $value;
        }

        $this->dataDogService->addTraceMetadata($meta);
    }

    private function isTraceableUser(object $user): bool
    {
        return null === $this->userEntity || $user instanceof $this->userEntity;
    }
}