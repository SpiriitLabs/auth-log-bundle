<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\AuthenticationLog;

use Spiriit\Bundle\AuthLogBundle\Confirmation\ConfirmationTokenGenerator;
use Spiriit\Bundle\AuthLogBundle\Entity\AuthenticationLogInterface;
use Spiriit\Bundle\AuthLogBundle\Entity\ConfirmableAuthenticationLogInterface;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;
use Spiriit\Bundle\AuthLogBundle\Repository\AuthenticationLogRepositoryInterface;

final readonly class DoctrineAuthenticationLogHandler implements AuthenticationLogHandlerInterface
{
    public function __construct(
        private AuthenticationLogRepositoryInterface $repository,
        private AuthenticationLogCreatorInterface $creator,
        private ?ConfirmationTokenGenerator $confirmationTokenGenerator = null,
    ) {
    }

    public function isKnown(string $userIdentifier, UserInformation $userInformation): bool
    {
        return $this->repository->findExistingLog($userIdentifier, $userInformation);
    }

    public function handle(string $userIdentifier, UserInformation $userInformation): AuthenticationLogInterface
    {
        $log = $this->creator->createLog($userIdentifier, $userInformation);

        if (null !== $this->confirmationTokenGenerator && $log instanceof ConfirmableAuthenticationLogInterface) {
            $log->enableConfirmation($this->confirmationTokenGenerator->generate());
        }

        $this->repository->save($log);

        return $log;
    }
}
