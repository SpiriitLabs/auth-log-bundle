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
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\LocateUserInformation\Geoip2LocateMethod;

final class Geoip2LocateMethodTest extends TestCase
{
    public function testLocateThrowsWhenGeoipNotInstalled(): void
    {
        $method = new Geoip2LocateMethod('/dummy/path.mmdb');

        self::expectException(\RuntimeException::class);
        self::expectExceptionMessage('The GeoIP extension is not installed or enabled.');

        $method->locate('8.8.8.8');
    }
}
