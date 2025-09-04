<?php

declare(strict_types=1);

/*
 * This file is part of the SpiriitLabs php-excel-rust package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\FetchUserInformation\LocateUserInformation;

use GeoIp2\Database\Reader;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\FetchUserInformationMethodInterface;

class Geoip2LocateMethod implements FetchUserInformationMethodInterface
{
    public function __construct(
        private readonly string $geoip2DatabasePath,
    ) {
    }

    public function locate(string $ipAddress): ?LocateValues
    {
        if (!class_exists(Reader::class)) {
            throw new \RuntimeException('The GeoIP extension is not installed or enabled.');
        }

        $reader = new Reader($this->geoip2DatabasePath);

        try {
            $record = $reader->city($ipAddress);

            return new LocateValues(
                country: $record->country->name,
                country_code: $record->country->isoCode,
                city: $record->city->name,
                latitude: $record->location->latitude,
                longitude: $record->location->longitude
            );
        } catch (\Throwable) {
            return null;
        }
    }
}
