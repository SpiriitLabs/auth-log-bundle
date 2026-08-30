<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\Listener;

use Spiriit\Bundle\AuthLogBundle\DTO\UserIdentity;
use Spiriit\Bundle\AuthLogBundle\Entity\AuthenticationLogInterface;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;
use Symfony\Contracts\EventDispatcher\Event;

class AuthenticationLogEvent extends Event
{
    public function __construct(
        private readonly UserIdentity $userIdentity,
        private readonly UserInformation $userInformation,
        private readonly AuthenticationLogInterface $authenticationLog,
    ) {
    }

    public function userIdentity(): UserIdentity
    {
        return $this->userIdentity;
    }

    public function userIdentifier(): string
    {
        return $this->userIdentity->userIdentifier;
    }

    public function userInformation(): UserInformation
    {
        return $this->userInformation;
    }

    public function authenticationLog(): AuthenticationLogInterface
    {
        return $this->authenticationLog;
    }
}
