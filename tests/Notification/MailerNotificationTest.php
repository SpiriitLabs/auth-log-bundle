<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\Tests\Notification;

use PHPUnit\Framework\TestCase;
use Spiriit\Bundle\AuthLogBundle\DTO\UserReference;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\LocateUserInformation\LocateValues;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;
use Spiriit\Bundle\AuthLogBundle\Notification\MailerNotification;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class MailerNotificationTest extends TestCase
{
    public function testItShouldSendEmailNotification(): void
    {
        // Arrange
        $mailer = $this->createMock(MailerInterface::class);

        $notification = new MailerNotification(
            mailer: $mailer,
            translator: $this->createStub(TranslatorInterface::class),
            addresses: [
                'fromEmail' => 'test@email.fr',
                'fromName' => 'Test',
            ],
        );

        $userReference = new UserReference(
            userIdentifier: '1',
            email: 'email@test.com',
            displayName: 'Jon Smith',
        );

        // Act
        $mailer->expects(self::once())->method('send');

        $notification->send(
            userInformation: new UserInformation(
                ipAddress: '127.23.6',
                userAgent: 'Mozilla',
                loginAt: new \DateTimeImmutable('2025-09-11'),
                location: new LocateValues(country: 'France', country_code: 'FR', city: 'Paris', latitude: 48.8566, longitude: 2.3522),
            ),
            userReference: $userReference,
        );
    }
}
