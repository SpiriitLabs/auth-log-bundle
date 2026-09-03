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
use Psr\Log\LoggerInterface;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\LocateUserInformation\IpApiLocateMethod;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\LocateUserInformation\LocateValues;
use Symfony\Component\HttpClient\Exception\JsonException;
use Symfony\Component\HttpClient\Exception\TransportException;
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

    public function testLocateReturnsNullAndLogsWarningWhenTransportFails(): void
    {
        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('request')->willThrowException(new TransportException('Could not resolve host: ip-api.com'));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('warning')
            ->with(
                'IP geolocation failed: {message}',
                self::callback(static fn (array $context): bool => $context['exception'] instanceof TransportException)
            );

        $method = new IpApiLocateMethod($httpClient, $logger);

        self::assertNull($method->locate('8.8.8.8'));
    }

    public function testLocateReturnsNullAndLogsWarningWhenPayloadIsIncomplete(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('toArray')->willReturn([
            'status' => 'success',
            'country' => 'France',
        ]);

        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($response);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning');

        $method = new IpApiLocateMethod($httpClient, $logger);

        self::assertNull($method->locate('8.8.8.8'));
    }

    public function testLocateStaysSilentWhenServiceRateLimits(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(429);

        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($response);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('warning');

        $method = new IpApiLocateMethod($httpClient, $logger);

        self::assertNull($method->locate('8.8.8.8'));
    }

    public function testLocateReturnsNullWhenStatusCodeCannotBeRead(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getStatusCode')->willThrowException(new TransportException('Idle timeout reached'));

        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($response);

        $method = new IpApiLocateMethod($httpClient);

        self::assertNull($method->locate('8.8.8.8'));
    }

    public function testLocateReturnsNullWhenBodyCannotBeDecoded(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('toArray')->willThrowException(new JsonException('Syntax error'));

        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($response);

        $method = new IpApiLocateMethod($httpClient);

        self::assertNull($method->locate('8.8.8.8'));
    }

    public function testLocateReturnsNullWhenStatusKeyIsMissing(): void
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('toArray')->willReturn([]);

        $httpClient = $this->createStub(HttpClientInterface::class);
        $httpClient->method('request')->willReturn($response);

        $method = new IpApiLocateMethod($httpClient);

        self::assertNull($method->locate('8.8.8.8'));
    }
}
