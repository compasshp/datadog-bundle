<?php

declare(strict_types=1);

namespace Compass\DatadogBundle\EventListener;

use Compass\DatadogBundle\Event\DatadogLoginSuccessEvent;
use Compass\DatadogBundle\Service\DatadogService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class LoginSuccessEventListener implements EventSubscriberInterface
{
    private DatadogService $dataDogService;

    private EventDispatcherInterface $eventDispatcher;

    /**
     * @param DatadogService $dataDogService
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(DatadogService $dataDogService, EventDispatcherInterface $eventDispatcher)
    {
        $this->dataDogService = $dataDogService;
        $this->eventDispatcher = $eventDispatcher;
    }

    public static function getSubscribedEvents()
    {
        return [
            LoginSuccessEvent::class => "loginSuccessEvent"
        ];
    }

    public function loginSuccessEvent(LoginSuccessEvent $event)
    {
        $eventMeta = $this->eventDispatcher->dispatch(new DatadogLoginSuccessEvent());

        $this->dataDogService->trackLoginSuccess($event->getUser()->getUserIdentifier(), $eventMeta->getUserMeta());
    }
}