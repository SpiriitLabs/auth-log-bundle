<?php

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\DTO;

final readonly class UserReference
{
    public function __construct(
        public string $userIdentifier,
        public string $email,
        public string $displayName,
    ) {
    }
}
