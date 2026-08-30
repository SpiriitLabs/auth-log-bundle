<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\Tests\DTO;

use PHPUnit\Framework\TestCase;
use Spiriit\Bundle\AuthLogBundle\DTO\UserIdentity;
use Spiriit\Bundle\Tests\Stubs\StubAdminUser;
use Spiriit\Bundle\Tests\Stubs\StubUser;
use Spiriit\Bundle\Tests\Stubs\StubUserProxy;

final class UserIdentityTest extends TestCase
{
    public function testItShouldBuildIdentityFromUser(): void
    {
        $userIdentity = UserIdentity::fromUser(new StubUser('admin@test.com'));

        self::assertSame('admin@test.com', $userIdentity->userIdentifier);
        self::assertSame(StubUser::class, $userIdentity->userClass);
    }

    public function testItShouldDistinguishTwoUserClassesSharingTheSameIdentifier(): void
    {
        $customer = UserIdentity::fromUser(new StubUser('shared@test.com'));
        $admin = UserIdentity::fromUser(new StubAdminUser('shared@test.com'));

        self::assertSame($customer->userIdentifier, $admin->userIdentifier);
        self::assertNotEquals($customer, $admin);
        self::assertSame(StubUser::class, $customer->userClass);
        self::assertSame(StubAdminUser::class, $admin->userClass);
    }

    public function testItShouldResolveTheRealClassBehindADoctrineProxy(): void
    {
        $userIdentity = UserIdentity::fromUser(new StubUserProxy('admin@test.com'));

        self::assertSame('admin@test.com', $userIdentity->userIdentifier);
        self::assertSame(StubUser::class, $userIdentity->userClass);
    }
}
