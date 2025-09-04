<?php

declare(strict_types=1);

/*
 * This file is part of the SpiriitLabs php-excel-rust package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\Listener;

class AuthenticationLogEvents
{
    public const LOGIN = 'spiriit.auth_log.login';
    public const FAILED_LOGIN = 'spiriit.auth_log.failed_login';
}
