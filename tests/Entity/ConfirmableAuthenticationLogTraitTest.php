<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Spiriit\Bundle\AuthLogBundle\Confirmation\ConfirmationToken;
use Spiriit\Bundle\AuthLogBundle\Confirmation\Exception\AuthenticationLogAlreadyReviewedException;
use Spiriit\Bundle\AuthLogBundle\DTO\UserIdentity;
use Spiriit\Bundle\AuthLogBundle\Entity\AbstractAuthenticationLog;
use Spiriit\Bundle\AuthLogBundle\Entity\AuthenticationLogStatus;
use Spiriit\Bundle\AuthLogBundle\Entity\AuthLogUserInterface;
use Spiriit\Bundle\AuthLogBundle\Entity\ConfirmableAuthenticationLogInterface;
use Spiriit\Bundle\AuthLogBundle\Entity\ConfirmableAuthenticationLogTrait;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;
use Spiriit\Bundle\Tests\Stubs\StubUser;

final class ConfirmableAuthenticationLogTraitTest extends TestCase
{
    public function testItShouldBePendingOnceConfirmationIsEnabled(): void
    {
        $log = $this->createConfirmableLog();

        $log->enableConfirmation(new ConfirmationToken('the-token'));

        self::assertTrue($log->isPending());
        self::assertSame('the-token', $log->confirmationToken());
        self::assertSame(AuthenticationLogStatus::PENDING, $log->status());
        self::assertNull($log->respondedAt());
    }

    public function testItShouldAcknowledgeAPendingLog(): void
    {
        $log = $this->createConfirmableLog();
        $log->enableConfirmation(new ConfirmationToken('the-token'));

        $log->acknowledge();

        self::assertSame(AuthenticationLogStatus::ACKNOWLEDGED, $log->status());
        self::assertFalse($log->isPending());
        self::assertNotNull($log->respondedAt());
    }

    public function testItShouldDisavowAPendingLog(): void
    {
        $log = $this->createConfirmableLog();
        $log->enableConfirmation(new ConfirmationToken('the-token'));

        $log->disavow();

        self::assertSame(AuthenticationLogStatus::DISAVOWED, $log->status());
        self::assertFalse($log->isPending());
        self::assertNotNull($log->respondedAt());
    }

    public function testItShouldRejectReviewingAnAlreadyReviewedLog(): void
    {
        $log = $this->createConfirmableLog();
        $log->enableConfirmation(new ConfirmationToken('the-token'));
        $log->acknowledge();

        self::expectException(AuthenticationLogAlreadyReviewedException::class);

        $log->disavow();
    }

    public function testItShouldRejectEnablingConfirmationOnAnAlreadyReviewedLog(): void
    {
        $log = $this->createConfirmableLog();
        $log->enableConfirmation(new ConfirmationToken('the-token'));
        $log->acknowledge();

        self::expectException(AuthenticationLogAlreadyReviewedException::class);

        $log->enableConfirmation(new ConfirmationToken('another-token'));
    }

    private function createConfirmableLog(): ConfirmableAuthenticationLogInterface&AbstractAuthenticationLog
    {
        $userInformation = new UserInformation('127.0.0.1', 'PHPUnit', new \DateTimeImmutable(), null);

        return new class(new UserIdentity('user-1', StubUser::class), $userInformation) extends AbstractAuthenticationLog implements ConfirmableAuthenticationLogInterface {
            use ConfirmableAuthenticationLogTrait;

            public function getUser(): AuthLogUserInterface
            {
                throw new \RuntimeException('Stub');
            }
        };
    }
}
