<?php

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\Tests\Integration\Stubs;

use Spiriit\Bundle\AuthLogBundle\AuthenticationLog\AuthenticationLogCreatorInterface;
use Spiriit\Bundle\AuthLogBundle\DTO\UserIdentity;
use Spiriit\Bundle\AuthLogBundle\Entity\AbstractAuthenticationLog;
use Spiriit\Bundle\AuthLogBundle\Entity\AuthenticationLogInterface;
use Spiriit\Bundle\AuthLogBundle\Entity\ConfirmableAuthenticationLogInterface;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;
use Spiriit\Bundle\AuthLogBundle\Notification\NewDeviceNotification;
use Spiriit\Bundle\AuthLogBundle\Notification\NotificationInterface;
use Spiriit\Bundle\AuthLogBundle\Repository\AuthenticationLogRepositoryInterface;
use Spiriit\Bundle\AuthLogBundle\Repository\ConfirmableAuthenticationLogRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;

class BundleExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $repository = new Definition(StubAuthenticationLogRepository::class);
        $repository->setPublic(true);
        $repository->setAutoconfigured(true);
        $container->setDefinition(StubAuthenticationLogRepository::class, $repository);

        $creator = new Definition(StubAuthenticationLogCreator::class);
        $creator->setPublic(true);
        $creator->setAutoconfigured(true);
        $container->setDefinition(StubAuthenticationLogCreator::class, $creator);

        $notification = new Definition(StubNotification::class);
        $notification->setPublic(true);
        $container->setDefinition('app.custom_notification', $notification);
    }
}

/**
 * Mirrors the README: a single repository implements every bundle interface,
 * so autoconfiguration tags it and the bundle aliases the interfaces to it.
 *
 * @internal
 */
class StubAuthenticationLogRepository implements AuthenticationLogRepositoryInterface, ConfirmableAuthenticationLogRepositoryInterface
{
    public function save(AuthenticationLogInterface $log): void
    {
    }

    public function findExistingLog(UserIdentity $userIdentity, UserInformation $userInformation): bool
    {
        return false;
    }

    public function findOneByConfirmationToken(string $confirmationToken): ?ConfirmableAuthenticationLogInterface
    {
        return null;
    }
}

/**
 * @internal
 */
class StubAuthenticationLogCreator implements AuthenticationLogCreatorInterface
{
    public function createLog(UserIdentity $userIdentity, UserInformation $userInformation): AuthenticationLogInterface
    {
        return new class($userIdentity, $userInformation) extends AbstractAuthenticationLog {
            public function getUser(): \Spiriit\Bundle\AuthLogBundle\Entity\AuthLogUserInterface
            {
                throw new \RuntimeException('Stub');
            }
        };
    }
}

/**
 * @internal
 */
class StubNotification implements NotificationInterface
{
    public function send(NewDeviceNotification $notification): void
    {
    }
}
