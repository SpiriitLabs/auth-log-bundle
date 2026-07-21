<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Routing\Loader\Configurator;

/*
 * Optional default route for the confirmation feature. Import it from your app
 * (optionally with a "prefix") to get a ready-to-use route, or declare your own
 * route pointing to "spiriit_auth_log.confirmation_controller" and set
 * "spiriit_auth_log.confirmation.route_name" to its name instead.
 */
return static function (RoutingConfigurator $routes): void {
    $routes->add('spiriit_auth_log_confirm', '/auth-log/confirm/{action}/{token}')
        ->controller('spiriit_auth_log.confirmation_controller')
        ->methods(['GET', 'POST'])
        ->requirements(['action' => 'acknowledge|disavow']);
};
