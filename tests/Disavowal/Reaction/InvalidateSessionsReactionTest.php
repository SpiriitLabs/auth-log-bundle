<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\Tests\Disavowal\Reaction;

use PHPUnit\Framework\TestCase;
use Spiriit\Bundle\AuthLogBundle\Disavowal\DisavowedLogin;
use Spiriit\Bundle\AuthLogBundle\Disavowal\Reaction\InvalidateSessionsReaction;
use Spiriit\Bundle\AuthLogBundle\Disavowal\SessionInvalidatorInterface;
use Spiriit\Bundle\AuthLogBundle\DTO\UserIdentity;
use Spiriit\Bundle\AuthLogBundle\Entity\ConfirmableAuthenticationLogInterface;
use Spiriit\Bundle\Tests\Stubs\StubUser;

final class InvalidateSessionsReactionTest extends TestCase
{
    public function testItShouldInvalidateTheSessionsOfTheDisavowingUser(): void
    {
        $user = new StubUser();

        $log = $this->createStub(ConfirmableAuthenticationLogInterface::class);
        $log->method('getUser')->willReturn($user);

        $sessionInvalidator = $this->createMock(SessionInvalidatorInterface::class);
        $sessionInvalidator->expects(self::once())->method('invalidateSessions')->with($user);

        $reaction = new InvalidateSessionsReaction($sessionInvalidator);

        $reaction->react(new DisavowedLogin($log, new UserIdentity('user@test.com', StubUser::class)));
    }
}
