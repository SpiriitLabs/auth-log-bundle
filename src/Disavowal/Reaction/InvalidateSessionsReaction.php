<?php

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\Disavowal\Reaction;

use Spiriit\Bundle\AuthLogBundle\Disavowal\DisavowalReactionInterface;
use Spiriit\Bundle\AuthLogBundle\Disavowal\DisavowedLogin;
use Spiriit\Bundle\AuthLogBundle\Disavowal\SessionInvalidatorInterface;

final readonly class InvalidateSessionsReaction implements DisavowalReactionInterface
{
    public function __construct(
        private SessionInvalidatorInterface $sessionInvalidator,
    ) {
    }

    public function react(DisavowedLogin $disavowedLogin): void
    {
        $this->sessionInvalidator->invalidateSessions($disavowedLogin->user);
    }
}
