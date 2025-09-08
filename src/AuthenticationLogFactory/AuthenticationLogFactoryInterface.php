<?php

declare(strict_types=1);

/*
 * This file is part of the SpiriitLabs php-excel-rust package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\AuthenticationLogFactory;

use Spiriit\Bundle\AuthLogBundle\DTO\UserReference;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;

interface AuthenticationLogFactoryInterface
{
    public function supports(): string;

    public function createUserReference(string $userIdentifier): UserReference;

    public function isKnown(UserReference $userReference, UserInformation $userInformation): bool;
}
