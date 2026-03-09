<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\Tests\Integration;

use Spiriit\Bundle\AuthLogBundle\AuthenticationLog\DoctrineAuthenticationLogHandler;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\FetchUserInformation;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\LocateUserInformation\IpApiLocateMethod;
use Spiriit\Bundle\AuthLogBundle\Listener\LoginListener;
use Spiriit\Bundle\AuthLogBundle\Messenger\AuthLoginMessage\AuthLoginMessageHandler;
use Spiriit\Bundle\AuthLogBundle\Notification\MailerNotification;
use Spiriit\Bundle\AuthLogBundle\Notification\NotificationInterface;
use Spiriit\Bundle\AuthLogBundle\Services\LoginService;
use Spiriit\Bundle\Tests\Integration\Stubs\Kernel as KernelStub;
use Spiriit\Bundle\Tests\Integration\Stubs\StubNotification;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Smoke test that verifies the bundle can be installed on a Symfony project
 * without missing service declarations, broken references or circular dependencies.
 *
 * This mirrors what `bin/console lint:container` does in a real Symfony project:
 * it boots the kernel, compiles the container, and instantiates every service
 * the bundle declares to guarantee no runtime DI error will occur.
 */
class ContainerLintTest extends KernelTestCase
{
    /**
     * Service IDs that every configuration must register.
     */
    private const COMMON_SERVICES = [
        'spiriit_auth_log.login_listener',
        'spiriit_auth_log.login_service',
        'spiriit_auth_log.handler',
        'spiriit_auth_log.notification',
        'spiriit_auth_log.fetch_user_information',
        'spiriit_auth_log.login_event_dispatcher',
    ];

    /**
     * Additional services only present when using the default mailer notification.
     */
    private const MAILER_SERVICES = [
        'spiriit_auth_log.transports.mailer',
        'spiriit_auth_log.translator',
    ];

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

    /**
     * Booting the kernel compiles the DI container. Any missing reference,
     * wrong argument type, or circular dependency will throw here.
     *
     * @dataProvider configProvider
     */
    public function testContainerCompilesWithoutErrors(string $config): void
    {
        $kernel = self::bootKernel(['config' => $config]);

        self::assertSame('test', $kernel->getEnvironment());
    }

    /**
     * Every common service declared by the bundle must be present and
     * instantiable in every configuration variant.
     *
     * @dataProvider configProvider
     */
    public function testAllCommonServicesAreInstantiable(string $config): void
    {
        self::bootKernel(['config' => $config]);
        $container = self::getContainer();

        foreach (self::COMMON_SERVICES as $id) {
            self::assertTrue(
                $container->has($id),
                \sprintf('Service "%s" should be registered (config: %s)', $id, $config)
            );

            $service = $container->get($id);
            self::assertIsObject(
                $service,
                \sprintf('Service "%s" should be instantiable without error (config: %s)', $id, $config)
            );
        }
    }

    /**
     * With the default mailer transport, additional mailer-specific aliases
     * must be present and resolvable.
     */
    public function testMailerServicesAreInstantiableWithDefaultConfig(): void
    {
        self::bootKernel(['config' => 'minimal']);
        $container = self::getContainer();

        foreach (self::MAILER_SERVICES as $id) {
            self::assertTrue($container->has($id), \sprintf('Service "%s" should be registered', $id));
            self::assertIsObject($container->get($id), \sprintf('Service "%s" should be instantiable', $id));
        }
    }

    /**
     * Verify the full LoginService dependency chain resolves.
     * LoginService depends on FetchUserInformation, AuthenticationLogHandler,
     * NotificationInterface, EventDispatcher — each with its own sub-dependencies.
     */
    public function testLoginServiceFullDependencyChainResolves(): void
    {
        self::bootKernel(['config' => 'minimal']);
        $container = self::getContainer();

        self::assertInstanceOf(LoginService::class, $container->get('spiriit_auth_log.login_service'));
        self::assertInstanceOf(DoctrineAuthenticationLogHandler::class, $container->get('spiriit_auth_log.handler'));
        self::assertInstanceOf(FetchUserInformation::class, $container->get('spiriit_auth_log.fetch_user_information'));
        self::assertInstanceOf(MailerNotification::class, $container->get('spiriit_auth_log.notification'));
        self::assertInstanceOf(LoginListener::class, $container->get('spiriit_auth_log.login_listener'));
    }

    /**
     * Messenger handler must be instantiable and receive LoginService.
     */
    public function testMessengerHandlerDependencyChainResolves(): void
    {
        self::bootKernel(['config' => 'messenger']);

        self::assertInstanceOf(
            AuthLoginMessageHandler::class,
            self::getContainer()->get('spiriit_auth_log.login_message_handler')
        );
    }

    /**
     * When the user overrides the notification service, the container
     * must still compile and all services must resolve.
     */
    public function testCustomNotificationContainerCompiles(): void
    {
        self::bootKernel(['config' => 'custom_notification']);
        $container = self::getContainer();

        $notification = $container->get('spiriit_auth_log.notification');
        self::assertInstanceOf(NotificationInterface::class, $notification);
        self::assertInstanceOf(StubNotification::class, $notification);
        self::assertNotInstanceOf(MailerNotification::class, $notification);

        // Rest of the dependency chain still works
        self::assertInstanceOf(LoginService::class, $container->get('spiriit_auth_log.login_service'));
        self::assertInstanceOf(LoginListener::class, $container->get('spiriit_auth_log.login_listener'));
        self::assertInstanceOf(DoctrineAuthenticationLogHandler::class, $container->get('spiriit_auth_log.handler'));
    }

    /**
     * When location is configured, the locate method service and its injection
     * into FetchUserInformation must work.
     */
    public function testLocationProviderDependencyChainResolves(): void
    {
        self::bootKernel(['config' => 'location_ipapi']);
        $container = self::getContainer();

        self::assertInstanceOf(
            IpApiLocateMethod::class,
            $container->get('spiriit_auth_log.fetch_user_information_method')
        );
        self::assertInstanceOf(
            FetchUserInformation::class,
            $container->get('spiriit_auth_log.fetch_user_information')
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function configProvider(): iterable
    {
        yield 'minimal' => ['minimal'];
        yield 'location_ipapi' => ['location_ipapi'];
        yield 'location_geoip2' => ['location_geoip2'];
        yield 'messenger' => ['messenger'];
        yield 'custom_notification' => ['custom_notification'];
    }
}
