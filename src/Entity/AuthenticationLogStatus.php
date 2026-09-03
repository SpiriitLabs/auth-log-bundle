<?php

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\Entity;

enum AuthenticationLogStatus: string
{
    case PENDING = 'pending';
    case ACKNOWLEDGED = 'acknowledged';
    case DISAVOWED = 'disavowed';
    case REVOKED = 'revoked';
}
