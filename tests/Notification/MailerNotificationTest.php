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
use Spiriit\Bundle\AuthLogBundle\DTO\UserIdentity;
use Spiriit\Bundle\AuthLogBundle\DTO\UserReference;
use Spiriit\Bundle\AuthLogBundle\Entity\AbstractAuthenticationLog;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\LocateUserInformation\LocateValues;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;
use Spiriit\Bundle\AuthLogBundle\Notification\MailerNotification;
use Spiriit\Bundle\AuthLogBundle\Notification\NewDeviceNotification;
use Spiriit\Bundle\Tests\Stubs\StubUser;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
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
            userIdentity: new UserIdentity('1', StubUser::class),
            email: 'email@test.com',
            displayName: 'Jon Smith',
        );

        $userInformation = new UserInformation(
            ipAddress: '127.23.6',
            userAgent: 'Mozilla',
            loginAt: new \DateTimeImmutable('2025-09-11'),
            location: new LocateValues(country: 'France', country_code: 'FR', city: 'Paris', latitude: 48.8566, longitude: 2.3522),
        );
        $authenticationLog = $this->createStub(AbstractAuthenticationLog::class);

        // Act
        $mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(static function (TemplatedEmail $email) use ($userReference, $userInformation, $authenticationLog): bool {
                $context = $email->getContext();

                return $context['userReference'] === $userReference
                    && $context['authenticableLog'] === $userReference
                    && $context['userInformation'] === $userInformation
                    && $context['authenticationLog'] === $authenticationLog
                    && null === $context['confirmationLinks'];
            }));

        $notification->send(new NewDeviceNotification($userReference, $userInformation, $authenticationLog));
    }
}
