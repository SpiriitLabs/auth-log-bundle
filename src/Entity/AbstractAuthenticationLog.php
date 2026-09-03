<?php

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Spiriit\Bundle\AuthLogBundle\DTO\UserIdentity;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\LocateUserInformation\LocateValues;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;

#[ORM\MappedSuperclass]
abstract class AbstractAuthenticationLog implements AuthenticationLogInterface
{
    #[ORM\Column(type: Types::STRING, length: 255)]
    protected string $userIdentifier;

    #[ORM\Column(type: Types::STRING, length: 255)]
    protected string $userClass;

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
        UserIdentity $userIdentity,
        UserInformation $userInformation,
    ) {
        $this->userIdentifier = $userIdentity->userIdentifier;
        $this->userClass = $userIdentity->userClass;
        $this->loginAt = $userInformation->loginAt;
        $this->userAgent = $userInformation->userAgent;
        $this->ipAddress = $userInformation->ipAddress;

        if (null !== $userInformation->location) {
            $this->location = get_object_vars($userInformation->location);
        }
    }

    public function userIdentity(): UserIdentity
    {
        return new UserIdentity($this->userIdentifier, $this->userClass);
    }

    public function getUserClass(): string
    {
        return $this->userClass;
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
        if (empty($this->location)) {
            return null;
        }

        return LocateValues::fromArray($this->location);
    }

    abstract public function getUser(): AuthLogUserInterface;
}
