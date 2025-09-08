<?php

declare(strict_types=1);

/*
 * This file is part of the SpiriitLabs php-excel-rust package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\Listener;

use Spiriit\Bundle\AuthLogBundle\DTO\LoginParameterDto;
use Spiriit\Bundle\AuthLogBundle\Entity\AuthenticableLogInterface;
use Spiriit\Bundle\AuthLogBundle\Messenger\AuthLoginMessage\AuthLoginMessage;
use Spiriit\Bundle\AuthLogBundle\Services\LoginService;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class LoginListener
{
    private ?MessageBusInterface $bus = null;

    public function __construct(
        private readonly LoginService $loginService,
    ) {
    }

    public function onLogin(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof AuthenticableLogInterface) {
            return;
        }

        $request = $event->getRequest();

        /** @var UserInterface&AuthenticableLogInterface $user */
        $loginParameterDto = new LoginParameterDto(
            factoryName: $user->getAuthenticationLogFactoryName(),
            userIdentifier: $user->getUserIdentifier(),
            toEmail: $user->getAuthenticationLogsToEmail(),
            toEmailName: $user->getAuthenticationLogsToEmailName(),
            clientIp: $request->getClientIp(),
            userAgent: $request->headers->get('User-Agent'),
        );

        if (null === $this->bus) {
            $this->loginService->execute($loginParameterDto);
        } elseif ($this->bus instanceof MessageBusInterface) {
            $this->bus->dispatch(new AuthLoginMessage($loginParameterDto));
        }
    }

    public function setMessageBus(?MessageBusInterface $messageBus = null): void
    {
        $this->bus = $messageBus;
    }
}
