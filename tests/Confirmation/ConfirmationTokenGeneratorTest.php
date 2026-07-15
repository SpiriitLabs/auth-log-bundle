<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\Tests\Confirmation;

use PHPUnit\Framework\TestCase;
use Spiriit\Bundle\AuthLogBundle\Confirmation\ConfirmationToken;
use Spiriit\Bundle\AuthLogBundle\Confirmation\ConfirmationTokenGenerator;

final class ConfirmationTokenGeneratorTest extends TestCase
{
    public function testItShouldGenerateAToken(): void
    {
        $confirmationTokenGenerator = new ConfirmationTokenGenerator();

        $token = $confirmationTokenGenerator->generate();

        self::assertInstanceOf(ConfirmationToken::class, $token);
        self::assertSame(32, \strlen($token->toString()));
    }

    public function testItShouldGenerateUniqueTokens(): void
    {
        $confirmationTokenGenerator = new ConfirmationTokenGenerator();

        self::assertNotSame(
            $confirmationTokenGenerator->generate()->toString(),
            $confirmationTokenGenerator->generate()->toString(),
        );
    }
}
