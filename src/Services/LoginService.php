<?php

/*
 * This file is part of the SpiriitLabs php-excel-rust package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\Services;

use Spiriit\Bundle\AuthLogBundle\DTO\LoginParameterDto;

class LoginService
{
    public function __construct(
        private readonly AuthenticationContextBuilder $contextBuilder,
        private readonly AuthenticationEventPublisher $publisher,
    ) {
    }

    public function execute(LoginParameterDto $loginParameterDto): void
    {
        $context = $this->contextBuilder->build($loginParameterDto);

        if (!$context->authLog->isKnown($context->authenticationLog)) {
            $this->publisher->publish($context);
        }
    }
}
