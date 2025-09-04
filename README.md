# SpiriitLabs Auth Log Bundle

A comprehensive Symfony bundle for logging authentication events including successful logins. This bundle provides detailed logging with IP address tracking, user agent information, geolocation data, and email notifications.

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-8.3%2B-blue.svg)](https://php.net)
[![Symfony](https://img.shields.io/badge/symfony-6.4%2B%7C7.0%2B-blue.svg)](https://symfony.com)

## Features

- **Authentication Event Logging**: Track successful logins with detailed information
- **Geolocation Support**: Enrich logs with location data using GeoIP2 or IP API
- **Email Notifications**: Send email alerts for authentication events
- **Messenger Integration**: Optional processing with Symfony Messenger
- **Highly Configurable**: Flexible configuration options for various use cases
- **Extensible**: Easy to extend with custom authentication log entities

## Requirements

- PHP 8.3 or higher
- Symfony 6.4+ or 7.0+
- Doctrine ORM 3.0+ or 4.0+

## Installation

Install the bundle using Composer:

```bash
composer require spiriitlabs/auth-log-bundle
```

If you're using Symfony Flex, the bundle will be automatically registered. Otherwise, add it to your `config/bundles.php`:

```php
<?php

return [
    // ...
    Spiriit\Bundle\AuthLogBundle\SpiriitAuthLogBundle::class => ['all' => true],
];
```

## Configuration

Create a configuration file `config/packages/spiriit_auth_log.yaml`:

### Basic Configuration

```yaml
spiriit_auth_log:
    # Enable Messenger integration for async processing
    messenger: 'messenger.default_bus' # no required

    # Email notification settings
    transports:
        mailer: 'mailer'
        sender_email: 'no-reply@yourdomain.com'
        sender_name: 'Your App Security'

    # Geolocation configuration (optional)
    location:
        method: null # 'geoip2', 'ipApi', or null to disable
        geoip2_database_path: null # Required if using geoip2
```

### Configuration with GeoIP2

Using GeoIP2 requires downloading the GeoLite2 database from MaxMind.

```yaml
spiriit_auth_log:
    # ...

    location:
        method: 'geoip2'
        geoip2_database_path: '%kernel.project_dir%/var/GeoLite2-City.mmdb'
```

### Configuration with IP API

ipApi.com offers a free tier with a limit of 45 requests per minute and 1,000 requests per day; exceeding these limits requires a paid plan.

```yaml
spiriit_auth_log:
    # ...

    location:
        method: 'ipApi'
```

## Usage

### 1. Create Your Authentication Log Entity

Create an entity that extends `AbstractAuthenticationLog`:

Here comes the fun part: building your Authentication Log Entity. We will use
an User class as an example.

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Spiriit\Bundle\AuthLogBundle\Entity\AbstractAuthenticationLog;
use Spiriit\Bundle\AuthLogBundle\Entity\AuthenticableLogInterface;

#[ORM\Entity]
#[ORM\Table(name: 'user_authentication_logs')]
class UserAuthenticationLog extends AbstractAuthenticationLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User:class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    public function __construct(
        User $user,
        UserInformation $userInformation,
    ) {
        $this->user = $user;
        parent::__construct(
            userInformation: $userInformation
        );
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): AuthenticableLogInterface
    {
        return $this->user;
    }
}
```

### 2. Implement AuthenticableLogInterface

Equip your User with `AuthenticableLogInterface`:

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Spiriit\Bundle\AuthLogBundle\Entity\AuthenticableLogInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
class User implements UserInterface, AuthenticableLogInterface
{
    // ... your existing User properties and methods

    public function getAuthenticationLogFactoryName(): string
    {
        return 'user'; // This should match your factory service name
    }

    public function getAuthenticationLogsToEmail(): string
    {
        return $this->email;
    }

    public function getAuthenticationLogsToEmailName(): string
    {
        return $this->getFullName();
    }
}
```

### 3. Create an Authentication Log Factory

Spin up your Authentication Log Factory:

```php
<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\UserAuthenticationLog;
use Spiriit\Bundle\AuthLogBundle\AuthenticationLogFactoryInterface;
use Spiriit\Bundle\AuthLogBundle\Entity\AuthenticableLogInterface;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;

class UserAuthenticationLogFactory implements AuthenticationLogFactoryInterface
{
    public function createFrom(string $userIdentifier, UserInformation $userInformation): AbstractAuthenticationLog
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['identifiant' => $userIdentifier]);

        if (!$user instanceof User) {
            throw new \InvalidArgumentException();
        }

        return new AssureAuthLog(
            user: $user,
            userInformation: $userInformation,
        );
    }

    public function isKnown(AbstractAuthenticationLog $authenticationLog): bool
    {
        // example of basic logic

        return (bool) $this->entityManager->createQueryBuilder()
            ->select('al')
            ->from(UserAuthenticationLog::class, 'uu')
            ->innerJoin('uu.user', 'u')
            ->andWhere('u.createdAt < :one_minute')
            ->andWhere('uu.ipAddress = :ip')
            ->andWhere('uu.userAgent = :ua')
            ->andWhere('uu.id = :user_id')
            ->setParameter('user_id', $authenticationLog->getUser()->getId())
            ->setParameter('one_minute', new \DateTimeImmutable('-1 minute'))
            ->setParameter('ip', $authenticationLog->getIpAddress())
            ->setParameter('ua', $authenticationLog->getUserAgent())
            ->getQuery()
            ->getOneOrNullResult() ?? false;
    }

    public function supports(AuthenticableLogInterface $authenticableLog): string
    {
        return 'user';
    }
}
```

## Messenger Integration

To enable asynchronous processing with Symfony Messenger:

1. Configure the bundle:

```yaml
spiriit_auth_log:
    messenger: 'messenger.default_bus' # can be your custom service id
```

2. Optional Configure your messenger transports in `config/packages/messenger.yaml`:

By default, the message transport is set to `sync`, but you can change it to any transport you prefer:

```yaml
framework:
    messenger:
        routing:
            'Spiriit\Bundle\AuthLogBundle\Messenger\AuthLoginMessage\AuthLoginMessage': my_async_transport
```

## Email Notifications

The bundle send email notifications for authentication events.

Currently only InteractiveEvent is supported.

Ensure you have configured Symfony Mailer and enabled notifications:

```yaml
spiriit_auth_log:
    transports:
        mailer: 'mailer' # default is symfony 'mailer' service, you can customize it
        sender_email: 'security@yourdomain.com'
        sender_name: 'Security Team'
```

The parameter mailer accepts any service that implements `Spiriit\Bundle\AuthLogBundle\Notification\NotificationInterface`.

## Events

The bundle will dispatch an event `AuthenticationLogEvents::LOGIN` - your job is to catch it.

Why? Because you decide how the entity gets persisted (the bundle won’t do it for you).
Once you’ve saved it, make sure to mark the event as persisted, so the bundle can keep rolling smoothly.

You can listen to these events to add custom logic:

```php
<?php

namespace App\EventListener;

use Spiriit\Bundle\AuthLogBundle\Listener\AuthenticationLogEvent;
use Spiriit\Bundle\AuthLogBundle\Listener\AuthenticationLogEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CustomAuthenticationLogListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            AuthenticationLogEvents::LOGIN => 'onLogin',
        ];
    }

    public function onLogin(AuthenticationLogEvent $event): void
    {
        // Add your custom logic here
        $log = $event->getLog();
        $userInfo = $event->getUserInformation();

        // persist log
        // flush

        // !! IMPORTANT !! Make sure to mark the event as persisted to continue the process
        $event->markAsPersisted();
    }
}
```

## Template

You can use the default template, not recommended indeed!

Override here

```bash
templates/bundles/SpiriitAuthLogBundle/new_device.html.twig
```

You can access to UserInformation object:

The `userInformation` object contains details about a user's login session. Each property is optional and may be null or empty.

## Properties

### `ipAddress`
- **Type:** `string | null`
- **Description:** The IP address from which the user logged in.

### `userAgent`
- **Type:** `string | null`
- **Description:** The browser or device information of the user.

### `loginAt`
- **Type:** `\DateTimeInterface | null`
- **Description:** The timestamp of the user's login.

### `location`
- **Type:** `LocateValues | null`
- **Description:** Geographical information about the user's location.
- **Properties:**
    - `city` (`string`) — The city name.
    - `country` (`string`) — The country name.
    - `latitude` (`float`) — Latitude coordinate.
    - `longitude` (`float`) — Longitude coordinate.

## Testing

Run the test suite:

```bash
vendor/bin/simple-phpunit
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This bundle is released under the MIT License. See the [LICENSE](LICENSE) file for details.

## Support

For questions and support, please contact [dev@spiriit.com](mailto:dev@spiriit.com) or open an issue on GitHub.
# auth-log-bundle
