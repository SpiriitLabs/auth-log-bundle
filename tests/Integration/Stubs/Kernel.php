<?php

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\Tests\Integration\Stubs;

use Spiriit\Bundle\AuthLogBundle\SpiriitAuthLogBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    /* @phpstan-ignore-next-line */
    public const IS_LEGACY = 5 > BaseKernel::MAJOR_VERSION;

    private string $config;

    public function __construct(string $environment, bool $debug, string $config = 'base')
    {
        parent::__construct($environment, $debug);
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function registerBundles(): array
    {
        return [
            new FrameworkBundle(),
            new SpiriitAuthLogBundle(),
            new Bundle(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/SpiriitAuthLogBundle/cache';
    }

    /**
     * {@inheritdoc}
     */
    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/SpiriitAuthLogBundle/logs';
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(\sprintf(__DIR__.'/../config/%s_config.yaml', $this->config));
    }
}
