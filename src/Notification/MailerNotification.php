<?php

declare(strict_types=1);

/*
 * This file is part of the SpiriitLabs php-excel-rust package.
 * Copyright (c) SpiriitLabs <https://www.spiriit.com/>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiriit\Bundle\AuthLogBundle\Notification;

use Spiriit\Bundle\AuthLogBundle\Entity\AbstractAuthenticationLog;
use Spiriit\Bundle\AuthLogBundle\Entity\AuthenticableLogInterface;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;

final class MailerNotification implements NotificationInterface
{
    /**
     * @param mixed[] $addresses
     */
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly TranslatorInterface $translator,
        private readonly array $addresses,
    ) {
    }

    public function send(UserInformation $userInformation, AbstractAuthenticationLog $authenticableLog): void
    {
        if (!is_a($user = $authenticableLog->getUser(), AuthenticableLogInterface::class)) {
            throw new \InvalidArgumentException('The $authenticableLog must implement AuthenticableLogInterface');
        }

        $templateEmail = (new TemplatedEmail())
            ->to(new Address(
                address: $user->getAuthenticationLogsToEmail(),
                name: $user->getAuthenticationLogsToEmailName())
            )
            ->from(new Address(address: $this->addresses['fromEmail'], name: $this->addresses['fromName']))
            ->subject(subject: $this->translator->trans('notification.subject', [], 'SpiriitAuthLogBundle'))
            ->htmlTemplate('@SpiriitAuthLog/new_device.html.twig')
            ->context(
                [
                    'userInformation' => $userInformation,
                    'authenticableLog' => $user,
                ],
            );

        $this->mailer->send($templateEmail);
    }
}
