<?php

/*
 * This file is part of the SpiriitLabs php-excel-rust package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Spiriit\Bundle\AuthLogBundle\DependencyInjection\SpiriitAuthLogExtension;
use Spiriit\Bundle\AuthLogBundle\SpiriitAuthLogBundle;
use Spiriit\Bundle\Tests\Integration\Stubs\Kernel;
use Symfony\Bundle\FrameworkBundle\DependencyInjection\FrameworkExtension;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class SpiriitAuthLogExtensionTest extends TestCase
{
    public function testEmptyConfiguration(): void
    {
        $container = $this->createContainer([
            'framework' => [
                'secret' => 'testing',
            ],
            'spiriit_auth_log' => [],
        ]);

        self::expectException(InvalidConfigurationException::class);
        self::expectExceptionMessage('The child config "transports" under "spiriit_auth_log" must be configured.');

        $container->compile();
    }

    private function createContainer(array $configs = []): ContainerBuilder
    {
        $container = new ContainerBuilder(new ParameterBag([
            'kernel.bundles_metadata' => [],
            'kernel.cache_dir' => __DIR__,
            'kernel.debug' => false,
            'kernel.environment' => 'test',
            'kernel.name' => 'kernel',
            'kernel.root_dir' => __DIR__,
            'kernel.project_dir' => __DIR__,
            'kernel.container_class' => 'AutowiringTestContainer',
            'kernel.charset' => 'utf8',
            'kernel.runtime_environment' => 'test',
            'env(base64:default::SYMFONY_DECRYPTION_SECRET)' => 'dummy',
            'kernel.build_dir' => __DIR__,
            'debug.file_link_format' => null,
            'env(bool:default::SYMFONY_TRUST_X_SENDFILE_TYPE_HEADER)' => true,
            'env(default::SYMFONY_TRUSTED_HOSTS)' => [],
            'env(default::SYMFONY_TRUSTED_PROXIES)' => [],
            'env(default::SYMFONY_TRUSTED_HEADERS)' => [],
            'kernel.bundles' => [
                'FrameworkBundle' => FrameworkBundle::class,
                'SpiriitAuthLogBundle' => SpiriitAuthLogBundle::class,
            ],
        ]));

        $container->set('kernel', function () {
            return new Kernel('test', false);
        });

        $container->registerExtension(new FrameworkExtension());
        $container->registerExtension(new SpiriitAuthLogExtension());

        foreach ($configs as $extension => $config) {
            $container->loadFromExtension($extension, $config);
        }

        return $container;
    }
}
