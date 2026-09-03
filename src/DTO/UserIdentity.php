<?php

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\DTO;

use Doctrine\Persistence\Proxy;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class UserIdentity
{
    public function __construct(
        public string $userIdentifier,
        public string $userClass,
    ) {
    }

    public static function fromUser(UserInterface $user): self
    {
        $userClass = $user::class;

        // A generated Doctrine proxy exposes its own FQCN, never the entity one.
        if ($user instanceof Proxy) {
            $userClass = get_parent_class($user) ?: $userClass;
        }

        return new self($user->getUserIdentifier(), $userClass);
    }
}
