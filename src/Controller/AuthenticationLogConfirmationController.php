<?php

declare(strict_types=1);

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\Controller;

use Spiriit\Bundle\AuthLogBundle\Confirmation\ConfirmationAction;
use Spiriit\Bundle\AuthLogBundle\Entity\ConfirmableAuthenticationLogInterface;
use Spiriit\Bundle\AuthLogBundle\Listener\AuthenticationLogConfirmationEvent;
use Spiriit\Bundle\AuthLogBundle\Listener\AuthenticationLogEvents;
use Spiriit\Bundle\AuthLogBundle\Repository\ConfirmableAuthenticationLogRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;

final readonly class AuthenticationLogConfirmationController
{
    public function __construct(
        private UriSigner $uriSigner,
        private ConfirmableAuthenticationLogRepositoryInterface $confirmableAuthenticationLogRepository,
        private EventDispatcherInterface $eventDispatcher,
        private Environment $twig,
    ) {
    }

    public function __invoke(Request $request, ConfirmationAction $action, string $token): Response
    {
        if (!$this->uriSigner->checkRequest($request)) {
            return $this->render('invalid', Response::HTTP_FORBIDDEN);
        }

        if ($this->isExpired($request)) {
            return $this->render('expired', Response::HTTP_FORBIDDEN);
        }

        if (!$request->isMethod(Request::METHOD_POST)) {
            return $this->renderConfirmationForm($action);
        }

        $authenticationLog = $this->confirmableAuthenticationLogRepository->findOneByConfirmationToken($token);

        if (null === $authenticationLog || !$authenticationLog->isPending()) {
            return $this->render('already_reviewed');
        }

        $this->review($authenticationLog, $action);

        return $this->render(ConfirmationAction::ACKNOWLEDGE === $action ? 'acknowledged' : 'disavowed');
    }

    private function review(ConfirmableAuthenticationLogInterface $authenticationLog, ConfirmationAction $action): void
    {
        match ($action) {
            ConfirmationAction::ACKNOWLEDGE => $authenticationLog->acknowledge(),
            ConfirmationAction::DISAVOW => $authenticationLog->disavow(),
        };

        $this->confirmableAuthenticationLogRepository->save($authenticationLog);

        $this->eventDispatcher->dispatch(
            new AuthenticationLogConfirmationEvent($authenticationLog),
            ConfirmationAction::ACKNOWLEDGE === $action
                ? AuthenticationLogEvents::LOGIN_ACKNOWLEDGED
                : AuthenticationLogEvents::LOGIN_DISAVOWED,
        );
    }

    private function isExpired(Request $request): bool
    {
        $expires = $request->query->getInt('expires');

        return 0 === $expires || $expires < time();
    }

    private function renderConfirmationForm(ConfirmationAction $action): Response
    {
        return new Response($this->twig->render('@SpiriitAuthLog/confirmation/show.html.twig', [
            'action' => $action->value,
        ]));
    }

    private function render(string $outcome, int $status = Response::HTTP_OK): Response
    {
        return new Response(
            $this->twig->render('@SpiriitAuthLog/confirmation/result.html.twig', ['outcome' => $outcome]),
            $status,
        );
    }
}
