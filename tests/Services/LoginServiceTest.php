<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\Tests\Services;

use PHPUnit\Framework\TestCase;
use Spiriit\Bundle\AuthLogBundle\AuthenticationLog\AuthenticationLogHandlerInterface;
use Spiriit\Bundle\AuthLogBundle\DTO\LoginParameterDto;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\FetchUserInformation;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;
use Spiriit\Bundle\AuthLogBundle\Notification\NotificationInterface;
use Spiriit\Bundle\AuthLogBundle\Services\LoginService;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class LoginServiceTest extends TestCase
{
    public function testItShouldNotNotifyWhenAuthLogIsKnown(): void
    {
        // Arrange
        $dto = new LoginParameterDto(
            userIdentifier: '1',
            toEmail: 'email@test.fr',
            toEmailName: 'test',
            clientIp: '127.0.0.1',
            userAgent: 'agent',
        );

        $userInformation = new UserInformation('127.0.0.1', 'agent', new \DateTimeImmutable('2025-09-01'), null);

        $fetchUserInformation = $this->createStub(FetchUserInformation::class);
        $fetchUserInformation->method('fetch')->willReturn($userInformation);

        $handler = $this->createMock(AuthenticationLogHandlerInterface::class);
        $handler->method('isKnown')->willReturn(true);

        $notifier = $this->createMock(NotificationInterface::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        // Act
        $handler->expects(self::never())->method('handle');
        $notifier->expects(self::never())->method('send');
        $dispatcher->expects(self::never())->method('dispatch');

        $service = new LoginService($fetchUserInformation, $handler, $notifier, $dispatcher);
        $service->execute($dto);
    }

    public function testItShouldHandleAndNotifyWhenAuthLogIsNotKnown(): void
    {
        // Arrange
        $dto = new LoginParameterDto(
            userIdentifier: '1',
            toEmail: 'email@test.fr',
            toEmailName: 'test',
            clientIp: '127.0.0.1',
            userAgent: 'agent',
        );

        $userInformation = new UserInformation('127.0.0.1', 'agent', new \DateTimeImmutable('2025-09-01'), null);

        $fetchUserInformation = $this->createStub(FetchUserInformation::class);
        $fetchUserInformation->method('fetch')->willReturn($userInformation);

        $handler = $this->createMock(AuthenticationLogHandlerInterface::class);
        $handler->method('isKnown')->willReturn(false);

        $notifier = $this->createMock(NotificationInterface::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        // Act
        $handler->expects(self::once())->method('handle');
        $notifier->expects(self::once())->method('send');
        $dispatcher->expects(self::once())->method('dispatch');

        $service = new LoginService($fetchUserInformation, $handler, $notifier, $dispatcher);
        $service->execute($dto);
    }
}
