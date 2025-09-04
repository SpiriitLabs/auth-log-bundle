<?php

declare(strict_types=1);

/*
 * This file is part of the SpiriitLabs php-excel-rust package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\FetchUserInformation\LocateUserInformation;

use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\FetchUserInformationMethodInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Free usage limited to 45 requests per minute from an IP address.
 */
class IpApiLocateMethod implements FetchUserInformationMethodInterface
{
    public function __construct(private readonly HttpClientInterface $httpClient)
    {
    }

    public function locate(string $ipAddress): ?LocateValues
    {
        $response = $this->httpClient->request('GET', 'http://ip-api.com/json/'.$ipAddress);

        if (200 !== $response->getStatusCode()) {
            return null;
        }

        $data = $response->toArray();

        return new LocateValues(
            country: $data['country'],
            country_code: $data['countryCode'],
            city: $data['city'],
            latitude: $data['lat'],
            longitude: $data['lon']
        );
    }
}
