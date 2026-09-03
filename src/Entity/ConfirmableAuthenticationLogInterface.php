<?php

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\Entity;

use Spiriit\Bundle\AuthLogBundle\Confirmation\ConfirmationToken;

interface ConfirmableAuthenticationLogInterface extends AuthenticationLogInterface
{
    public function enableConfirmation(ConfirmationToken $confirmationToken): void;

    public function acknowledge(): void;

    public function disavow(): void;

    public function confirmationToken(): ?string;

    public function status(): AuthenticationLogStatus;

    public function respondedAt(): ?\DateTimeImmutable;

    public function isPending(): bool;
}
