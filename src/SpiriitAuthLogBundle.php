<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle;

use Spiriit\Bundle\AuthLogBundle\AuthenticationLog\AuthenticationLogCreatorInterface;
use Spiriit\Bundle\AuthLogBundle\AuthenticationLog\AuthenticationLogHandlerInterface;
use Spiriit\Bundle\AuthLogBundle\AuthenticationLog\DoctrineAuthenticationLogHandler;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\FetchUserInformation;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\FetchUserInformationMethodInterface;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\LocateUserInformation\Geoip2LocateMethod;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\LocateUserInformation\IpApiLocateMethod;
use Spiriit\Bundle\AuthLogBundle\Notification\MailerNotification;
use Spiriit\Bundle\AuthLogBundle\Notification\NotificationInterface;
use Spiriit\Bundle\AuthLogBundle\Repository\AuthenticationLogRepositoryInterface;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class SpiriitAuthLogBundle extends AbstractBundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->registerForAutoconfiguration(AuthenticationLogRepositoryInterface::class)
            ->addTag('spiriit_auth_log.repository');
        $container->registerForAutoconfiguration(AuthenticationLogCreatorInterface::class)
            ->addTag('spiriit_auth_log.creator');
        $container->registerForAutoconfiguration(AuthenticationLogHandlerInterface::class)
            ->addTag('spiriit_auth_log.handler');
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        /** @var \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition $rootNode */
        $rootNode = $definition->rootNode();
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
                                ->thenInvalid('The method "%s" is not supported. Use "geoip2" or "ipApi".')
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
                        ->thenInvalid('The "geoip2_database_path" field is required when using the "geoip2" provider.')
                    ->end()
                ->end()
            ->end()
        ;
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->setParameter('spiriit_auth_log.config', $config);

        $container->import('Resources/config/new_device.php');

        // Notification
        if ('mailer' === $config['transports']['mailer']) {
            $builder
                ->setAlias('spiriit_auth_log.transports.mailer', $config['transports']['mailer']);

            $addresses = [
                'fromEmail' => $config['transports']['sender_email'],
                'fromName' => $config['transports']['sender_name'],
            ];

            $builder->setAlias('spiriit_auth_log.translator', 'translator');

            $builder->setDefinition('spiriit_auth_log.notification', new Definition(NotificationInterface::class))
                ->setClass(MailerNotification::class)
                ->setArgument('$mailer', new Reference('spiriit_auth_log.transports.mailer'))
                ->setArgument('$translator', new Reference('spiriit_auth_log.translator'))
                ->setArgument('$addresses', $addresses)
                ->setPublic(true);
        } else {
            $builder->setAlias('spiriit_auth_log.notification', $config['transports']['mailer'])->setPublic(false);
        }

        // FetchUserInformation
        $builder->setDefinition('spiriit_auth_log.fetch_user_information', new Definition(FetchUserInformation::class))
            ->setPublic(true);

        // Location provider
        if (!empty($config['location'])) {
            $this->loadLocateMethod($config, $builder);
        }

        if ($builder->hasDefinition('spiriit_auth_log.fetch_user_information_method')) {
            $builder->getDefinition('spiriit_auth_log.fetch_user_information')
                ->addMethodCall(
                    'setLocateMethod',
                    [new Reference('spiriit_auth_log.fetch_user_information_method')]
                );
        }

        // Handler (DoctrineAuthenticationLogHandler)
        $builder->setDefinition('spiriit_auth_log.handler', new Definition(AuthenticationLogHandlerInterface::class))
            ->setClass(DoctrineAuthenticationLogHandler::class)
            ->setArgument('$repository', new Reference(AuthenticationLogRepositoryInterface::class))
            ->setArgument('$creator', new Reference(AuthenticationLogCreatorInterface::class));

        // Event dispatcher alias
        $builder->setAlias('spiriit_auth_log.login_event_dispatcher', 'event_dispatcher');

        // Messenger (async)
        if ($config['messenger']) {
            $container->import('Resources/config/messenger.php');

            $builder->getDefinition('spiriit_auth_log.login_listener')
                ->setArgument('$messageBus', new Reference($config['messenger']));
        }
    }

    /**
     * @param mixed[] $config
     */
    private function loadLocateMethod(array $config, ContainerBuilder $container): void
    {
        $class = match ($config['location']['provider']) {
            'ipApi' => IpApiLocateMethod::class,
            'geoip2' => Geoip2LocateMethod::class,
            default => null,
        };

        if (IpApiLocateMethod::class === $class) {
            $container->setDefinition('spiriit_auth_log.fetch_user_information_method', new Definition(FetchUserInformationMethodInterface::class))
                ->setClass(IpApiLocateMethod::class)
                ->setArgument('$httpClient', new Reference('spiriit_auth_log.http_client'))
                ->setPublic(true);
        } elseif (Geoip2LocateMethod::class === $class) {
            $container->setDefinition('spiriit_auth_log.fetch_user_information_method', new Definition(FetchUserInformationMethodInterface::class))
                ->setClass(Geoip2LocateMethod::class)
                ->setArgument('$geoip2DatabasePath', $config['location']['geoip2_database_path'] ?? null)
                ->setPublic(true);
        }
    }
}
