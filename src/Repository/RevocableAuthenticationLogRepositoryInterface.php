<?php

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\Repository;

use Spiriit\Bundle\AuthLogBundle\DTO\UserIdentity;

interface RevocableAuthenticationLogRepositoryInterface
{
    public function revokeKnownContexts(UserIdentity $userIdentity): void;
}
