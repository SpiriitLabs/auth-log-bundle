<?php

declare(strict_types=1);

/*
 * This file is part of the SpiriitLabs php-excel-rust package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\Listener;

use Spiriit\Bundle\AuthLogBundle\Entity\AbstractAuthenticationLog;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;
use Symfony\Contracts\EventDispatcher\Event;

class AuthenticationLogEvent extends Event
{
    private bool $logPersisted = false;

    public function __construct(
        private AbstractAuthenticationLog $log,
        private UserInformation $userInformation,
    ) {
    }

    public function getLog(): AbstractAuthenticationLog
    {
        return $this->log;
    }

    public function getUserInformation(): UserInformation
    {
        return $this->userInformation;
    }

    public function isRegisterConfirmed(): bool
    {
        return $this->logPersisted;
    }

    public function markAsPersisted(): void
    {
        $this->logPersisted = true;
    }
}
