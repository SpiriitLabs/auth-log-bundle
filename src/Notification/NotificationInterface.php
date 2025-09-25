<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\Notification;

use Spiriit\Bundle\AuthLogBundle\DTO\UserReference;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;

interface NotificationInterface
{
    public function send(UserInformation $userInformation, UserReference $userReference): void;
}
