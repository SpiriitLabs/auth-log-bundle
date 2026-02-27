<?php

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\Tests\Integration\Stubs;

use Spiriit\Bundle\AuthLogBundle\AuthenticationLog\AuthenticationLogCreatorInterface;
use Spiriit\Bundle\AuthLogBundle\Entity\AbstractAuthenticationLog;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;
use Spiriit\Bundle\AuthLogBundle\Repository\AuthenticationLogRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;

class BundleExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $repository = new Definition(StubAuthenticationLogRepository::class);
        $repository->setPublic(true);
        $container->setDefinition(AuthenticationLogRepositoryInterface::class, $repository);

        $creator = new Definition(StubAuthenticationLogCreator::class);
        $creator->setPublic(true);
        $container->setDefinition(AuthenticationLogCreatorInterface::class, $creator);
    }
}

/**
 * @internal
 */
class StubAuthenticationLogRepository implements AuthenticationLogRepositoryInterface
{
    public function save(AbstractAuthenticationLog $log): void
    {
    }

    public function findExistingLog(string $userIdentifier, UserInformation $userInformation): bool
    {
        return false;
    }
}

/**
 * @internal
 */
class StubAuthenticationLogCreator implements AuthenticationLogCreatorInterface
{
    public function createLog(string $userIdentifier, UserInformation $userInformation): AbstractAuthenticationLog
    {
        return new class($userInformation) extends AbstractAuthenticationLog {
            public function getUser(): \Spiriit\Bundle\AuthLogBundle\Entity\AuthLogUserInterface
            {
                throw new \RuntimeException('Stub');
            }
        };
    }
}
