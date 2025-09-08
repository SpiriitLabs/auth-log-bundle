<?php

/*
 * This file is part of the SpiriitLabs php-excel-rust package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\DTO;

final class UserReference
{
    private ?string $email = null;
    private ?string $displayName = null;

    public function __construct(
        public string $type,
        public string $id,
    ) {
    }

    public function getDisplayName(): string
    {
        return $this->displayName ?? $this->email;
    }

    public function setNotificationParameters(string $toEmail, string $toEmailName): void
    {
        $this->email = $toEmail;
        $this->displayName = $toEmailName;
    }

    public function getEmail(): string
    {
        if (null === $this->email) {
            throw new \RuntimeException('Email not set');
        }

        return $this->email;
    }
}
