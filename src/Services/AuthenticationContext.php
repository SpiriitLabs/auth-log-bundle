<?php

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\Services;

use Spiriit\Bundle\AuthLogBundle\AuthenticationLogFactory\AuthenticationLogFactoryInterface;
use Spiriit\Bundle\AuthLogBundle\DTO\UserReference;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;

readonly class AuthenticationContext
{
    public function __construct(
        public AuthenticationLogFactoryInterface $authenticationLogFactory,
        public UserReference $userReference,
        public UserInformation $userInformation,
    ) {
    }

    public function isKnown(): bool
    {
        return $this->authenticationLogFactory->isKnown($this->userReference, $this->userInformation);
    }
}
