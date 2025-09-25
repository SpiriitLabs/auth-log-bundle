<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Spiriit\Bundle\AuthLogBundle\AuthenticationLogFactory\FetchAuthenticationLogFactory;
use Spiriit\Bundle\AuthLogBundle\Listener\LoginListener;
use Spiriit\Bundle\AuthLogBundle\Services\AuthenticationContextBuilder;
use Spiriit\Bundle\AuthLogBundle\Services\AuthenticationEventPublisher;
use Spiriit\Bundle\AuthLogBundle\Services\LoginService;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Contracts\HttpClient\HttpClientInterface;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('spiriit_auth_log.authentication_context_builder', AuthenticationContextBuilder::class)
        ->args([
            service('spiriit_auth_log.fetch_authentication_log_factory'),
            service('spiriit_auth_log.fetch_user_information'),
        ]);

    $services->set('spiriit_auth_log.authentication_event_publisher', AuthenticationEventPublisher::class)
        ->args([
            service('spiriit_auth_log.login_event_dispatcher'),
            service('spiriit_auth_log.notification'),
        ]);

    $services->set('spiriit_auth_log.login_service', LoginService::class)
        ->args([
            service('spiriit_auth_log.authentication_context_builder'),
            service('spiriit_auth_log.authentication_event_publisher'),
        ]);

    $services->set('spiriit_auth_log.fetch_authentication_log_factory', FetchAuthenticationLogFactory::class)
        ->args([
            tagged_iterator('spiriit_auth_log.factory'),
        ]);

    $services
        ->set('spiriit_auth_log.login_listener', LoginListener::class)
        ->args([
            service('spiriit_auth_log.login_service'),
        ])
        ->tag('kernel.event_listener', [
            'event' => LoginSuccessEvent::class,
            'method' => 'onLogin',
        ]);

    $services->set('spiriit_auth_log.http_client', HttpClientInterface::class)
        ->factory([HttpClient::class, 'create'])
        ->tag('http_client.client');
};
