<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\Listener;

use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;
use Symfony\Contracts\EventDispatcher\Event;

class AuthenticationLogEvent extends Event
{
    public function __construct(
        private readonly string $userIdentifier,
        private readonly UserInformation $userInformation,
    ) {
    }

    public function userIdentifier(): string
    {
        return $this->userIdentifier;
    }

    public function userInformation(): UserInformation
    {
        return $this->userInformation;
    }
}
