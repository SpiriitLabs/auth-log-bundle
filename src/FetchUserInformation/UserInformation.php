<?php

declare(strict_types=1);

/*
 * This file is part of the SpiriitLabs php-excel-rust package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\FetchUserInformation;

use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\LocateUserInformation\LocateValues;

readonly class UserInformation
{
    public function __construct(
        public ?string $ipAddress,
        public ?string $userAgent,
        public ?\DateTimeImmutable $loginAt,
        public ?LocateValues $location,
    ) {
    }
}
