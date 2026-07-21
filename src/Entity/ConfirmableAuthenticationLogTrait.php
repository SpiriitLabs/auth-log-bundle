<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Spiriit\Bundle\AuthLogBundle\Confirmation\ConfirmationToken;
use Spiriit\Bundle\AuthLogBundle\Confirmation\Exception\AuthenticationLogAlreadyReviewedException;

trait ConfirmableAuthenticationLogTrait
{
    #[ORM\Column(type: Types::STRING, length: 100, unique: true, nullable: true)]
    protected ?string $confirmationToken = null;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: AuthenticationLogStatus::class, options: ['default' => AuthenticationLogStatus::PENDING->value])]
    protected AuthenticationLogStatus $status = AuthenticationLogStatus::PENDING;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    protected ?\DateTimeImmutable $respondedAt = null;

    public function enableConfirmation(ConfirmationToken $confirmationToken): void
    {
        if (!$this->isPending()) {
            throw new AuthenticationLogAlreadyReviewedException(\sprintf('Cannot enable confirmation on an authentication log that has already been reviewed as "%s".', $this->status->value));
        }

        $this->confirmationToken = $confirmationToken->toString();
    }

    public function acknowledge(): void
    {
        $this->review(AuthenticationLogStatus::ACKNOWLEDGED);
    }

    public function disavow(): void
    {
        $this->review(AuthenticationLogStatus::DISAVOWED);
    }

    public function confirmationToken(): ?string
    {
        return $this->confirmationToken;
    }

    public function status(): AuthenticationLogStatus
    {
        return $this->status;
    }

    public function respondedAt(): ?\DateTimeImmutable
    {
        return $this->respondedAt;
    }

    public function isPending(): bool
    {
        return AuthenticationLogStatus::PENDING === $this->status;
    }

    private function review(AuthenticationLogStatus $status): void
    {
        if (!$this->isPending()) {
            throw new AuthenticationLogAlreadyReviewedException(\sprintf('Cannot review an authentication log that has already been reviewed as "%s".', $this->status->value));
        }

        $this->status = $status;
        $this->respondedAt = new \DateTimeImmutable();
    }
}
