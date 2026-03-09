<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\Tests\FetchUserInformation;

use PHPUnit\Framework\TestCase;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\FetchUserInformation;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\FetchUserInformationMethodInterface;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\LocateUserInformation\LocateValues;

final class FetchUserInformationTest extends TestCase
{
    public function testFetchWithoutLocateMethodReturnsNullLocation(): void
    {
        $service = new FetchUserInformation();

        $result = $service->fetch('127.0.0.1', 'TestAgent');

        self::assertSame('127.0.0.1', $result->ipAddress);
        self::assertSame('TestAgent', $result->userAgent);
        self::assertNull($result->location);
        self::assertInstanceOf(\DateTimeImmutable::class, $result->loginAt);
    }

    public function testFetchWithLocateMethodReturnsLocation(): void
    {
        $locateValues = new LocateValues(
            country: 'France',
            country_code: 'FR',
            city: 'Paris',
            latitude: 48.8566,
            longitude: 2.3522
        );

        $locateMethod = $this->createMock(FetchUserInformationMethodInterface::class);
        $locateMethod->expects(self::once())
            ->method('locate')
            ->with('10.0.0.1')
            ->willReturn($locateValues);

        $service = new FetchUserInformation();
        $service->setLocateMethod($locateMethod);

        $result = $service->fetch('10.0.0.1', 'Mozilla/5.0');

        self::assertSame('10.0.0.1', $result->ipAddress);
        self::assertSame('Mozilla/5.0', $result->userAgent);
        self::assertSame($locateValues, $result->location);
    }
}
