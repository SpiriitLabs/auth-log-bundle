<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Spiriit\Bundle\AuthLogBundle\Confirmation\ConfirmationTokenGenerator;
use Spiriit\Bundle\AuthLogBundle\Confirmation\ConfirmationUrlGenerator;
use Spiriit\Bundle\AuthLogBundle\Controller\AuthenticationLogConfirmationController;
use Spiriit\Bundle\AuthLogBundle\Repository\ConfirmableAuthenticationLogRepositoryInterface;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('spiriit_auth_log.confirmation_token_generator', ConfirmationTokenGenerator::class);

    $services->set('spiriit_auth_log.confirmation_url_generator', ConfirmationUrlGenerator::class)
        ->args([
            service('router'),
            service('uri_signer'),
            '%spiriit_auth_log.confirmation.route_name%',
            '%spiriit_auth_log.confirmation.token_ttl%',
        ]);

    $services->set('spiriit_auth_log.confirmation_controller', AuthenticationLogConfirmationController::class)
        ->args([
            service('uri_signer'),
            service(ConfirmableAuthenticationLogRepositoryInterface::class),
            service('event_dispatcher'),
            service('twig'),
        ])
        ->public()
        ->tag('controller.service_arguments');
};
