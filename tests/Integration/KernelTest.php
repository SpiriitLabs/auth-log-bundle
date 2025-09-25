<?php

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\Tests\Integration;

use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\FetchUserInformation;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\LocateUserInformation\Geoip2LocateMethod;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\LocateUserInformation\IpApiLocateMethod;
use Spiriit\Bundle\AuthLogBundle\Notification\MailerNotification;
use Spiriit\Bundle\Tests\Integration\Stubs\Kernel as KernelStub;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Mailer\MailerInterface;

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
     * {@inheritdoc}
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
        self::assertArrayHasKey('sender_email', $config['transports']);
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
        self::assertNotNull(self::getContainer()->get('spiriit_auth_log.login_message_handler'));
    }
}
