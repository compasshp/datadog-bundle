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

    /**
     * @param DatadogService $dataDogService
     * @param TokenStorageInterface $tokenStorage
     * @param EventDispatcherInterface $eventDispatcher
     * @param array $userTraceProperties
     */
    public function __construct(DatadogService $dataDogService, TokenStorageInterface $tokenStorage, EventDispatcherInterface $eventDispatcher, array $userTraceProperties)
    {
        $this->dataDogService = $dataDogService;
        $this->tokenStorage = $tokenStorage;
        $this->eventDispatcher = $eventDispatcher;
        $this->userTraceProperties = $userTraceProperties;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'updateRootSpan',
        ];
    }

    public function updateRootSpan(RequestEvent $event)
    {
        $meta = [];

        $requestEvent = $this->eventDispatcher->dispatch(new DatadogRequestEvent());

        $meta['usr.session_id'] = $event->getRequest()->getSession()?->getId();

        if (null !== $this->tokenStorage->getToken()?->getUser()) {
            $user = $this->tokenStorage->getToken()->getUser();

            if (false === empty($this->userTraceProperties)) {
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
}