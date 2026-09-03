<?php

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\Listener;

class AuthenticationLogEvents
{
    public const NEW_DEVICE = 'spiriit.auth_log.new_device';

    public const LOGIN_ACKNOWLEDGED = 'spiriit.auth_log.login_acknowledged';

    public const LOGIN_DISAVOWED = 'spiriit.auth_log.login_disavowed';
}
