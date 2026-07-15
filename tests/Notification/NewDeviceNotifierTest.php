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
use Spiriit\Bundle\AuthLogBundle\Confirmation\ConfirmationLinks;
use Spiriit\Bundle\AuthLogBundle\Confirmation\ConfirmationToken;
use Spiriit\Bundle\AuthLogBundle\Confirmation\ConfirmationUrlGenerator;
use Spiriit\Bundle\AuthLogBundle\DTO\UserReference;
use Spiriit\Bundle\AuthLogBundle\Entity\AbstractAuthenticationLog;
use Spiriit\Bundle\AuthLogBundle\Entity\AuthLogUserInterface;
use Spiriit\Bundle\AuthLogBundle\Entity\ConfirmableAuthenticationLogInterface;
use Spiriit\Bundle\AuthLogBundle\Entity\ConfirmableAuthenticationLogTrait;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;
use Spiriit\Bundle\AuthLogBundle\Notification\NewDeviceNotifier;
use Spiriit\Bundle\AuthLogBundle\Notification\NotificationInterface;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class NewDeviceNotifierTest extends TestCase
{
    public function testItShouldSendWithoutLinksWhenConfirmationIsDisabled(): void
    {
        $userInformation = $this->userInformation();
        $userReference = $this->userReference();

        $notification = $this->createMock(NotificationInterface::class);
        $notification->expects(self::once())
            ->method('send')
            ->with($userInformation, $userReference, null);

        $newDeviceNotifier = new NewDeviceNotifier($notification);

        $newDeviceNotifier->notify($userInformation, $userReference, $this->createStub(AbstractAuthenticationLog::class));
    }

    public function testItShouldSendConfirmationLinksForAConfirmableLog(): void
    {
        $userInformation = $this->userInformation();
        $userReference = $this->userReference();

        $notification = $this->createMock(NotificationInterface::class);
        $notification->expects(self::once())
            ->method('send')
            ->with($userInformation, $userReference, self::isInstanceOf(ConfirmationLinks::class));

        $log = $this->confirmableLog();
        $log->enableConfirmation(new ConfirmationToken('the-token'));

        $newDeviceNotifier = new NewDeviceNotifier($notification, $this->confirmationUrlGenerator());

        $newDeviceNotifier->notify($userInformation, $userReference, $log);
    }

    public function testItShouldSendWithoutLinksWhenLogIsNotConfirmable(): void
    {
        $userInformation = $this->userInformation();
        $userReference = $this->userReference();

        $notification = $this->createMock(NotificationInterface::class);
        $notification->expects(self::once())
            ->method('send')
            ->with($userInformation, $userReference, null);

        $newDeviceNotifier = new NewDeviceNotifier($notification, $this->confirmationUrlGenerator());

        $newDeviceNotifier->notify($userInformation, $userReference, $this->createStub(AbstractAuthenticationLog::class));
    }

    private function confirmationUrlGenerator(): ConfirmationUrlGenerator
    {
        $urlGenerator = $this->createStub(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('https://app.test/auth-log/confirm/acknowledge/the-token?expires=1');

        return new ConfirmationUrlGenerator($urlGenerator, new UriSigner('a-secret'), 'spiriit_auth_log_confirm', '3 days');
    }

    private function userInformation(): UserInformation
    {
        return new UserInformation('127.0.0.1', 'PHPUnit', new \DateTimeImmutable(), null);
    }

    private function userReference(): UserReference
    {
        return new UserReference('1', 'user@test.com', 'Test User');
    }

    private function confirmableLog(): ConfirmableAuthenticationLogInterface&AbstractAuthenticationLog
    {
        return new class($this->userInformation()) extends AbstractAuthenticationLog implements ConfirmableAuthenticationLogInterface {
            use ConfirmableAuthenticationLogTrait;

            public function getUser(): AuthLogUserInterface
            {
                throw new \RuntimeException('Stub');
            }
        };
    }
}
