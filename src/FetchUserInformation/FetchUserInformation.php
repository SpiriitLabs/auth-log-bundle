<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\FetchUserInformation;

class FetchUserInformation
{
    private ?FetchUserInformationMethodInterface $fetchUserInformationMethod = null;

    public function fetch(string $clientIp, string $userAgent): UserInformation
    {
        $ipAddress = $clientIp;
        $loginAt = new \DateTimeImmutable();

        if (null !== $this->fetchUserInformationMethod) {
            $location = $this->fetchUserInformationMethod->locate($ipAddress);
        } else {
            $location = [];
        }

        return new UserInformation(
            ipAddress: $ipAddress,
            userAgent: $userAgent,
            loginAt: $loginAt,
            location: $location
        );
    }

    public function setLocateMethod(FetchUserInformationMethodInterface $fetchUserInformationMethod): void
    {
        $this->fetchUserInformationMethod = $fetchUserInformationMethod;
    }
}
