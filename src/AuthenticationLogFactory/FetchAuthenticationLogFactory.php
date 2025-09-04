<?php

/*
 * This file is part of the SpiriitLabs php-excel-rust package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\AuthenticationLogFactory;

class FetchAuthenticationLogFactory
{
    /**
     * @param AuthenticationLogFactoryInterface[] $authenticationLogFactories
     */
    public function __construct(
        private readonly iterable $authenticationLogFactories,
    ) {
    }

    public function createFrom(string $factorySupport): AuthenticationLogFactoryInterface
    {
        foreach ($this->authenticationLogFactories as $authenticationLogFactory) {
            if ($factorySupport === $authenticationLogFactory->supports()) {
                return $authenticationLogFactory;
            }
        }

        throw new \InvalidArgumentException('There is no authentication log factory available named '.$factorySupport);
    }
}
