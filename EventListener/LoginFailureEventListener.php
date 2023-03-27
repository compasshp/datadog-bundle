<?php

declare(strict_types=1);

namespace Compass\DatadogBundle\EventListener;

use Compass\DatadogBundle\Event\DatadogLoginFailureEvent;
use Compass\DatadogBundle\Service\DatadogService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class LoginFailureEventListener implements EventSubscriberInterface
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
            LoginFailureEvent::class => "loginFailureEvent"
        ];
    }

    public function loginFailureEvent(LoginFailureEvent $event)
    {
        /** @var UserBadge $badge */
        $badge = $event->getPassport()->getBadge(UserBadge::class);
        $username = $badge->getUserIdentifier();

        $exists = true;

        try {
            $badge->getUser();
        } catch (UserNotFoundException $e) {
            $exists = false;
        }

        $eventMeta = $this->eventDispatcher->dispatch(new DatadogLoginFailureEvent());

        $this->dataDogService->trackLoginFailure($username, $exists, $eventMeta->getUserMeta());
    }
}