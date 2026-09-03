<?php

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\Tests\Listener;

use PHPUnit\Framework\TestCase;
use Spiriit\Bundle\AuthLogBundle\DTO\UserIdentity;
use Spiriit\Bundle\AuthLogBundle\Entity\AbstractAuthenticationLog;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;
use Spiriit\Bundle\AuthLogBundle\Listener\AuthenticationLogEvent;
use Spiriit\Bundle\Tests\Stubs\StubUser;

final class AuthenticationLogEventTest extends TestCase
{
    public function testEventExposesUserIdentityInformationAndLog(): void
    {
        $userInformation = new UserInformation('127.0.0.1', 'TestAgent', new \DateTimeImmutable(), null);
        $userIdentity = new UserIdentity('user-42', StubUser::class);
        $authenticationLog = $this->createStub(AbstractAuthenticationLog::class);

        $event = new AuthenticationLogEvent($userIdentity, $userInformation, $authenticationLog);

        self::assertSame($userIdentity, $event->userIdentity());
        self::assertSame('user-42', $event->userIdentifier());
        self::assertSame(StubUser::class, $event->userIdentity()->userClass);
        self::assertSame($userInformation, $event->userInformation());
        self::assertSame($authenticationLog, $event->authenticationLog());
    }
}
