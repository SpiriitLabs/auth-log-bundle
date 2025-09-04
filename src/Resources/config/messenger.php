<?php

declare(strict_types=1);

/*
 * This file is part of the SpiriitLabs php-excel-rust package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Spiriit\Bundle\AuthLogBundle\Messenger\AuthLoginMessage\AuthLoginMessage;
use Spiriit\Bundle\AuthLogBundle\Messenger\AuthLoginMessage\AuthLoginMessageHandler;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services
        ->set('spiriit_auth_log.login_message_handler', AuthLoginMessageHandler::class)
        ->args([
            service('spiriit_auth_log.login_service'),
        ])
        ->tag('messenger.message_handler', [
            'handles' => AuthLoginMessage::class,
        ])
        ->private();
};
