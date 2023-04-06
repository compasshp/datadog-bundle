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

    private bool $appsecEnabled;

    /**
     * @param DatadogService $dataDogService
     * @param EventDispatcherInterface $eventDispatcher
     * @param bool $appsecEnabled
     */
    public function __construct(DatadogService $dataDogService, EventDispatcherInterface $eventDispatcher, bool $appsecEnabled)
    {
        $this->dataDogService = $dataDogService;
        $this->eventDispatcher = $eventDispatcher;
        $this->appsecEnabled = $appsecEnabled;
    }


    public static function getSubscribedEvents()
    {
        return [
            LoginSuccessEvent::class => "loginSuccessEvent"
        ];
    }

    public function loginSuccessEvent(LoginSuccessEvent $event)
    {
        if (!$this->appsecEnabled) {
            return;
        }

        $eventMeta = $this->eventDispatcher->dispatch(new DatadogLoginSuccessEvent());

        $this->dataDogService->trackLoginSuccess($event->getUser()->getUserIdentifier(), $eventMeta->getUserMeta());
    }
}