<?php

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\Tests\Services;

use PHPUnit\Framework\TestCase;
use Spiriit\Bundle\AuthLogBundle\AuthenticationLogFactory\AuthenticationLogFactoryInterface;
use Spiriit\Bundle\AuthLogBundle\DTO\LoginParameterDto;
use Spiriit\Bundle\AuthLogBundle\DTO\UserReference;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;
use Spiriit\Bundle\AuthLogBundle\Services\AuthenticationContext;
use Spiriit\Bundle\AuthLogBundle\Services\AuthenticationContextBuilder;
use Spiriit\Bundle\AuthLogBundle\Services\AuthenticationEventPublisher;
use Spiriit\Bundle\AuthLogBundle\Services\LoginService;

class LoginServiceTest extends TestCase
{
    public function testMustPublishWhenAuthLogIsNotKnown(): void
    {
        $parameters = new LoginParameterDto(
            factoryName: 'test-factory',
            userIdentifier: '1',
            toEmail: 'email@test.fr',
            toEmailName: 'test',
            clientIp: '127.0.0.1',
            userAgent: 'agent'
        );

        $authLogFactory = $this->createMock(AuthenticationLogFactoryInterface::class);

        $userReference = new UserReference(
            type: 'user',
            id: '1',
        );

        $userInformation = new UserInformation('127.0.0.1', 'PHPUnit', new \DateTimeImmutable('2025-09-01'), null);

        $authLogFactory
            ->expects(self::once())
            ->method('isKnown')
            ->with($userReference)
            ->willReturn(false);

        $context = new AuthenticationContext(
            authenticationLogFactory: $authLogFactory,
            userReference: $userReference,
            userInformation: $userInformation
        );

        $builder = $this->createMock(AuthenticationContextBuilder::class);
        $builder
            ->method('build')
            ->with($parameters)
            ->willReturn($context);

        $publisher = $this->createMock(AuthenticationEventPublisher::class);
        $publisher
            ->expects(self::once())
            ->method('publish')
            ->with($context);

        $service = new LoginService($builder, $publisher);
        $service->execute($parameters);
    }

    public function testMustNotPublishWhenAuthLogIsKnown(): void
    {
        $parameters = new LoginParameterDto(
            factoryName: 'test-factory',
            userIdentifier: '1',
            toEmail: 'email@test.fr',
            toEmailName: 'test',
            clientIp: '127.0.0.1',
            userAgent: 'agent'
        );

        $authLogFactory = $this->createMock(AuthenticationLogFactoryInterface::class);
        $userReference = new UserReference(
            type: 'user',
            id: '1',
        );

        $userInformation = new UserInformation('127.0.0.1', 'PHPUnit', new \DateTimeImmutable('2025-09-01'), null);

        $authLogFactory
            ->expects(self::once())
            ->method('isKnown')
            ->with($userReference)
            ->willReturn(true);

        $context = new AuthenticationContext(
            authenticationLogFactory: $authLogFactory,
            userReference: $userReference,
            userInformation: $userInformation
        );

        $builder = $this->createMock(AuthenticationContextBuilder::class);
        $builder
            ->method('build')
            ->with($parameters)
            ->willReturn($context);

        $publisher = $this->createMock(AuthenticationEventPublisher::class);
        $publisher
            ->expects(self::never())
            ->method('publish');

        $service = new LoginService($builder, $publisher);
        $service->execute($parameters);
    }
}
