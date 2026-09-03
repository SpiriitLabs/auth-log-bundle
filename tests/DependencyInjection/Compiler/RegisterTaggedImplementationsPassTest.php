<?php

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Spiriit\Bundle\AuthLogBundle\DependencyInjection\Compiler\RegisterTaggedImplementationsPass;
use Spiriit\Bundle\AuthLogBundle\Disavowal\PasswordResetRequesterInterface;
use Spiriit\Bundle\AuthLogBundle\Disavowal\SessionInvalidatorInterface;
use Spiriit\Bundle\AuthLogBundle\Repository\AuthenticationLogRepositoryInterface;
use Spiriit\Bundle\AuthLogBundle\Repository\ConfirmableAuthenticationLogRepositoryInterface;
use Spiriit\Bundle\AuthLogBundle\Repository\RevocableAuthenticationLogRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RegisterTaggedImplementationsPassTest extends TestCase
{
    public function testItShouldAliasInterfaceToTaggedImplementation(): void
    {
        $container = new ContainerBuilder();
        $container->register('app.auth_log_repository', \stdClass::class)
            ->addTag('spiriit_auth_log.confirmable_repository');

        (new RegisterTaggedImplementationsPass())->process($container);

        self::assertTrue($container->hasAlias(ConfirmableAuthenticationLogRepositoryInterface::class));
        self::assertSame('app.auth_log_repository', (string) $container->getAlias(ConfirmableAuthenticationLogRepositoryInterface::class));
    }

    public function testItShouldAliasTheDisavowalPortInterfacesToTheirTaggedImplementations(): void
    {
        $container = new ContainerBuilder();
        $container->register('app.revocable_repository', \stdClass::class)
            ->addTag('spiriit_auth_log.revocable_repository');
        $container->register('app.session_invalidator', \stdClass::class)
            ->addTag('spiriit_auth_log.session_invalidator');
        $container->register('app.password_reset_requester', \stdClass::class)
            ->addTag('spiriit_auth_log.password_reset_requester');

        (new RegisterTaggedImplementationsPass())->process($container);

        self::assertSame('app.revocable_repository', (string) $container->getAlias(RevocableAuthenticationLogRepositoryInterface::class));
        self::assertSame('app.session_invalidator', (string) $container->getAlias(SessionInvalidatorInterface::class));
        self::assertSame('app.password_reset_requester', (string) $container->getAlias(PasswordResetRequesterInterface::class));
    }

    public function testItShouldNotOverrideAnExistingWiring(): void
    {
        $container = new ContainerBuilder();
        $container->register('app.tagged_repository', \stdClass::class)
            ->addTag('spiriit_auth_log.repository');
        $container->register(AuthenticationLogRepositoryInterface::class, \stdClass::class);

        (new RegisterTaggedImplementationsPass())->process($container);

        self::assertFalse($container->hasAlias(AuthenticationLogRepositoryInterface::class));
        self::assertTrue($container->hasDefinition(AuthenticationLogRepositoryInterface::class));
    }

    public function testItShouldFailWhenSeveralServicesAreTagged(): void
    {
        $container = new ContainerBuilder();
        $container->register('app.repository_one', \stdClass::class)
            ->addTag('spiriit_auth_log.repository');
        $container->register('app.repository_two', \stdClass::class)
            ->addTag('spiriit_auth_log.repository');

        self::expectException(\LogicException::class);

        (new RegisterTaggedImplementationsPass())->process($container);
    }

    public function testItShouldDoNothingWhenNoServiceIsTagged(): void
    {
        $container = new ContainerBuilder();

        (new RegisterTaggedImplementationsPass())->process($container);

        self::assertFalse($container->has(ConfirmableAuthenticationLogRepositoryInterface::class));
    }
}
