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

* Treating authentication failures or unusual logins as events worthy of detection and alerting
* Ensuring all login events are logged, especially when the context changes (IP, location, device)
* Using secure channels (TLS) for all authentication-related operations
* Validating and normalizing incoming data (e.g. user agent strings, IP addresses) to avoid ambiguity or spoofing

## Features

- **Authentication Event Logging**: Track successful logins with detailed information
- **Geolocation Support**: Enrich logs with location data using GeoIP2 or IP API
- **Email Notifications**: Send email alerts for authentication events
- **Messenger Integration**: Optional processing with Symfony Messenger
- **Highly Configurable**: Flexible configuration options for various use cases
- **Extensible**: Easy to extend with custom authentication log entities

## Getting Started

### 1. Install

```bash
composer require spiriitlabs/auth-log-bundle
```

### 2. Configure

```yaml
# config/packages/spiriit_auth_log.yaml
spiriit_auth_log:
    transports:
        sender_email: 'no-reply@yourdomain.com'
        sender_name: 'Security'
```

### 3. Implement AuthenticableLogInterface on your User

```php
use Spiriit\Bundle\AuthLogBundle\Entity\AuthenticableLogInterface;

class User implements UserInterface, AuthenticableLogInterface
{
    public function getAuthenticationLogFactoryName(): string { return 'user'; }
    public function getAuthenticationLogsToEmail(): string { return $this->email; }
    public function getAuthenticationLogsToEmailName(): string { return $this->name; }
}
```

### 4. Create your log entity

```php
use Spiriit\Bundle\AuthLogBundle\Entity\AbstractAuthenticationLog;

#[ORM\Entity]
class UserAuthLog extends AbstractAuthenticationLog
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private User $user;

    public function __construct(User $user, UserInformation $info) {
        $this->user = $user;
        parent::__construct($info);
    }
    public function getUser(): AuthenticableLogInterface { return $this->user; }
}
```

### 5. Create the factory

```php
use Spiriit\Bundle\AuthLogBundle\AuthenticationLogFactory\AuthenticationLogFactoryInterface;

class UserAuthLogFactory implements AuthenticationLogFactoryInterface
{
    public function __construct(private EntityManagerInterface $em) {}

    public function createUserReference(string $userIdentifier): UserReference
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $userIdentifier]);
        return new UserReference(type: 'user', id: (string) $user->getId());
    }

    public function isKnown(UserReference $ref, UserInformation $info): bool
    {
        return (bool) $this->em->createQueryBuilder()
            ->select('l')->from(UserAuthLog::class, 'l')
            ->where('l.user = :id AND l.ipAddress = :ip AND l.userAgent = :ua')
            ->setParameters(['id' => $ref->id, 'ip' => $info->ipAddress, 'ua' => $info->userAgent])
            ->getQuery()->getOneOrNullResult();
    }

    public function supports(): string { return 'user'; } // must match getAuthenticationLogFactoryName()
}
```

### 6. Listen to the event

```php
use Spiriit\Bundle\AuthLogBundle\Listener\{AuthenticationLogEvent, AuthenticationLogEvents};

class AuthLogListener implements EventSubscriberInterface
{
    public function __construct(private EntityManagerInterface $em) {}

    public static function getSubscribedEvents(): array
    {
        return [AuthenticationLogEvents::NEW_DEVICE => 'onNewDevice'];
    }

    public function onNewDevice(AuthenticationLogEvent $event): void
    {
        $user = $this->em->getRepository(User::class)->find($event->getUserReference()->id);
        $log = new UserAuthLog($user, $event->getUserInformation());
        $this->em->persist($log);
        $this->em->flush();
         // persist log or custom process
        $event->markAsHandled(); // Required to continue the notification process
    }
}
```

## Options

### Geolocation

**GeoIP2** (local database):
```yaml
spiriit_auth_log:
    location:
        provider: 'geoip2'
        geoip2_database_path: '%kernel.project_dir%/var/GeoLite2-City.mmdb'
```

**IP API** (external API, 45 req/min free):
```yaml
spiriit_auth_log:
    location:
        provider: 'ipApi'
```

### Messenger (async processing)

```yaml
spiriit_auth_log:
    messenger: 'messenger.default_bus'
```

Optional routing:
```yaml
framework:
    messenger:
        routing:
            'Spiriit\Bundle\AuthLogBundle\Messenger\AuthLoginMessage\AuthLoginMessage': async
```

# Custom email template

## Template

You can use the default template, not recommended indeed!

![ipApi.png](doc/images/ipApi.png)

Override here

Create the file:

```
templates/bundles/SpiriitAuthLogBundle/new_device.html.twig
```

The `userInformation` object contains: `ipAddress`, `userAgent`, `loginAt`, `location` (city, country, latitude, longitude).

## Testing

Run the test suite:

```bash
vendor/bin/simple-phpunit
```

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request

## License

This bundle is released under the MIT License. See the [LICENSE](LICENSE) file for details.

## Support

For questions and support, please contact [dev@spiriit.com](mailto:dev@spiriit.com) or open an issue on GitHub.
