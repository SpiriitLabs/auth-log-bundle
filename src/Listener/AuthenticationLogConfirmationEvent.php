<?php

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\Listener;

use Spiriit\Bundle\AuthLogBundle\Entity\ConfirmableAuthenticationLogInterface;
use Symfony\Contracts\EventDispatcher\Event;

class AuthenticationLogConfirmationEvent extends Event
{
    public function __construct(
        private readonly ConfirmableAuthenticationLogInterface $authenticationLog,
    ) {
    }

    public function authenticationLog(): ConfirmableAuthenticationLogInterface
    {
        return $this->authenticationLog;
    }
}
