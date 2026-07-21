<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\Tests\Confirmation;

use PHPUnit\Framework\TestCase;
use Spiriit\Bundle\AuthLogBundle\Confirmation\ConfirmationUrlGenerator;
use Spiriit\Bundle\AuthLogBundle\Confirmation\Exception\ConfirmationNotEnabledException;
use Spiriit\Bundle\AuthLogBundle\Entity\ConfirmableAuthenticationLogInterface;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ConfirmationUrlGeneratorTest extends TestCase
{
    public function testItShouldGenerateTwoSignedLinks(): void
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturnCallback(
            static fn (string $name, array $parameters = []): string => 'https://app.test/auth-log/confirm/'.$parameters['action'].'/'.$parameters['token'].'?expires='.$parameters['expires']
        );

        $uriSigner = new UriSigner('a-secret');

        $log = $this->createStub(ConfirmableAuthenticationLogInterface::class);
        $log->method('confirmationToken')->willReturn('the-token');

        $confirmationUrlGenerator = new ConfirmationUrlGenerator($urlGenerator, $uriSigner, 'spiriit_auth_log_confirm', '3 days');

        $links = $confirmationUrlGenerator->generate($log);

        self::assertNotSame($links->acknowledgeUrl, $links->disavowUrl);
        self::assertTrue($uriSigner->check($links->acknowledgeUrl));
        self::assertTrue($uriSigner->check($links->disavowUrl));
        self::assertStringContainsString('acknowledge', $links->acknowledgeUrl);
        self::assertStringContainsString('disavow', $links->disavowUrl);
    }

    public function testItShouldThrowWhenConfirmationIsNotEnabled(): void
    {
        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->expects(self::never())->method('generate');

        $log = $this->createStub(ConfirmableAuthenticationLogInterface::class);
        $log->method('confirmationToken')->willReturn(null);

        $confirmationUrlGenerator = new ConfirmationUrlGenerator($urlGenerator, new UriSigner('a-secret'), 'spiriit_auth_log_confirm', '3 days');

        $this->expectException(ConfirmationNotEnabledException::class);

        $confirmationUrlGenerator->generate($log);
    }
}
