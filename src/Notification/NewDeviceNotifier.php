<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\Notification;

use Spiriit\Bundle\AuthLogBundle\Confirmation\ConfirmationLinks;
use Spiriit\Bundle\AuthLogBundle\Confirmation\ConfirmationUrlGenerator;
use Spiriit\Bundle\AuthLogBundle\DTO\UserReference;
use Spiriit\Bundle\AuthLogBundle\Entity\AbstractAuthenticationLog;
use Spiriit\Bundle\AuthLogBundle\Entity\ConfirmableAuthenticationLogInterface;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;

final readonly class NewDeviceNotifier
{
    public function __construct(
        private NotificationInterface $notification,
        private ?ConfirmationUrlGenerator $confirmationUrlGenerator = null,
    ) {
    }

    public function notify(UserInformation $userInformation, UserReference $userReference, AbstractAuthenticationLog $authenticationLog): void
    {
        $this->notification->send($userInformation, $userReference, $this->confirmationLinks($authenticationLog));
    }

    private function confirmationLinks(AbstractAuthenticationLog $authenticationLog): ?ConfirmationLinks
    {
        if (null === $this->confirmationUrlGenerator) {
            return null;
        }

        if (!$authenticationLog instanceof ConfirmableAuthenticationLogInterface || null === $authenticationLog->confirmationToken()) {
            return null;
        }

        return $this->confirmationUrlGenerator->generate($authenticationLog);
    }
}
