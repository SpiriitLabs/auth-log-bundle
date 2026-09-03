<?php

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\Disavowal;

use Psr\Log\LoggerInterface;
use Spiriit\Bundle\AuthLogBundle\Entity\ConfirmableAuthenticationLogInterface;

final readonly class DisavowalReactionExecutor
{
    /**
     * @param iterable<DisavowalReactionInterface> $reactions
     */
    public function __construct(
        private iterable $reactions,
        private ?LoggerInterface $logger = null,
    ) {
    }

    public function execute(ConfirmableAuthenticationLogInterface $authenticationLog): void
    {
        try {
            $disavowedLogin = new DisavowedLogin($authenticationLog, $authenticationLog->getUser(), $authenticationLog->userIdentity());
        } catch (\Throwable $exception) {
            $this->logger?->error('Disavowal reactions skipped, the user of the disavowed log cannot be resolved: {message}', [
                'message' => $exception->getMessage(),
                'exception' => $exception,
            ]);

            return;
        }

        foreach ($this->reactions as $reaction) {
            try {
                $reaction->react($disavowedLogin);
            } catch (\Throwable $exception) {
                $this->logger?->error('Disavowal reaction "{reaction}" failed for user "{user}": {message}', [
                    'reaction' => $reaction::class,
                    'user' => $disavowedLogin->userIdentity->userIdentifier,
                    'message' => $exception->getMessage(),
                    'exception' => $exception,
                ]);
            }
        }
    }
}
