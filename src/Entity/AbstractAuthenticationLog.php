<?php

declare(strict_types=1);

/*
 * This file is part of the SpiriitLabs php-excel-rust package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\LocateUserInformation\LocateValues;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;

#[ORM\MappedSuperclass]
abstract class AbstractAuthenticationLog
{
    #[ORM\Column(type: Types::STRING, length: 45, nullable: true)]
    protected ?string $ipAddress;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $userAgent;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    protected ?\DateTimeImmutable $loginAt;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON)]
    protected array $location = [];

    public function __construct(
        UserInformation $userInformation,
    ) {
        $this->loginAt = $userInformation->loginAt;
        $this->userAgent = $userInformation->userAgent;
        $this->ipAddress = $userInformation->ipAddress;

        if (null !== $userInformation->location) {
            $this->location = get_object_vars($userInformation->location);
        }
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function getLoginAt(): ?\DateTimeImmutable
    {
        return $this->loginAt;
    }

    public function getLocation(): ?LocateValues
    {
        if (!empty($this->location)) {
            return null;
        }

        return LocateValues::fromArray($this->location);
    }

    abstract public function getUser(): AuthenticableLogInterface;
}
