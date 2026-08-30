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
use Spiriit\Bundle\AuthLogBundle\Confirmation\ConfirmationTokenGenerator;
use Spiriit\Bundle\AuthLogBundle\DTO\UserIdentity;
use Spiriit\Bundle\AuthLogBundle\Entity\AbstractAuthenticationLog;
use Spiriit\Bundle\AuthLogBundle\Entity\AuthLogUserInterface;
use Spiriit\Bundle\AuthLogBundle\Entity\ConfirmableAuthenticationLogInterface;
use Spiriit\Bundle\AuthLogBundle\Entity\ConfirmableAuthenticationLogTrait;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;
use Spiriit\Bundle\AuthLogBundle\Repository\AuthenticationLogRepositoryInterface;
use Spiriit\Bundle\Tests\Stubs\StubAdminUser;
use Spiriit\Bundle\Tests\Stubs\StubUser;

final class DoctrineAuthenticationLogHandlerTest extends TestCase
{
    public function testItShouldDelegateIsKnownToRepository(): void
    {
        // Arrange
        $userInformation = new UserInformation('127.0.0.1', 'PHPUnit', new \DateTimeImmutable(), null);
        $userIdentity = new UserIdentity('user-1', StubUser::class);

        $repository = $this->createMock(AuthenticationLogRepositoryInterface::class);
        $creator = $this->createStub(AuthenticationLogCreatorInterface::class);

        // Act
        $repository->expects(self::once())
            ->method('findExistingLog')
            ->with($userIdentity, $userInformation)
            ->willReturn(true);

        $handler = new DoctrineAuthenticationLogHandler($repository, $creator);
        $result = $handler->isKnown($userIdentity, $userInformation);

        // Assert
        self::assertTrue($result);
    }

    public function testItShouldTellTwoUserClassesApartForTheSameIdentifier(): void
    {
        $userInformation = new UserInformation('127.0.0.1', 'PHPUnit', new \DateTimeImmutable(), null);
        $customer = new UserIdentity('shared@test.com', StubUser::class);
        $admin = new UserIdentity('shared@test.com', StubAdminUser::class);

        $repository = $this->createMock(AuthenticationLogRepositoryInterface::class);
        $creator = $this->createStub(AuthenticationLogCreatorInterface::class);

        $repository->expects(self::exactly(2))
            ->method('findExistingLog')
            ->willReturnCallback(static fn (UserIdentity $userIdentity): bool => StubUser::class === $userIdentity->userClass);

        $handler = new DoctrineAuthenticationLogHandler($repository, $creator);

        self::assertTrue($handler->isKnown($customer, $userInformation));
        self::assertFalse($handler->isKnown($admin, $userInformation));
    }

    public function testItShouldCreateAndSaveLog(): void
    {
        // Arrange
        $userInformation = new UserInformation('127.0.0.1', 'PHPUnit', new \DateTimeImmutable(), null);
        $userIdentity = new UserIdentity('user-1', StubUser::class);
        $log = $this->createStub(AbstractAuthenticationLog::class);

        $repository = $this->createMock(AuthenticationLogRepositoryInterface::class);
        $creator = $this->createMock(AuthenticationLogCreatorInterface::class);
        $creator->method('createLog')->willReturn($log);

        // Act
        $creator->expects(self::once())
            ->method('createLog')
            ->with($userIdentity, $userInformation);
        $repository->expects(self::once())
            ->method('save')
            ->with($log);

        $handler = new DoctrineAuthenticationLogHandler($repository, $creator);
        $handler->handle($userIdentity, $userInformation);
    }

    public function testItShouldEnableConfirmationWhenGeneratorIsProvidedAndLogIsConfirmable(): void
    {
        $userInformation = new UserInformation('127.0.0.1', 'PHPUnit', new \DateTimeImmutable(), null);
        $log = new class(new UserIdentity('user-1', StubUser::class), $userInformation) extends AbstractAuthenticationLog implements ConfirmableAuthenticationLogInterface {
            use ConfirmableAuthenticationLogTrait;

            public function getUser(): AuthLogUserInterface
            {
                throw new \RuntimeException('Stub');
            }
        };

        $repository = $this->createStub(AuthenticationLogRepositoryInterface::class);
        $creator = $this->createStub(AuthenticationLogCreatorInterface::class);
        $creator->method('createLog')->willReturn($log);

        $handler = new DoctrineAuthenticationLogHandler($repository, $creator, new ConfirmationTokenGenerator());
        $result = $handler->handle(new UserIdentity('user-1', StubUser::class), $userInformation);

        self::assertSame($log, $result);
        self::assertNotNull($log->confirmationToken());
        self::assertTrue($log->isPending());
    }
}
