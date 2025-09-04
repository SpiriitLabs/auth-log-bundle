<?php

/*
 * This file is part of the SpiriitLabs php-excel-rust package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\Services;

use Spiriit\Bundle\AuthLogBundle\Listener\AuthenticationLogEvent;
use Spiriit\Bundle\AuthLogBundle\Listener\AuthenticationLogEvents;
use Spiriit\Bundle\AuthLogBundle\Notification\NotificationInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class AuthenticationEventPublisher
{
    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
        private readonly NotificationInterface $notifier,
    ) {
    }

    public function publish(AuthenticationContext $context): void
    {
        $event = new AuthenticationLogEvent($context->authenticationLog, $context->userInformation);
        $this->dispatcher->dispatch($event, AuthenticationLogEvents::LOGIN);

        if (!$event->isRegisterConfirmed()) {
            throw new \RuntimeException('The authentication log should be confirm persisted by an event listener');
        }

        $this->notifier->send(
            userInformation: $context->userInformation,
            authenticableLog: $context->authenticationLog
        );
    }
}
