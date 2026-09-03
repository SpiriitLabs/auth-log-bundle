<?php

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\DependencyInjection\Compiler;

use Spiriit\Bundle\AuthLogBundle\AuthenticationLog\AuthenticationLogCreatorInterface;
use Spiriit\Bundle\AuthLogBundle\Disavowal\PasswordResetRequesterInterface;
use Spiriit\Bundle\AuthLogBundle\Disavowal\SessionInvalidatorInterface;
use Spiriit\Bundle\AuthLogBundle\Repository\AuthenticationLogRepositoryInterface;
use Spiriit\Bundle\AuthLogBundle\Repository\ConfirmableAuthenticationLogRepositoryInterface;
use Spiriit\Bundle\AuthLogBundle\Repository\RevocableAuthenticationLogRepositoryInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Aliases each bundle interface to the consumer implementation that carries the
 * matching tag, so a repository only has to implement the interfaces (the tags
 * are applied by autoconfiguration) without declaring the aliases by hand.
 */
final class RegisterTaggedImplementationsPass implements CompilerPassInterface
{
    private const TAGGED_INTERFACES = [
        'spiriit_auth_log.repository' => AuthenticationLogRepositoryInterface::class,
        'spiriit_auth_log.creator' => AuthenticationLogCreatorInterface::class,
        'spiriit_auth_log.confirmable_repository' => ConfirmableAuthenticationLogRepositoryInterface::class,
        'spiriit_auth_log.revocable_repository' => RevocableAuthenticationLogRepositoryInterface::class,
        'spiriit_auth_log.session_invalidator' => SessionInvalidatorInterface::class,
        'spiriit_auth_log.password_reset_requester' => PasswordResetRequesterInterface::class,
    ];

    public function process(ContainerBuilder $container): void
    {
        foreach (self::TAGGED_INTERFACES as $tag => $interface) {
            $this->aliasTaggedImplementation($container, $tag, $interface);
        }
    }

    private function aliasTaggedImplementation(ContainerBuilder $container, string $tag, string $interface): void
    {
        if ($container->has($interface)) {
            return;
        }

        $serviceIds = array_keys($container->findTaggedServiceIds($tag));

        if ([] === $serviceIds) {
            return;
        }

        if (\count($serviceIds) > 1) {
            throw new \LogicException(\sprintf('There are several services tagged "%s" (%s). Alias "%s" to the one to use instead.', $tag, implode(', ', $serviceIds), $interface));
        }

        $container->setAlias($interface, $serviceIds[0]);
    }
}
