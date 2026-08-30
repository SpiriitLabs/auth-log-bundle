<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\Tests\Messenger;

use PHPUnit\Framework\TestCase;
use Spiriit\Bundle\AuthLogBundle\DTO\LoginParameterDto;
use Spiriit\Bundle\AuthLogBundle\DTO\UserIdentity;
use Spiriit\Bundle\AuthLogBundle\Messenger\AuthLoginMessage\AuthLoginMessage;
use Spiriit\Bundle\Tests\Stubs\StubUser;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;

final class AuthLoginMessageSerializationTest extends TestCase
{
    public function testItShouldSurviveAPhpSerializerRoundTrip(): void
    {
        $dto = new LoginParameterDto(
            userIdentity: new UserIdentity('user-1', StubUser::class),
            toEmail: 'email@test.fr',
            toEmailName: 'test',
            clientIp: '127.0.0.1',
            userAgent: 'agent',
        );

        $serializer = new PhpSerializer();
        $envelope = $serializer->decode($serializer->encode(new Envelope(new AuthLoginMessage($dto))));

        $message = $envelope->getMessage();
        self::assertInstanceOf(AuthLoginMessage::class, $message);
        self::assertEquals($dto, $message->loginParameterDto);
        self::assertSame('user-1', $message->loginParameterDto->userIdentity->userIdentifier);
        self::assertSame(StubUser::class, $message->loginParameterDto->userIdentity->userClass);
    }
}
