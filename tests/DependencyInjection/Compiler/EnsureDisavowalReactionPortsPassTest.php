<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Spiriit\Bundle\AuthLogBundle\DependencyInjection\Compiler\EnsureDisavowalReactionPortsPass;
use Spiriit\Bundle\AuthLogBundle\Disavowal\Reaction\InvalidateSessionsReaction;
use Spiriit\Bundle\AuthLogBundle\Disavowal\Reaction\RevokeKnownContextsReaction;
use Spiriit\Bundle\AuthLogBundle\Disavowal\SessionInvalidatorInterface;
use Spiriit\Bundle\AuthLogBundle\Repository\RevocableAuthenticationLogRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class EnsureDisavowalReactionPortsPassTest extends TestCase
{
    public function testItShouldFailWhenAnEnabledReactionMissesItsPort(): void
    {
        $container = new ContainerBuilder();
        $container->register('spiriit_auth_log.disavowal_reaction.invalidate_sessions', InvalidateSessionsReaction::class);

        self::expectException(\LogicException::class);
        self::expectExceptionMessageMatches('/SessionInvalidatorInterface.*invalidate_sessions/s');

        (new EnsureDisavowalReactionPortsPass())->process($container);
    }

    public function testItShouldAcceptAnEnabledReactionWhosePortIsAliased(): void
    {
        $container = new ContainerBuilder();
        $container->register('app.session_invalidator', \stdClass::class);
        $container->setAlias(SessionInvalidatorInterface::class, 'app.session_invalidator');
        $container->register('spiriit_auth_log.disavowal_reaction.invalidate_sessions', InvalidateSessionsReaction::class);

        (new EnsureDisavowalReactionPortsPass())->process($container);

        self::assertTrue($container->hasDefinition('spiriit_auth_log.disavowal_reaction.invalidate_sessions'));
    }

    public function testItShouldIgnoreAMissingPortWhenTheReactionIsDisabled(): void
    {
        $container = new ContainerBuilder();

        (new EnsureDisavowalReactionPortsPass())->process($container);

        self::assertFalse($container->has(RevocableAuthenticationLogRepositoryInterface::class));
    }

    public function testItShouldCheckEveryEnabledReaction(): void
    {
        $container = new ContainerBuilder();
        $container->register('app.revocable_repository', \stdClass::class);
        $container->setAlias(RevocableAuthenticationLogRepositoryInterface::class, 'app.revocable_repository');
        $container->register('spiriit_auth_log.disavowal_reaction.revoke_known_contexts', RevokeKnownContextsReaction::class);
        $container->register('spiriit_auth_log.disavowal_reaction.invalidate_sessions', InvalidateSessionsReaction::class);

        self::expectException(\LogicException::class);
        self::expectExceptionMessageMatches('/invalidate_sessions/');

        (new EnsureDisavowalReactionPortsPass())->process($container);
    }
}
