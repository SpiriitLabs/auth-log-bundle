<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\FetchUserInformation\LocateUserInformation;

readonly class LocateValues
{
    public function __construct(
        public string $country,
        public string $country_code,
        public string $city,
        public float $latitude,
        public float $longitude,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            country: $data['country'],
            country_code: $data['country_code'],
            city: $data['city'],
            latitude: $data['latitude'],
            longitude: $data['longitude']
        );
    }
}
