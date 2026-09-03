<?php

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\Tests\Listener;

use PHPUnit\Framework\TestCase;
use Spiriit\Bundle\AuthLogBundle\Entity\ConfirmableAuthenticationLogInterface;
use Spiriit\Bundle\AuthLogBundle\Listener\AuthenticationLogConfirmationEvent;

final class AuthenticationLogConfirmationEventTest extends TestCase
{
    public function testEventExposesTheConfirmedLog(): void
    {
        $authenticationLog = $this->createStub(ConfirmableAuthenticationLogInterface::class);
        $event = new AuthenticationLogConfirmationEvent($authenticationLog);

        self::assertSame($authenticationLog, $event->authenticationLog());
    }
}
