<?php

/*
 * This file is part of the SpiriitLabs php-excel-rust package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\Messenger\AuthLoginMessage;

use Spiriit\Bundle\AuthLogBundle\DTO\LoginParameterDto;

readonly class AuthLoginMessage
{
    public function __construct(
        public LoginParameterDto $loginParameterDto,
    ) {
    }
}
