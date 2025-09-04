<?php

/*
 * This file is part of the SpiriitLabs php-excel-rust package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\Messenger\AuthLoginMessage;

use Spiriit\Bundle\AuthLogBundle\Services\LoginService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class AuthLoginMessageHandler
{
    public function __construct(
        private LoginService $loginService,
    ) {
    }

    public function __invoke(AuthLoginMessage $message): void
    {
        $this->loginService->execute($message->loginParameterDto);
    }
}
