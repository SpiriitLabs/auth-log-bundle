<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\Tests\AuthenticationLog;

use PHPUnit\Framework\TestCase;
use Spiriit\Bundle\AuthLogBundle\AuthenticationLog\AuthenticationLogCreatorInterface;
use Spiriit\Bundle\AuthLogBundle\AuthenticationLog\DoctrineAuthenticationLogHandler;
use Spiriit\Bundle\AuthLogBundle\Entity\AbstractAuthenticationLog;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;
use Spiriit\Bundle\AuthLogBundle\Repository\AuthenticationLogRepositoryInterface;

final class DoctrineAuthenticationLogHandlerTest extends TestCase
{
    public function testItShouldDelegateIsKnownToRepository(): void
    {
        // Arrange
        $userInformation = new UserInformation('127.0.0.1', 'PHPUnit', new \DateTimeImmutable(), null);

        $repository = $this->createMock(AuthenticationLogRepositoryInterface::class);
        $creator = $this->createStub(AuthenticationLogCreatorInterface::class);

        // Act
        $repository->expects(self::once())
            ->method('findExistingLog')
            ->with('user-1', $userInformation)
            ->willReturn(true);

        $handler = new DoctrineAuthenticationLogHandler($repository, $creator);
        $result = $handler->isKnown('user-1', $userInformation);

        // Assert
        self::assertTrue($result);
    }

    public function testItShouldCreateAndSaveLog(): void
    {
        // Arrange
        $userInformation = new UserInformation('127.0.0.1', 'PHPUnit', new \DateTimeImmutable(), null);
        $log = $this->createStub(AbstractAuthenticationLog::class);

        $repository = $this->createMock(AuthenticationLogRepositoryInterface::class);
        $creator = $this->createStub(AuthenticationLogCreatorInterface::class);
        $creator->method('createLog')->willReturn($log);

        // Act
        $repository->expects(self::once())
            ->method('save')
            ->with($log);

        $handler = new DoctrineAuthenticationLogHandler($repository, $creator);
        $handler->handle('user-1', $userInformation);
    }
}
