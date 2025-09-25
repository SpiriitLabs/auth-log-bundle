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
use Spiriit\Bundle\AuthLogBundle\Entity\AuthenticableLogInterface;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\LocateUserInformation\LocateValues;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;
use Spiriit\Bundle\AuthLogBundle\Notification\MailerNotification;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class MailerNotificationTest extends TestCase
{
    public function testSend(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())->method('send');

        $notification = new MailerNotification(
            mailer: $mailer,
            translator: $this->createMock(TranslatorInterface::class),
            addresses: [
                'fromEmail' => 'test@email.fr',
                'fromName' => 'Test',
            ],
        );

        new class implements AuthenticableLogInterface {
            public function getAuthenticationLogFactoryName(): string
            {
                return 'user';
            }

            public function getAuthenticationLogsToEmail(): string
            {
                return 'my_email@test.fr';
            }

            public function getAuthenticationLogsToEmailName(): string
            {
                return 'Jon Smith';
            }
        };

        $userReference = new UserReference(
            type: 'user',
            id: '1',
        );
        $userReference->setNotificationParameters(
            toEmail: 'email@test.com',
            toEmailName: 'Jon Smith'
        );

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
