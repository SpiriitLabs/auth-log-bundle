<?php

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\Services;

use Spiriit\Bundle\AuthLogBundle\AuthenticationLogFactory\FetchAuthenticationLogFactory;
use Spiriit\Bundle\AuthLogBundle\DTO\LoginParameterDto;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\FetchUserInformation;

class AuthenticationContextBuilder
{
    public function __construct(
        private readonly FetchAuthenticationLogFactory $factory,
        private readonly FetchUserInformation $fetchUserInformation,
    ) {
    }

    public function build(LoginParameterDto $loginParameterDto): AuthenticationContext
    {
        $factory = $this->factory->createFrom($loginParameterDto->factoryName);

        $userInformation = $this->fetchUserInformation->fetch(
            clientIp: $loginParameterDto->clientIp,
            userAgent: $loginParameterDto->userAgent
        );

        $userReference = $factory->createUserReference($loginParameterDto->userIdentifier);
        $userReference->setNotificationParameters($loginParameterDto->toEmail, $loginParameterDto->toEmailName);

        return new AuthenticationContext($factory, $userReference, $userInformation);
    }
}
