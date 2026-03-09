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
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\LocateUserInformation\IpApiLocateMethod;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\LocateUserInformation\LocateValues;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class IpApiLocateMethodTest extends TestCase
{
    public function testLocateReturnsValuesOnSuccess(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('toArray')->willReturn([
            'status' => 'success',
            'country' => 'France',
            'countryCode' => 'FR',
            'city' => 'Paris',
            'lat' => 48.8566,
            'lon' => 2.3522,
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->expects(self::once())
            ->method('request')
            ->with('GET', 'http://ip-api.com/json/8.8.8.8')
            ->willReturn($response);

        $method = new IpApiLocateMethod($httpClient);
        $result = $method->locate('8.8.8.8');

        self::assertInstanceOf(LocateValues::class, $result);
        self::assertSame('France', $result->country);
        self::assertSame('FR', $result->country_code);
        self::assertSame('Paris', $result->city);
        self::assertSame(48.8566, $result->latitude);
        self::assertSame(2.3522, $result->longitude);
    }

    public function testLocateReturnsNullOnNon200Status(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(429);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($response);

        $method = new IpApiLocateMethod($httpClient);

        self::assertNull($method->locate('8.8.8.8'));
    }

    public function testLocateReturnsNullOnFailedStatus(): void
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('toArray')->willReturn([
            'status' => 'fail',
            'message' => 'reserved range',
        ]);

        $httpClient = $this->createMock(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($response);

        $method = new IpApiLocateMethod($httpClient);

        self::assertNull($method->locate('127.0.0.1'));
    }
}
