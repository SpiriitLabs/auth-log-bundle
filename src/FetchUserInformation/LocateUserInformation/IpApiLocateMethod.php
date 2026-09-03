<?php

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\FetchUserInformation\LocateUserInformation;

use Psr\Log\LoggerInterface;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\FetchUserInformationMethodInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Free usage limited to 45 requests per minute from an IP address.
 */
class IpApiLocateMethod implements FetchUserInformationMethodInterface
{
    public const SUCCESS = 'success';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function locate(string $ipAddress): ?LocateValues
    {
        try {
            $response = $this->httpClient->request('GET', 'http://ip-api.com/json/'.$ipAddress);

            if (200 !== $response->getStatusCode()) {
                return null;
            }

            $data = $response->toArray();

            if (self::SUCCESS !== ($data['status'] ?? null)) {
                return null;
            }

            return new LocateValues(
                country: $data['country'],
                country_code: $data['countryCode'],
                city: $data['city'],
                latitude: $data['lat'],
                longitude: $data['lon']
            );
        } catch (\Throwable $exception) {
            $this->logger?->warning('IP geolocation failed: {message}', [
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            return null;
        }
    }
}
