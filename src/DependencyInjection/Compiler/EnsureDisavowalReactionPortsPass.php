<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\DependencyInjection\Compiler;

use Spiriit\Bundle\AuthLogBundle\Disavowal\PasswordResetRequesterInterface;
use Spiriit\Bundle\AuthLogBundle\Disavowal\SessionInvalidatorInterface;
use Spiriit\Bundle\AuthLogBundle\Repository\RevocableAuthenticationLogRepositoryInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Fails container compilation with an actionable message when an enabled
 * disavowal reaction is missing its application-side port implementation.
 */
final class EnsureDisavowalReactionPortsPass implements CompilerPassInterface
{
    private const REACTION_PORTS = [
        'spiriit_auth_log.disavowal_reaction.revoke_known_contexts' => [RevocableAuthenticationLogRepositoryInterface::class, 'revoke_known_contexts'],
        'spiriit_auth_log.disavowal_reaction.invalidate_sessions' => [SessionInvalidatorInterface::class, 'invalidate_sessions'],
        'spiriit_auth_log.disavowal_reaction.force_password_reset' => [PasswordResetRequesterInterface::class, 'force_password_reset'],
    ];

    public function process(ContainerBuilder $container): void
    {
        foreach (self::REACTION_PORTS as $reactionId => [$portInterface, $configKey]) {
            if ($container->hasDefinition($reactionId) && !$container->has($portInterface)) {
                throw new \LogicException(\sprintf('There is no service implementing "%s", although the "%s" disavowal reaction is enabled. Implement the interface (autoconfiguration registers it automatically) or set "spiriit_auth_log.confirmation.on_disavowal.%s" to false.', $portInterface, $configKey, $configKey));
            }
        }
    }
}
