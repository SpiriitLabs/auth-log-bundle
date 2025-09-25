<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\DependencyInjection;

use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\FetchUserInformation;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\FetchUserInformationMethodInterface;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\LocateUserInformation\Geoip2LocateMethod;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\LocateUserInformation\IpApiLocateMethod;
use Spiriit\Bundle\AuthLogBundle\Notification\MailerNotification;
use Spiriit\Bundle\AuthLogBundle\Notification\NotificationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class SpiriitAuthLogExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('spiriit_auth_log.config', $config);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('new_device.php');

        if ('mailer' === $config['transports']['mailer']) {
            $container
                ->setAlias('spiriit_auth_log.transports.mailer', $config['transports']['mailer'])
                ->setPublic(false);

            $addresses = [
                'fromEmail' => $config['transports']['sender_email'],
                'fromName' => $config['transports']['sender_name'],
            ];

            $container->setAlias('spiriit_auth_log.translator', 'translator');

            $container->setDefinition('spiriit_auth_log.notification', new Definition(NotificationInterface::class))
                ->setClass(MailerNotification::class)
                ->setArgument('$mailer', new Reference('spiriit_auth_log.transports.mailer'))
                ->setArgument('$translator', new Reference('spiriit_auth_log.translator'))
                ->setArgument('$addresses', $addresses);
        } else {
            $container->setAlias('spiriit_auth_log.notification', $config['transports']['mailer'])->setPublic(false);
        }

        $container->setDefinition('spiriit_auth_log.fetch_user_information', new Definition(FetchUserInformation::class));

        if (!empty($config['location'])) {
            $this->loadLocateMethod($config, $container);
        }

        if ($container->hasDefinition('spiriit_auth_log.fetch_user_information_method')) {
            $container->getDefinition('spiriit_auth_log.fetch_user_information')
                ->addMethodCall(
                    'setLocateMethod',
                    [new Reference('spiriit_auth_log.fetch_user_information_method')]
                );
        }

        $container->setAlias('spiriit_auth_log.login_event_dispatcher', EventDispatcherInterface::class);

        if ($config['messenger']) {
            $definitionLoginListener = $container->getDefinition('spiriit_auth_log.login_listener');

            $loader->load('messenger.php');

            $definitionLoginListener->addMethodCall('setMessageBus', [
                new Reference($config['messenger']),
            ]);
        }
    }

    /**
     * @param mixed[] $configLocation
     */
    private function loadLocateMethod(array $configLocation, ContainerBuilder $container): void
    {
        $class = match ($configLocation['location']['provider']) {
            'ipApi' => IpApiLocateMethod::class,
            'geoip2' => Geoip2LocateMethod::class,
            default => null,
        };

        if (IpApiLocateMethod::class === $class) {
            $container->setDefinition('spiriit_auth_log.fetch_user_information_method', new Definition(FetchUserInformationMethodInterface::class))
                ->setClass(IpApiLocateMethod::class)
                ->setArgument('$httpClient', new Reference('spiriit_auth_log.http_client'))
            ;
        } elseif (Geoip2LocateMethod::class === $class) {
            $container->setDefinition('spiriit_auth_log.fetch_user_information_method', new Definition(FetchUserInformationMethodInterface::class))
                ->setClass(Geoip2LocateMethod::class)
                ->setArgument('$geoip2DatabasePath', $configLocation['location']['geoip2_database_path'] ?? null)
            ;
        }
    }
}
