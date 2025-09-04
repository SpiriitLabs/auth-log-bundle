<?php

declare(strict_types=1);

/*
 * This file is part of the SpiriitLabs php-excel-rust package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\AuthenticationLogFactory;

use Spiriit\Bundle\AuthLogBundle\Entity\AbstractAuthenticationLog;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;

interface AuthenticationLogFactoryInterface
{
    public function supports(): string;

    /**
     * @param mixed[] $factoryParameters
     */
    public function createFrom(string $userIdentifier, UserInformation $userInformation): AbstractAuthenticationLog;

    public function isKnown(AbstractAuthenticationLog $authenticationLog): bool;
}
