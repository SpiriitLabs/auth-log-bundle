<?php

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\Tests\Disavowal;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Spiriit\Bundle\AuthLogBundle\Disavowal\DisavowalReactionExecutor;
use Spiriit\Bundle\AuthLogBundle\Disavowal\DisavowalReactionInterface;
use Spiriit\Bundle\AuthLogBundle\Disavowal\DisavowedLogin;
use Spiriit\Bundle\AuthLogBundle\DTO\UserIdentity;
use Spiriit\Bundle\AuthLogBundle\Entity\AbstractAuthenticationLog;
use Spiriit\Bundle\AuthLogBundle\Entity\AuthLogUserInterface;
use Spiriit\Bundle\AuthLogBundle\Entity\ConfirmableAuthenticationLogInterface;
use Spiriit\Bundle\AuthLogBundle\Entity\ConfirmableAuthenticationLogTrait;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;
use Spiriit\Bundle\Tests\Stubs\StubAdminUser;
use Spiriit\Bundle\Tests\Stubs\StubUser;

final class DisavowalReactionExecutorTest extends TestCase
{
    public function testItShouldExecuteEveryReactionWithTheDisavowedLogin(): void
    {
        $log = $this->disavowedLog();

        $assertDisavowedLogin = self::callback(static fn (DisavowedLogin $disavowedLogin): bool => $disavowedLogin->authenticationLog === $log
            && $disavowedLogin->user instanceof StubUser
            && 'user@test.com' === $disavowedLogin->userIdentity->userIdentifier
            && StubUser::class === $disavowedLogin->userIdentity->userClass);

        $firstReaction = $this->createMock(DisavowalReactionInterface::class);
        $firstReaction->expects(self::once())->method('react')->with($assertDisavowedLogin);

        $secondReaction = $this->createMock(DisavowalReactionInterface::class);
        $secondReaction->expects(self::once())->method('react')->with($assertDisavowedLogin);

        (new DisavowalReactionExecutor([$firstReaction, $secondReaction]))->execute($log);
    }

    public function testItShouldContinueAndLogWhenAReactionFails(): void
    {
        $failingReaction = $this->createMock(DisavowalReactionInterface::class);
        $failingReaction->method('react')->willThrowException(new \RuntimeException('Sessions storage is down.'));

        $nextReaction = $this->createMock(DisavowalReactionInterface::class);
        $nextReaction->expects(self::once())->method('react');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with(self::anything(), self::callback(static fn (array $context): bool => 'Sessions storage is down.' === $context['message']));

        (new DisavowalReactionExecutor([$failingReaction, $nextReaction], $logger))->execute($this->disavowedLog());
    }

    public function testItShouldSkipReactionsAndLogWhenTheUserCannotBeResolved(): void
    {
        $log = $this->createStub(ConfirmableAuthenticationLogInterface::class);
        $log->method('getUser')->willThrowException(new \RuntimeException('User row is gone.'));

        $reaction = $this->createMock(DisavowalReactionInterface::class);
        $reaction->expects(self::never())->method('react');

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error')
            ->with(self::anything(), self::callback(static fn (array $context): bool => 'User row is gone.' === $context['message']));

        (new DisavowalReactionExecutor([$reaction], $logger))->execute($log);
    }

    public function testItShouldTakeTheIdentityFromTheLogNotFromTheResolvedUser(): void
    {
        $userInformation = new UserInformation('127.0.0.1', 'PHPUnit', new \DateTimeImmutable(), null);

        $log = new class(new UserIdentity('user@test.com', StubUser::class), $userInformation) extends AbstractAuthenticationLog implements ConfirmableAuthenticationLogInterface {
            use ConfirmableAuthenticationLogTrait;

            public function getUser(): AuthLogUserInterface
            {
                return new StubAdminUser('someone.else@test.com');
            }
        };

        $reaction = $this->createMock(DisavowalReactionInterface::class);
        $reaction->expects(self::once())
            ->method('react')
            ->with(self::callback(static fn (DisavowedLogin $disavowedLogin): bool => 'user@test.com' === $disavowedLogin->userIdentity->userIdentifier
                && StubUser::class === $disavowedLogin->userIdentity->userClass));

        (new DisavowalReactionExecutor([$reaction]))->execute($log);
    }

    public function testItShouldResolveTheUserOnlyOnceForEveryReaction(): void
    {
        $log = $this->createMock(ConfirmableAuthenticationLogInterface::class);
        $log->expects(self::once())->method('getUser')->willReturn(new StubUser());
        $log->method('userIdentity')->willReturn(new UserIdentity('user@test.com', StubUser::class));

        $reactions = [$this->createMock(DisavowalReactionInterface::class), $this->createMock(DisavowalReactionInterface::class)];

        (new DisavowalReactionExecutor($reactions))->execute($log);
    }

    public function testItShouldDoNothingWithoutReactions(): void
    {
        (new DisavowalReactionExecutor([]))->execute($this->disavowedLog());

        self::expectNotToPerformAssertions();
    }

    public function testItShouldWorkWithoutLogger(): void
    {
        $failingReaction = $this->createMock(DisavowalReactionInterface::class);
        $failingReaction->method('react')->willThrowException(new \RuntimeException('Boom.'));

        (new DisavowalReactionExecutor([$failingReaction]))->execute($this->disavowedLog());

        self::expectNotToPerformAssertions();
    }

    private function disavowedLog(): ConfirmableAuthenticationLogInterface
    {
        $userInformation = new UserInformation('127.0.0.1', 'PHPUnit', new \DateTimeImmutable(), null);

        return new class(new UserIdentity('user@test.com', StubUser::class), $userInformation) extends AbstractAuthenticationLog implements ConfirmableAuthenticationLogInterface {
            use ConfirmableAuthenticationLogTrait;

            public function getUser(): AuthLogUserInterface
            {
                return new StubUser();
            }
        };
    }
}
