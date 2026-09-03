<?php

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\Tests\Disavowal\Reaction;

use PHPUnit\Framework\TestCase;
use Spiriit\Bundle\AuthLogBundle\Disavowal\DisavowedLogin;
use Spiriit\Bundle\AuthLogBundle\Disavowal\Reaction\RevokeKnownContextsReaction;
use Spiriit\Bundle\AuthLogBundle\DTO\UserIdentity;
use Spiriit\Bundle\AuthLogBundle\Entity\ConfirmableAuthenticationLogInterface;
use Spiriit\Bundle\AuthLogBundle\Repository\RevocableAuthenticationLogRepositoryInterface;
use Spiriit\Bundle\Tests\Stubs\StubUser;

final class RevokeKnownContextsReactionTest extends TestCase
{
    public function testItShouldRevokeTheKnownContextsOfTheDisavowingUser(): void
    {
        $userIdentity = new UserIdentity('user@test.com', StubUser::class);

        $repository = $this->createMock(RevocableAuthenticationLogRepositoryInterface::class);
        $repository->expects(self::once())->method('revokeKnownContexts')->with($userIdentity);

        $reaction = new RevokeKnownContextsReaction($repository);

        $reaction->react(new DisavowedLogin(
            $this->createStub(ConfirmableAuthenticationLogInterface::class),
            new StubUser(),
            $userIdentity,
        ));
    }
}
