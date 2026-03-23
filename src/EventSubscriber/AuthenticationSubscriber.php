<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\ActivityLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class AuthenticationSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly ActivityLogger $activityLogger)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLogin',
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLogin(LoginSuccessEvent $event): void
    {
        $user = $event->getAuthenticatedToken()->getUser();
        if ($user instanceof User) {
            $this->activityLogger->log('LOGIN', 'auth', $user->getUserIdentifier(), $user->getId(), $user);
        }
    }

    public function onLogout(LogoutEvent $event): void
    {
        $user = $event->getToken()?->getUser();
        if ($user instanceof User) {
            $this->activityLogger->log('LOGOUT', 'auth', $user->getUserIdentifier(), $user->getId(), $user);
        } else {
            $this->activityLogger->log('LOGOUT', 'auth', 'anonymous');
        }
    }
}
