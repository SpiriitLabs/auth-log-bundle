<?php

declare(strict_types=1);

/*
 * This file is part of the SpiriitLabs php-excel-rust package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('spiriit_auth_log');

        /** @var ArrayNodeDefinition $rootNode */
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('messenger')
                    ->defaultFalse()
                    ->info('Enables integration with symfony/messenger if set true.')
                ->end()
                ->arrayNode('transports')
                    ->isRequired()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('mailer')
                            ->defaultValue('mailer')
                        ->end()
                        ->scalarNode('sender_email')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                        ->scalarNode('sender_name')
                            ->isRequired()
                            ->cannotBeEmpty()
                        ->end()
                    ->end()
                ->end()

                ->arrayNode('location')
                    ->canBeUnset()
                    ->children()
                        ->scalarNode('provider')
                            ->defaultNull()
                            ->validate()
                                ->ifNotInArray(['geoip2', 'ipApi', null])
                                ->thenInvalid('La méthode %s n\'est pas supportée. Choisir "geoip2" ou "ipApi".')
                            ->end()
                        ->end()
                        ->scalarNode('geoip2_database_path')
                            ->defaultNull()
                        ->end()
                    ->end()
                    ->validate()
                        ->ifTrue(function ($v): bool {
                            return null !== $v && ($v['provider'] ?? null) === 'geoip2' && empty($v['geoip2_database_path']);
                        })
                        ->thenInvalid('Le champ "geoip2_database_path" est requis si la méthode "geoip2" est utilisée.')
                    ->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
