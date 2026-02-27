<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\Tests\DependencyInjection;

use Spiriit\Bundle\Tests\Integration\Stubs\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class SpiriitAuthLogExtensionTest extends KernelTestCase
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
        return new Kernel('test', true, $options['config'] ?? 'base');
    }

    public function testItShouldFailWithEmptyConfiguration(): void
    {
        self::expectException(InvalidConfigurationException::class);
        self::expectExceptionMessage('The child config "transports" under "spiriit_auth_log" must be configured.');

        self::bootKernel(['config' => 'empty']);
    }
}
