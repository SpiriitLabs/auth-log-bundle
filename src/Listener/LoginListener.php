<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\Listener;

use Spiriit\Bundle\AuthLogBundle\DTO\LoginParameterDto;
use Spiriit\Bundle\AuthLogBundle\Entity\AuthLogUserInterface;
use Spiriit\Bundle\AuthLogBundle\Messenger\AuthLoginMessage\AuthLoginMessage;
use Spiriit\Bundle\AuthLogBundle\Services\LoginService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

#[AsEventListener(event: LoginSuccessEvent::class)]
final class LoginListener
{
    public function __construct(
        private readonly LoginService $loginService,
        private readonly ?MessageBusInterface $messageBus = null,
    ) {
    }

    public function onLogin(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof AuthLogUserInterface) {
            return;
        }

        $request = $event->getRequest();

        $dto = new LoginParameterDto(
            userIdentifier: $user->getUserIdentifier(),
            toEmail: $user->getAuthLogEmail(),
            toEmailName: $user->getAuthLogDisplayName(),
            clientIp: $request->getClientIp() ?? '',
            userAgent: $request->headers->get('User-Agent', ''),
        );

        if (null !== $this->messageBus) {
            $this->messageBus->dispatch(new AuthLoginMessage($dto));

            return;
        }

        $this->loginService->execute($dto);
    }
}
