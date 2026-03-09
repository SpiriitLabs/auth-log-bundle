<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\Tests\Listener;

use PHPUnit\Framework\TestCase;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;
use Spiriit\Bundle\AuthLogBundle\Listener\AuthenticationLogEvent;

final class AuthenticationLogEventTest extends TestCase
{
    public function testEventExposesUserIdentifierAndInformation(): void
    {
        $userInformation = new UserInformation('127.0.0.1', 'TestAgent', new \DateTimeImmutable(), null);
        $event = new AuthenticationLogEvent('user-42', $userInformation);

        self::assertSame('user-42', $event->userIdentifier());
        self::assertSame($userInformation, $event->userInformation());
    }
}
