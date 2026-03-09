<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\Tests\Integration;

use Spiriit\Bundle\AuthLogBundle\AuthenticationLog\AuthenticationLogCreatorInterface;
use Spiriit\Bundle\AuthLogBundle\AuthenticationLog\DoctrineAuthenticationLogHandler;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\FetchUserInformation;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\LocateUserInformation\Geoip2LocateMethod;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\LocateUserInformation\IpApiLocateMethod;
use Spiriit\Bundle\AuthLogBundle\Listener\LoginListener;
use Spiriit\Bundle\AuthLogBundle\Messenger\AuthLoginMessage\AuthLoginMessageHandler;
use Spiriit\Bundle\AuthLogBundle\Notification\MailerNotification;
use Spiriit\Bundle\AuthLogBundle\Notification\NotificationInterface;
use Spiriit\Bundle\AuthLogBundle\Repository\AuthenticationLogRepositoryInterface;
use Spiriit\Bundle\AuthLogBundle\Services\LoginService;
use Spiriit\Bundle\Tests\Integration\Stubs\Kernel as KernelStub;
use Spiriit\Bundle\Tests\Integration\Stubs\StubAuthenticationLogCreator;
use Spiriit\Bundle\Tests\Integration\Stubs\StubAuthenticationLogRepository;
use Spiriit\Bundle\Tests\Integration\Stubs\StubNotification;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

class KernelTest extends KernelTestCase
{
    protected function setUp(): void
    {
        $fs = new Filesystem();
        $fs->remove(sys_get_temp_dir().'/SpiriitAuthLogBundle/');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        static::ensureKernelShutdown();
    }

    /**
     * @param array<string, mixed> $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return new KernelStub('test', true, $options['config'] ?? 'base');
    }

    public function testLoadedMinimalConfig(): void
    {
        self::bootKernel(['config' => 'minimal']);

        $config = self::getContainer()->getParameter('spiriit_auth_log.config');

        self::assertArrayHasKey('transports', $config);
        self::assertArrayHasKey('sender_email', $config['transports']);
        self::assertArrayHasKey('sender_name', $config['transports']);
        self::assertInstanceOf(MailerInterface::class, self::getContainer()->get('spiriit_auth_log.transports.mailer'));
        self::assertInstanceOf(FetchUserInformation::class, self::getContainer()->get('spiriit_auth_log.fetch_user_information'));
        self::assertInstanceOf(MailerNotification::class, self::getContainer()->get('spiriit_auth_log.notification'));
        self::assertFalse(self::getContainer()->has('spiriit_auth_log.fetch_user_information_method'));
    }

    public function testLoadedLocationIpApiConfig(): void
    {
        self::bootKernel(['config' => 'location_ipapi']);

        $config = self::getContainer()->getParameter('spiriit_auth_log.config');

        self::assertArrayHasKey('location', $config);
        self::assertArrayHasKey('provider', $config['location']);
        self::assertInstanceOf(IpApiLocateMethod::class, self::getContainer()->get('spiriit_auth_log.fetch_user_information_method'));
    }

    public function testLoadedLocationGeoIpConfig(): void
    {
        self::bootKernel(['config' => 'location_geoip2']);

        $config = self::getContainer()->getParameter('spiriit_auth_log.config');

        self::assertArrayHasKey('location', $config);
        self::assertArrayHasKey('provider', $config['location']);
        self::assertInstanceOf(Geoip2LocateMethod::class, self::getContainer()->get('spiriit_auth_log.fetch_user_information_method'));
    }

    public function testLoadedMessengerConfig(): void
    {
        self::bootKernel(['config' => 'messenger']);

        $config = self::getContainer()->getParameter('spiriit_auth_log.config');

        self::assertArrayHasKey('messenger', $config);
        self::assertTrue(self::getContainer()->has('spiriit_auth_log.login_message_handler'));
    }

    public function testLoginListenerIsRegisteredAsService(): void
    {
        self::bootKernel(['config' => 'minimal']);

        self::assertTrue(self::getContainer()->has('spiriit_auth_log.login_listener'));
        self::assertInstanceOf(LoginListener::class, self::getContainer()->get('spiriit_auth_log.login_listener'));
    }

    public function testLoginListenerIsRegisteredAsEventListener(): void
    {
        self::bootKernel(['config' => 'minimal']);

        /** @var EventDispatcher $dispatcher */
        $dispatcher = self::getContainer()->get('event_dispatcher');
        $listeners = $dispatcher->getListeners(LoginSuccessEvent::class);

        $found = false;
        foreach ($listeners as $listener) {
            if (\is_array($listener) && $listener[0] instanceof LoginListener) {
                $found = true;
                break;
            }
        }

        self::assertTrue($found, 'LoginListener should be registered as a listener for LoginSuccessEvent');
    }

    public function testLoginServiceIsRegistered(): void
    {
        self::bootKernel(['config' => 'minimal']);

        self::assertTrue(self::getContainer()->has('spiriit_auth_log.login_service'));
        self::assertInstanceOf(LoginService::class, self::getContainer()->get('spiriit_auth_log.login_service'));
    }

    public function testHandlerIsRegistered(): void
    {
        self::bootKernel(['config' => 'minimal']);

        self::assertTrue(self::getContainer()->has('spiriit_auth_log.handler'));
        self::assertInstanceOf(DoctrineAuthenticationLogHandler::class, self::getContainer()->get('spiriit_auth_log.handler'));
    }

    public function testEventDispatcherAliasIsRegistered(): void
    {
        self::bootKernel(['config' => 'minimal']);

        self::assertTrue(self::getContainer()->has('spiriit_auth_log.login_event_dispatcher'));
    }

    public function testRepositoryInterfaceIsResolvable(): void
    {
        self::bootKernel(['config' => 'minimal']);

        self::assertTrue(self::getContainer()->has(AuthenticationLogRepositoryInterface::class));
        self::assertInstanceOf(StubAuthenticationLogRepository::class, self::getContainer()->get(AuthenticationLogRepositoryInterface::class));
    }

    public function testCreatorInterfaceIsResolvable(): void
    {
        self::bootKernel(['config' => 'minimal']);

        self::assertTrue(self::getContainer()->has(AuthenticationLogCreatorInterface::class));
        self::assertInstanceOf(StubAuthenticationLogCreator::class, self::getContainer()->get(AuthenticationLogCreatorInterface::class));
    }

    public function testHandlerReceivesInterfaceImplementations(): void
    {
        self::bootKernel(['config' => 'minimal']);

        $handler = self::getContainer()->get('spiriit_auth_log.handler');

        self::assertInstanceOf(DoctrineAuthenticationLogHandler::class, $handler);
    }

    public function testCustomNotificationServiceIsUsed(): void
    {
        self::bootKernel(['config' => 'custom_notification']);

        $notification = self::getContainer()->get('spiriit_auth_log.notification');

        self::assertInstanceOf(NotificationInterface::class, $notification);
        self::assertInstanceOf(StubNotification::class, $notification);
        self::assertNotInstanceOf(MailerNotification::class, $notification);
    }

    public function testDefaultNotificationIsMailerNotification(): void
    {
        self::bootKernel(['config' => 'minimal']);

        $notification = self::getContainer()->get('spiriit_auth_log.notification');

        self::assertInstanceOf(NotificationInterface::class, $notification);
        self::assertInstanceOf(MailerNotification::class, $notification);
    }

    public function testLoginListenerMethodIsOnLogin(): void
    {
        self::bootKernel(['config' => 'minimal']);

        /** @var EventDispatcher $dispatcher */
        $dispatcher = self::getContainer()->get('event_dispatcher');
        $listeners = $dispatcher->getListeners(LoginSuccessEvent::class);

        $found = false;
        foreach ($listeners as $listener) {
            if (\is_array($listener) && $listener[0] instanceof LoginListener) {
                self::assertSame('onLogin', $listener[1]);
                $found = true;
                break;
            }
        }

        self::assertTrue($found, 'LoginListener::onLogin should be registered for LoginSuccessEvent');
    }

    public function testMessengerHandlerReceivesLoginService(): void
    {
        self::bootKernel(['config' => 'messenger']);

        $handler = self::getContainer()->get('spiriit_auth_log.login_message_handler');

        self::assertInstanceOf(AuthLoginMessageHandler::class, $handler);
    }

    public function testFetchUserInformationHasLocateMethodWhenLocationConfigured(): void
    {
        self::bootKernel(['config' => 'location_ipapi']);

        self::assertTrue(self::getContainer()->has('spiriit_auth_log.fetch_user_information'));
        self::assertTrue(self::getContainer()->has('spiriit_auth_log.fetch_user_information_method'));
        self::assertInstanceOf(FetchUserInformation::class, self::getContainer()->get('spiriit_auth_log.fetch_user_information'));
        self::assertInstanceOf(IpApiLocateMethod::class, self::getContainer()->get('spiriit_auth_log.fetch_user_information_method'));
    }
}
