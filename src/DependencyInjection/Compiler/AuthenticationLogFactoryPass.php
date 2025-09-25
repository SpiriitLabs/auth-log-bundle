<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\DependencyInjection\Compiler;

use Spiriit\Bundle\AuthLogBundle\AuthenticationLogFactory\AuthenticationLogFactoryInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class AuthenticationLogFactoryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        foreach ($container->getDefinitions() as $definition) {
            $class = $definition->getClass();
            if (!$class) {
                continue;
            }
            if (!class_exists($class, false)) {
                continue;
            }

            if (is_subclass_of($class, AuthenticationLogFactoryInterface::class)) {
                $definition->addTag('spiriit_auth_log.factory');
            }
        }
    }
}
