<?php

/*
 * This file is part of the SpiriitLabs php-excel-rust package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\DTO;

readonly class LoginParameterDto
{
    public function __construct(
        public string $factoryName,
        public string $userIdentifier,
        public string $toEmail,
        public string $toEmailName,
        public string $clientIp,
        public string $userAgent,
    ) {
    }
}
