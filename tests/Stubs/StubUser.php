<?php

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\Tests\Stubs;

use Spiriit\Bundle\AuthLogBundle\Entity\AuthLogUserInterface;

class StubUser implements AuthLogUserInterface
{
    public function __construct(
        private readonly string $identifier = 'user@test.com',
        private readonly string $displayName = 'Test User',
    ) {
    }

    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }

    public function getAuthLogEmail(): string
    {
        return $this->identifier;
    }

    public function getAuthLogDisplayName(): string
    {
        return $this->displayName;
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function eraseCredentials(): void
    {
    }
}
