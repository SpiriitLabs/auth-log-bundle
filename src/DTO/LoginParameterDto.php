<?php

namespace Spiriit\Bundle\AuthLogBundle\DTO;

readonly class LoginParameterDto
{
    public function __construct(
        public string $factoryName,
        public string $userIdentifier,
        public string $toEmail,
        public string $toEmailName,
        public string $clientIp,
        public string $userAgent,
    ) {}
}
