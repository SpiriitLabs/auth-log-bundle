<?php

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\Confirmation;

use Symfony\Component\String\ByteString;

final class ConfirmationTokenGenerator
{
    public function generate(): ConfirmationToken
    {
        return new ConfirmationToken(ByteString::fromRandom(32)->toString());
    }
}
