<?php

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\Notification;

use Spiriit\Bundle\AuthLogBundle\Confirmation\ConfirmationLinks;
use Spiriit\Bundle\AuthLogBundle\DTO\UserReference;
use Spiriit\Bundle\AuthLogBundle\Entity\AuthenticationLogInterface;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;

final readonly class NewDeviceNotification
{
    public function __construct(
        public UserReference $userReference,
        public UserInformation $userInformation,
        public AuthenticationLogInterface $authenticationLog,
        public ?ConfirmationLinks $confirmationLinks = null,
    ) {
    }
}
