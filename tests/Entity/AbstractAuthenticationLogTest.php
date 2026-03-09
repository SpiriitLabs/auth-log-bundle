<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Spiriit\Bundle\AuthLogBundle\Entity\AbstractAuthenticationLog;
use Spiriit\Bundle\AuthLogBundle\Entity\AuthLogUserInterface;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\LocateUserInformation\LocateValues;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;

final class AbstractAuthenticationLogTest extends TestCase
{
    public function testConstructorAndGettersWithoutLocation(): void
    {
        $loginAt = new \DateTimeImmutable('2025-01-15 10:30:00');
        $userInformation = new UserInformation('192.168.1.1', 'Mozilla/5.0', $loginAt, null);

        $log = $this->createConcreteLog($userInformation);

        self::assertSame('192.168.1.1', $log->getIpAddress());
        self::assertSame('Mozilla/5.0', $log->getUserAgent());
        self::assertSame($loginAt, $log->getLoginAt());
        self::assertNull($log->getLocation());
    }

    public function testConstructorAndGettersWithLocation(): void
    {
        $loginAt = new \DateTimeImmutable('2025-06-01 14:00:00');
        $location = new LocateValues(
            country: 'France',
            country_code: 'FR',
            city: 'Paris',
            latitude: 48.8566,
            longitude: 2.3522
        );
        $userInformation = new UserInformation('10.0.0.1', 'Chrome', $loginAt, $location);

        $log = $this->createConcreteLog($userInformation);

        self::assertSame('10.0.0.1', $log->getIpAddress());
        self::assertSame('Chrome', $log->getUserAgent());
        self::assertSame($loginAt, $log->getLoginAt());

        $returnedLocation = $log->getLocation();
        self::assertInstanceOf(LocateValues::class, $returnedLocation);
        self::assertSame('France', $returnedLocation->country);
        self::assertSame('FR', $returnedLocation->country_code);
        self::assertSame('Paris', $returnedLocation->city);
        self::assertSame(48.8566, $returnedLocation->latitude);
        self::assertSame(2.3522, $returnedLocation->longitude);
    }

    private function createConcreteLog(UserInformation $userInformation): AbstractAuthenticationLog
    {
        return new class($userInformation) extends AbstractAuthenticationLog {
            public function getUser(): AuthLogUserInterface
            {
                throw new \RuntimeException('Stub');
            }
        };
    }
}
