<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Spiriit\Bundle\AuthLogBundle\Listener\LoginListener;
use Spiriit\Bundle\AuthLogBundle\Services\LoginService;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Contracts\HttpClient\HttpClientInterface;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('spiriit_auth_log.login_service', LoginService::class)
        ->args([
            service('spiriit_auth_log.fetch_user_information'),
            service('spiriit_auth_log.handler'),
            service('spiriit_auth_log.notification'),
            service('spiriit_auth_log.login_event_dispatcher'),
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
