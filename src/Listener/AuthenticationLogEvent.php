<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\Listener;

use Spiriit\Bundle\AuthLogBundle\DTO\UserReference;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;
use Symfony\Contracts\EventDispatcher\Event;

class AuthenticationLogEvent extends Event
{
    private bool $logHandled = false;

    public function __construct(
        private UserReference $userReference,
        private UserInformation $userInformation,
    ) {
    }

    public function getUserReference(): UserReference
    {
        return $this->userReference;
    }

    public function getUserInformation(): UserInformation
    {
        return $this->userInformation;
    }

    public function isLogHandled(): bool
    {
        return $this->logHandled;
    }

    public function markAsHandled(): void
    {
        $this->logHandled = true;
    }
}
