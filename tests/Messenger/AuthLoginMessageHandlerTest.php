<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\Tests\Messenger;

use PHPUnit\Framework\TestCase;
use Spiriit\Bundle\AuthLogBundle\AuthenticationLog\AuthenticationLogHandlerInterface;
use Spiriit\Bundle\AuthLogBundle\DTO\LoginParameterDto;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\FetchUserInformation;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;
use Spiriit\Bundle\AuthLogBundle\Messenger\AuthLoginMessage\AuthLoginMessage;
use Spiriit\Bundle\AuthLogBundle\Messenger\AuthLoginMessage\AuthLoginMessageHandler;
use Spiriit\Bundle\AuthLogBundle\Notification\NotificationInterface;
use Spiriit\Bundle\AuthLogBundle\Services\LoginService;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class AuthLoginMessageHandlerTest extends TestCase
{
    public function testInvokeDelegatesToLoginService(): void
    {
        $dto = new LoginParameterDto(
            userIdentifier: 'user-1',
            toEmail: 'user@test.com',
            toEmailName: 'Test User',
            clientIp: '127.0.0.1',
            userAgent: 'PHPUnit',
        );

        $userInformation = new UserInformation('127.0.0.1', 'PHPUnit', new \DateTimeImmutable(), null);

        $fetchUserInformation = $this->createStub(FetchUserInformation::class);
        $fetchUserInformation->method('fetch')->willReturn($userInformation);

        $handler = $this->createMock(AuthenticationLogHandlerInterface::class);
        $handler->method('isKnown')->willReturn(false);
        $handler->expects(self::once())->method('handle');

        $notifier = $this->createMock(NotificationInterface::class);
        $notifier->expects(self::once())->method('send');

        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $loginService = new LoginService($fetchUserInformation, $handler, $notifier, $dispatcher);
        $messageHandler = new AuthLoginMessageHandler($loginService);

        $messageHandler(new AuthLoginMessage($dto));
    }
}
