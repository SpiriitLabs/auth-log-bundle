<?php

/*
 * This file is part of the spiriitlabs/auth-log-bundle package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\Tests\Controller;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Spiriit\Bundle\AuthLogBundle\Confirmation\ConfirmationAction;
use Spiriit\Bundle\AuthLogBundle\Confirmation\ConfirmationToken;
use Spiriit\Bundle\AuthLogBundle\Controller\AuthenticationLogConfirmationController;
use Spiriit\Bundle\AuthLogBundle\Disavowal\DisavowalReactionExecutor;
use Spiriit\Bundle\AuthLogBundle\Disavowal\DisavowalReactionInterface;
use Spiriit\Bundle\AuthLogBundle\Disavowal\DisavowedLogin;
use Spiriit\Bundle\AuthLogBundle\DTO\UserIdentity;
use Spiriit\Bundle\AuthLogBundle\Entity\AbstractAuthenticationLog;
use Spiriit\Bundle\AuthLogBundle\Entity\AuthenticationLogStatus;
use Spiriit\Bundle\AuthLogBundle\Entity\AuthLogUserInterface;
use Spiriit\Bundle\AuthLogBundle\Entity\ConfirmableAuthenticationLogInterface;
use Spiriit\Bundle\AuthLogBundle\Entity\ConfirmableAuthenticationLogTrait;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;
use Spiriit\Bundle\AuthLogBundle\Listener\AuthenticationLogConfirmationEvent;
use Spiriit\Bundle\AuthLogBundle\Listener\AuthenticationLogEvents;
use Spiriit\Bundle\AuthLogBundle\Repository\ConfirmableAuthenticationLogRepositoryInterface;
use Spiriit\Bundle\Tests\Stubs\StubUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Twig\Environment;

final class AuthenticationLogConfirmationControllerTest extends TestCase
{
    private const SECRET = 'a-secret';

    public function testItShouldAcknowledgeOnValidPost(): void
    {
        $log = $this->pendingLog();
        $repository = $this->repository($log);
        $repository->expects(self::once())->method('save')->with($log);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(AuthenticationLogConfirmationEvent::class), AuthenticationLogEvents::LOGIN_ACKNOWLEDGED)
            ->willReturnArgument(0);

        $controller = $this->controller($repository, $dispatcher, $this->twigExpecting('result', 'acknowledged'));

        $response = $controller(
            $this->signedRequest('acknowledge', 'the-token', 'POST'),
            ConfirmationAction::ACKNOWLEDGE,
            'the-token',
        );

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertSame(AuthenticationLogStatus::ACKNOWLEDGED, $log->status());
    }

    public function testItShouldDisavowOnValidPost(): void
    {
        $log = $this->pendingLog();
        $repository = $this->repository($log);

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(AuthenticationLogConfirmationEvent::class), AuthenticationLogEvents::LOGIN_DISAVOWED)
            ->willReturnArgument(0);

        $controller = $this->controller($repository, $dispatcher, $this->twigExpecting('result', 'disavowed'));

        $controller($this->signedRequest('disavow', 'the-token', 'POST'), ConfirmationAction::DISAVOW, 'the-token');

        self::assertSame(AuthenticationLogStatus::DISAVOWED, $log->status());
    }

    public function testItShouldExecuteDisavowalReactionsWhenDisavowing(): void
    {
        $log = $this->pendingLog();

        $reaction = $this->createMock(DisavowalReactionInterface::class);
        $reaction->expects(self::once())
            ->method('react')
            ->with(self::callback(static fn (DisavowedLogin $disavowedLogin): bool => $disavowedLogin->authenticationLog === $log));

        $controller = $this->controller(
            $this->repository($log),
            $this->createMock(EventDispatcherInterface::class),
            $this->twigExpecting('result', 'disavowed'),
            new DisavowalReactionExecutor([$reaction]),
        );

        $controller($this->signedRequest('disavow', 'the-token', 'POST'), ConfirmationAction::DISAVOW, 'the-token');
    }

    public function testItShouldNotExecuteDisavowalReactionsWhenAcknowledging(): void
    {
        $log = $this->pendingLog();

        $reaction = $this->createMock(DisavowalReactionInterface::class);
        $reaction->expects(self::never())->method('react');

        $controller = $this->controller(
            $this->repository($log),
            $this->createMock(EventDispatcherInterface::class),
            $this->twigExpecting('result', 'acknowledged'),
            new DisavowalReactionExecutor([$reaction]),
        );

        $controller($this->signedRequest('acknowledge', 'the-token', 'POST'), ConfirmationAction::ACKNOWLEDGE, 'the-token');
    }

    public function testItShouldShowTheConfirmationFormOnGet(): void
    {
        $log = $this->pendingLog();

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::never())->method('dispatch');

        $twig = $this->createMock(Environment::class);
        $twig->expects(self::once())
            ->method('render')
            ->with('@SpiriitAuthLog/confirmation/show.html.twig', self::anything())
            ->willReturn('form');

        $controller = $this->controller($this->repository($log), $dispatcher, $twig);

        $response = $controller($this->signedRequest('acknowledge', 'the-token', 'GET'), ConfirmationAction::ACKNOWLEDGE, 'the-token');

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
        self::assertTrue($log->isPending());
    }

    public function testItShouldPassTheLogToTheConfirmationFormSoItCanShowTheLoginDetails(): void
    {
        $log = $this->pendingLog();

        $twig = $this->createMock(Environment::class);
        $twig->expects(self::once())
            ->method('render')
            ->with(
                '@SpiriitAuthLog/confirmation/show.html.twig',
                self::callback(static fn (array $context): bool => ($context['authenticationLog'] ?? null) === $log
                    && 'acknowledge' === ($context['action'] ?? null)),
            )
            ->willReturn('form');

        $controller = $this->controller($this->repository($log), $this->createMock(EventDispatcherInterface::class), $twig);

        $controller($this->signedRequest('acknowledge', 'the-token', 'GET'), ConfirmationAction::ACKNOWLEDGE, 'the-token');
    }

    public function testItShouldReportNotFoundOnGetWhenTokenMatchesNoLog(): void
    {
        $controller = $this->controller(
            $this->repository($this->pendingLog()),
            $this->createMock(EventDispatcherInterface::class),
            $this->twigExpecting('result', 'not_found'),
        );

        $response = $controller($this->signedRequest('acknowledge', 'unknown-token', 'GET'), ConfirmationAction::ACKNOWLEDGE, 'unknown-token');

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    public function testItShouldReportAnAlreadyReviewedLogOnGetWithoutShowingTheForm(): void
    {
        $log = $this->pendingLog();
        $log->acknowledge();

        $controller = $this->controller(
            $this->repository($log),
            $this->createMock(EventDispatcherInterface::class),
            $this->twigExpecting('result', 'already_reviewed'),
        );

        $response = $controller($this->signedRequest('acknowledge', 'the-token', 'GET'), ConfirmationAction::ACKNOWLEDGE, 'the-token');

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testItShouldRejectATamperedSignature(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::never())->method('dispatch');

        $controller = $this->controller($this->repository($this->pendingLog()), $dispatcher, $this->twigExpecting('result', 'invalid'));

        $request = Request::create('https://app.test/auth-log/confirm/acknowledge/the-token?expires='.(time() + 3600), 'GET');

        $response = $controller($request, ConfirmationAction::ACKNOWLEDGE, 'the-token');

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testItShouldRejectAnExpiredLink(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::never())->method('dispatch');

        $controller = $this->controller($this->repository($this->pendingLog()), $dispatcher, $this->twigExpecting('result', 'expired'));

        $response = $controller($this->signedRequest('acknowledge', 'the-token', 'GET', time() - 3600), ConfirmationAction::ACKNOWLEDGE, 'the-token');

        self::assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
    }

    public function testItShouldReportAnAlreadyReviewedLog(): void
    {
        $log = $this->pendingLog();
        $log->acknowledge();

        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::never())->method('dispatch');

        $controller = $this->controller($this->repository($log), $dispatcher, $this->twigExpecting('result', 'already_reviewed'));

        $response = $controller($this->signedRequest('acknowledge', 'the-token', 'POST'), ConfirmationAction::ACKNOWLEDGE, 'the-token');

        self::assertSame(Response::HTTP_OK, $response->getStatusCode());
    }

    public function testItShouldReportNotFoundWhenTokenMatchesNoLog(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::never())->method('dispatch');

        $controller = $this->controller($this->repository($this->pendingLog()), $dispatcher, $this->twigExpecting('result', 'not_found'));

        $response = $controller($this->signedRequest('acknowledge', 'unknown-token', 'POST'), ConfirmationAction::ACKNOWLEDGE, 'unknown-token');

        self::assertSame(Response::HTTP_NOT_FOUND, $response->getStatusCode());
    }

    private function controller(ConfirmableAuthenticationLogRepositoryInterface $repository, EventDispatcherInterface $dispatcher, Environment $twig, ?DisavowalReactionExecutor $disavowalReactionExecutor = null): AuthenticationLogConfirmationController
    {
        return new AuthenticationLogConfirmationController(
            new UriSigner(self::SECRET),
            $repository,
            $dispatcher,
            $twig,
            $disavowalReactionExecutor,
        );
    }

    private function signedRequest(string $action, string $token, string $method, ?int $expires = null): Request
    {
        $uriSigner = new UriSigner(self::SECRET);
        $url = \sprintf('https://app.test/auth-log/confirm/%s/%s?expires=%d', $action, $token, $expires ?? time() + 3600);

        return Request::create($uriSigner->sign($url), $method);
    }

    private function twigExpecting(string $template, string $outcome): Environment
    {
        $twig = $this->createMock(Environment::class);
        $twig->method('render')
            ->with(
                '@SpiriitAuthLog/confirmation/'.$template.'.html.twig',
                self::callback(static fn (array $context): bool => ($context['outcome'] ?? null) === $outcome),
            )
            ->willReturn('rendered');

        return $twig;
    }

    /**
     * @return ConfirmableAuthenticationLogRepositoryInterface&MockObject
     */
    private function repository(ConfirmableAuthenticationLogInterface $log): ConfirmableAuthenticationLogRepositoryInterface
    {
        $repository = $this->createMock(ConfirmableAuthenticationLogRepositoryInterface::class);
        $repository->method('findOneByConfirmationToken')
            ->willReturnCallback(static fn (string $token): ?ConfirmableAuthenticationLogInterface => 'the-token' === $token ? $log : null);

        return $repository;
    }

    private function pendingLog(): ConfirmableAuthenticationLogInterface&AbstractAuthenticationLog
    {
        $log = new class(new UserIdentity('user-1', StubUser::class), new UserInformation('127.0.0.1', 'PHPUnit', new \DateTimeImmutable(), null)) extends AbstractAuthenticationLog implements ConfirmableAuthenticationLogInterface {
            use ConfirmableAuthenticationLogTrait;

            public function getUser(): AuthLogUserInterface
            {
                return new StubUser();
            }
        };
        $log->enableConfirmation(new ConfirmationToken('the-token'));

        return $log;
    }
}
