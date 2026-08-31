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
use Spiriit\Bundle\AuthLogBundle\Disavowal\PasswordResetRequesterInterface;
use Spiriit\Bundle\AuthLogBundle\Disavowal\Reaction\ForcePasswordResetReaction;
use Spiriit\Bundle\AuthLogBundle\DTO\UserIdentity;
use Spiriit\Bundle\AuthLogBundle\Entity\ConfirmableAuthenticationLogInterface;
use Spiriit\Bundle\Tests\Stubs\StubUser;

final class ForcePasswordResetReactionTest extends TestCase
{
    public function testItShouldRequestAPasswordResetForTheDisavowingUser(): void
    {
        $user = new StubUser();

        $log = $this->createStub(ConfirmableAuthenticationLogInterface::class);
        $log->method('getUser')->willReturn($user);

        $passwordResetRequester = $this->createMock(PasswordResetRequesterInterface::class);
        $passwordResetRequester->expects(self::once())->method('requestPasswordReset')->with($user);

        $reaction = new ForcePasswordResetReaction($passwordResetRequester);

        $reaction->react(new DisavowedLogin($log, new UserIdentity('user@test.com', StubUser::class)));
    }
}
