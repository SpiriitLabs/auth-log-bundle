<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\Tests\Listener;

use PHPUnit\Framework\TestCase;
use Spiriit\Bundle\AuthLogBundle\AuthenticationLog\AuthenticationLogHandlerInterface;
use Spiriit\Bundle\AuthLogBundle\DTO\UserIdentity;
use Spiriit\Bundle\AuthLogBundle\Entity\AbstractAuthenticationLog;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\FetchUserInformation;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;
use Spiriit\Bundle\AuthLogBundle\Listener\LoginListener;
use Spiriit\Bundle\AuthLogBundle\Messenger\AuthLoginMessage\AuthLoginMessage;
use Spiriit\Bundle\AuthLogBundle\Notification\NewDeviceNotifier;
use Spiriit\Bundle\AuthLogBundle\Notification\NotificationInterface;
use Spiriit\Bundle\AuthLogBundle\Services\LoginService;
use Spiriit\Bundle\Tests\Stubs\StubUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class LoginListenerTest extends TestCase
{
    private function createLoginSuccessEvent(UserInterface $user, Request $request): LoginSuccessEvent
    {
        $passport = new SelfValidatingPassport(new UserBadge($user->getUserIdentifier(), fn () => $user));
        $token = new PostAuthenticationToken($user, 'main', []);

        return new LoginSuccessEvent(
            $this->createStub(AuthenticatorInterface::class),
            $passport,
            $token,
            $request,
            null,
            'main',
        );
    }

    public function testItShouldCallLoginServiceWhenUserImplementsAuthLogUserInterface(): void
    {
        $user = new StubUser('user@test.com');

        $handler = $this->createMock(AuthenticationLogHandlerInterface::class);
        $handler->expects(self::once())
            ->method('isKnown')
            ->with(
                self::callback(static fn (UserIdentity $userIdentity): bool => 'user@test.com' === $userIdentity->userIdentifier
                    && StubUser::class === $userIdentity->userClass),
                self::anything(),
            )
            ->willReturn(false);
        $handler->expects(self::once())->method('handle')->willReturn($this->createStub(AbstractAuthenticationLog::class));

        $notifier = $this->createMock(NotificationInterface::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $fetchUserInformation = $this->createStub(FetchUserInformation::class);
        $fetchUserInformation->method('fetch')->willReturn(
            new UserInformation('127.0.0.1', 'Test Agent', new \DateTimeImmutable(), null)
        );

        $loginService = new LoginService($fetchUserInformation, $handler, new NewDeviceNotifier($notifier), $dispatcher);
        $listener = new LoginListener($loginService);

        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '127.0.0.1']);
        $request->headers->set('User-Agent', 'Test Agent');

        $listener->onLogin($this->createLoginSuccessEvent($user, $request));
    }

    public function testItShouldNotCallLoginServiceWhenUserDoesNotImplementAuthLogUserInterface(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('other@test.com');

        $handler = $this->createMock(AuthenticationLogHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        $notifier = $this->createMock(NotificationInterface::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $fetchUserInformation = $this->createStub(FetchUserInformation::class);

        $loginService = new LoginService($fetchUserInformation, $handler, new NewDeviceNotifier($notifier), $dispatcher);
        $listener = new LoginListener($loginService);

        $request = new Request();

        $listener->onLogin($this->createLoginSuccessEvent($user, $request));
    }

    public function testItShouldDispatchMessageWhenMessengerIsConfigured(): void
    {
        $user = new StubUser('user@test.com');

        $handler = $this->createMock(AuthenticationLogHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        $notifier = $this->createMock(NotificationInterface::class);
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $fetchUserInformation = $this->createStub(FetchUserInformation::class);

        $loginService = new LoginService($fetchUserInformation, $handler, new NewDeviceNotifier($notifier), $dispatcher);

        $messageBus = $this->createMock(MessageBusInterface::class);
        $messageBus->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(static function (AuthLoginMessage $message): bool {
                $userIdentity = $message->loginParameterDto->userIdentity;

                return 'user@test.com' === $userIdentity->userIdentifier && StubUser::class === $userIdentity->userClass;
            }))
            ->willReturn(new Envelope(new \stdClass()));

        $listener = new LoginListener($loginService, $messageBus);

        $request = new Request([], [], [], [], [], ['REMOTE_ADDR' => '192.168.1.1']);
        $request->headers->set('User-Agent', 'Mozilla/5.0');

        $listener->onLogin($this->createLoginSuccessEvent($user, $request));
    }
}
