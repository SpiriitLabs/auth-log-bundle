<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\Entity;

use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\LocateUserInformation\LocateValues;

interface AuthenticationLogInterface
{
    public function getUser(): AuthLogUserInterface;

    public function getUserClass(): string;

    public function getIpAddress(): ?string;

    public function getUserAgent(): ?string;

    public function getLoginAt(): ?\DateTimeImmutable;

    public function getLocation(): ?LocateValues;
}
