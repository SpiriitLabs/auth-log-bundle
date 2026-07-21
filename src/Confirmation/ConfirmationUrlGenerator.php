<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\Confirmation;

use Spiriit\Bundle\AuthLogBundle\Confirmation\Exception\ConfirmationNotEnabledException;
use Spiriit\Bundle\AuthLogBundle\Entity\ConfirmableAuthenticationLogInterface;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final readonly class ConfirmationUrlGenerator
{
    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private UriSigner $uriSigner,
        private string $routeName,
        private string $tokenTtl,
    ) {
    }

    public function generate(ConfirmableAuthenticationLogInterface $authenticationLog): ConfirmationLinks
    {
        $token = $authenticationLog->confirmationToken();

        if (null === $token) {
            throw new ConfirmationNotEnabledException('Cannot generate confirmation links for a log whose confirmation has not been enabled.');
        }

        return new ConfirmationLinks(
            acknowledgeUrl: $this->signedUrl($token, ConfirmationAction::ACKNOWLEDGE),
            disavowUrl: $this->signedUrl($token, ConfirmationAction::DISAVOW),
        );
    }

    private function signedUrl(string $token, ConfirmationAction $action): string
    {
        $url = $this->urlGenerator->generate(
            $this->routeName,
            [
                'action' => $action->value,
                'token' => $token,
                'expires' => (new \DateTimeImmutable('+'.$this->tokenTtl))->getTimestamp(),
            ],
            UrlGeneratorInterface::ABSOLUTE_URL,
        );

        return $this->uriSigner->sign($url);
    }
}
