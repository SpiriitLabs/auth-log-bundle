<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\Tests\Stubs;

use Spiriit\Bundle\AuthLogBundle\Entity\AuthLogUserInterface;

final class StubAdminUser implements AuthLogUserInterface
{
    public function __construct(
        private readonly string $identifier = 'user@test.com',
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
        return 'Test Admin';
    }

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        return ['ROLE_ADMIN'];
    }

    public function eraseCredentials(): void
    {
    }
}
