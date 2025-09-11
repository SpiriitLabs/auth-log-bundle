# SpiriitLabs Auth Log Bundle

With this Symfony bundle you can send an email alert when a user logs in from a new context — for example:

* a different IP address
* a different location (geolocation)
* a different User Agent (device/browser)


This helps detect unusual login activity early and increases visibility into authentication events. 

[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/php-8.2%2B-blue.svg)](https://php.net)
[![Symfony](https://img.shields.io/badge/symfony-6.4%2B%7C7.0%2B-blue.svg)](https://symfony.com)
[![Latest Stable Version](https://poser.pugx.org/spiriitlabs/auth-log-bundle/v/stable.svg)](https://packagist.org/packages/spiriitlabs/auth-log-bundle)
[![CI Tests](https://github.com/SpiriitLabs/auth-log-bundle/actions/workflows/ci.yml/badge.svg)](https://github.com/SpiriitLabs/auth-log-bundle/actions/workflows/ci.yml)

## OWASP Authentication Best Practices

To ensure strong authentication security, this bundle aligns with guidance from the OWASP Authentication Cheat Sheet by:

Treating authentication failures or unusual logins as events worthy of detection and alerting

Ensuring all login events are logged, especially when the context changes (IP, location, device)

Using secure channels (TLS) for all authentication-related operations

Validating and normalizing incoming data (e.g. user agent strings, IP addresses) to avoid ambiguity or spoofing

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
    # Email notification settings
    transports:
        sender_email: 'no-reply@yourdomain.com'
        sender_name: 'Your App Security'
```

### Configuration with GeoIP2

Using GeoIP2 requires downloading the GeoLite2 database from MaxMind.

```yaml
spiriit_auth_log:
    # ...

    location:
        provider: 'geoip2'
        geoip2_database_path: '%kernel.project_dir%/var/GeoLite2-City.mmdb'
```

### Configuration with IP API

ipApi.com offers a free tier with a limit of 45 requests per minute and 1,000 requests per day; exceeding these limits requires a paid plan.

```yaml
spiriit_auth_log:
    # ...

    location:
        provider: 'ipApi'
```

## Usage

## 1. Implement AuthenticableLogInterface

Equip your User with `AuthenticableLogInterface`:

You could use any entity, here we use a User class as an example.

But it's not an obligation, you have just to implement the interface.

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
        return 'customer'; // This should match your factory service name
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

### 2. Create Your Authentication Log Entity

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
        $realCustomer = $this->entityManager->getRepository(User::class)->findOneBy(['identifiant' => $userIdentifier]);

        if (!$realCustomer instanceof User) {
            throw new \InvalidArgumentException();
        }

        return new UserReference(
            type: 'customer',
            id: (string) $realCustomer->getCustomerId(),
        );
    }

    public function isKnown(UserReference $userReference, UserInformation $userInformation): bool
    {
        // Your logic to determine if the authentication log is known
        // here is an example with Doctrine QueryBuilder
        // you can also use a different storage system like Redis, ElasticSearch, etc.

        return (bool) $this->entityManager->createQueryBuilder()
            ->select('al')
            ->from(UserAuthenticationLog::class, 'uu')
            ->innerJoin('uu.user', 'u')
            ->andWhere('uu.ipAddress = :ip')
            ->andWhere('uu.userAgent = :ua')
            ->andWhere('uu.id = :user_id')
            ->setParameter('user_id', $userReference->id)
            ->setParameter('ip', $userInformation->ipAddress)
            ->setParameter('ua', $userInformation->userAgent)
            ->getQuery()
            ->getOneOrNullResult() ?? false;
    }

    public function supports(AuthenticableLogInterface $authenticableLog): string
    {
        return 'customer'; // This should match the value returned by getAuthenticationLogFactoryName()
    }
}
```

## Messenger Integration

To enable a/synchronous processing with Symfony Messenger:

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

Currently only `LoginSuccessEvent` is supported.

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
            AuthenticationLogEvents::NEW_DEVICE => 'onLogin',
        ];
    }

    public function onLogin(AuthenticationLogEvent $event): void
    {
        // Add your custom logic here
        $log = $event->getUserReference();
        $userInfo = $event->getUserInformation();

        // persist log
        // flush

        // !! IMPORTANT !! Make sure to mark the event as persisted to continue the process
        $event->markAsHandled();
    }
}
```

## Template

You can use the default template, not recommended indeed!

![ipApi.png](doc/images/ipApi.png)

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
