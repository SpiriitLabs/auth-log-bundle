<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\Services;

use Spiriit\Bundle\AuthLogBundle\AuthenticationLog\AuthenticationLogHandlerInterface;
use Spiriit\Bundle\AuthLogBundle\DTO\LoginParameterDto;
use Spiriit\Bundle\AuthLogBundle\DTO\UserReference;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\FetchUserInformation;
use Spiriit\Bundle\AuthLogBundle\Listener\AuthenticationLogEvent;
use Spiriit\Bundle\AuthLogBundle\Listener\AuthenticationLogEvents;
use Spiriit\Bundle\AuthLogBundle\Notification\NotificationInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class LoginService
{
    public function __construct(
        private readonly FetchUserInformation $fetchUserInformation,
        private readonly AuthenticationLogHandlerInterface $handler,
        private readonly NotificationInterface $notifier,
        private readonly EventDispatcherInterface $dispatcher,
    ) {
    }

    public function execute(LoginParameterDto $dto): void
    {
        $userInformation = $this->fetchUserInformation->fetch($dto->clientIp, $dto->userAgent);

        if ($this->handler->isKnown($dto->userIdentifier, $userInformation)) {
            return;
        }

        $this->handler->handle($dto->userIdentifier, $userInformation);

        $this->dispatcher->dispatch(
            new AuthenticationLogEvent($dto->userIdentifier, $userInformation),
            AuthenticationLogEvents::NEW_DEVICE
        );

        $userReference = new UserReference(
            userIdentifier: $dto->userIdentifier,
            email: $dto->toEmail,
            displayName: $dto->toEmailName,
        );
        $this->notifier->send($userInformation, $userReference);
    }
}
