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
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\LocateUserInformation\LocateValues;

final class LocateValuesTest extends TestCase
{
    public function testFromArrayCreatesInstance(): void
    {
        $data = [
            'country' => 'France',
            'country_code' => 'FR',
            'city' => 'Paris',
            'latitude' => 48.8566,
            'longitude' => 2.3522,
        ];

        $result = LocateValues::fromArray($data);

        self::assertSame('France', $result->country);
        self::assertSame('FR', $result->country_code);
        self::assertSame('Paris', $result->city);
        self::assertSame(48.8566, $result->latitude);
        self::assertSame(2.3522, $result->longitude);
    }
}
