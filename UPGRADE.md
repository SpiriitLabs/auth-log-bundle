# Upgrade Guide

## Upgrading from 2.0 to 2.1

Release 2.1 introduces the optional login-confirmation feature ("It was me / It wasn't me"). As part of it, the bundle's persistence contract no longer references the concrete `AbstractAuthenticationLog` mapped superclass — it now depends on the new `AuthenticationLogInterface`. This decouples the contract from Doctrine inheritance: an integrator can implement a log without extending the mapped superclass.

`AbstractAuthenticationLog` implements `AuthenticationLogInterface`, so your entity needs **no change**. Only your repository signatures must be widened.

### 1. Repository: widen `save()` and `createLog()` (required)

`AuthenticationLogRepositoryInterface::save()` and `AuthenticationLogCreatorInterface::createLog()` now type-hint `AuthenticationLogInterface`. A parameter type cannot be narrowed in an implementation, so keeping `AbstractAuthenticationLog` triggers a fatal error — update the signatures.

**Before:**

```php
use Spiriit\Bundle\AuthLogBundle\Entity\AbstractAuthenticationLog;

class UserAuthLogRepository extends EntityRepository implements
    AuthenticationLogRepositoryInterface,
    AuthenticationLogCreatorInterface
{
    public function save(AbstractAuthenticationLog $log): void
    {
        $this->getEntityManager()->persist($log);
        $this->getEntityManager()->flush();
    }

    public function createLog(string $userIdentifier, UserInformation $userInformation): AbstractAuthenticationLog
    {
        return new UserAuthLog($userIdentifier, $userInformation);
    }
}
```

**After:**

```php
use Spiriit\Bundle\AuthLogBundle\Entity\AuthenticationLogInterface;

class UserAuthLogRepository extends EntityRepository implements
    AuthenticationLogRepositoryInterface,
    AuthenticationLogCreatorInterface
{
    public function save(AuthenticationLogInterface $log): void
    {
        $this->getEntityManager()->persist($log);
        $this->getEntityManager()->flush();
    }

    public function createLog(string $userIdentifier, UserInformation $userInformation): AuthenticationLogInterface
    {
        return new UserAuthLog($userIdentifier, $userInformation);
    }
}
```

### Summary of Changed Interfaces

| Interface | Change |
|---|---|
| `AuthenticationLogInterface` | **New.** Abstraction for an authentication log (`getUser()` + read accessors). `AbstractAuthenticationLog` implements it |
| `AuthenticationLogRepositoryInterface` | `save()` now type-hints `AuthenticationLogInterface` instead of `AbstractAuthenticationLog` |
| `AuthenticationLogCreatorInterface` | `createLog()` now returns `AuthenticationLogInterface` |

## Upgrading from 1.x to 2.0

This is a major release with breaking changes. Follow this guide step by step.

### 1. User Entity: Replace `AuthenticableLogInterface` with `AuthLogUserInterface`

**Before:**

```php
use Spiriit\Bundle\AuthLogBundle\Entity\AuthenticableLogInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface, AuthenticableLogInterface
{
    public function getAuthenticationLogFactoryName(): string
    {
        return 'user';
    }

    public function getAuthenticationLogsToEmail(): string
    {
        return $this->email;
    }

    public function getAuthenticationLogsToEmailName(): string
    {
        return $this->displayName;
    }
}
```

**After:**

```php
use Spiriit\Bundle\AuthLogBundle\Entity\AuthLogUserInterface;

class User implements AuthLogUserInterface
{
    public function getAuthLogEmail(): string
    {
        return $this->email;
    }

    public function getAuthLogDisplayName(): string
    {
        return $this->displayName;
    }
}
```

`AuthLogUserInterface` already extends `UserInterface`, so you no longer need to declare it explicitly.

The `getAuthenticationLogFactoryName()` method has been removed entirely. The factory pattern has been replaced by a repository-based approach (see step 3).

### 2. Authentication Log Entity: Update `getUser()` Return Type

**Before:**

```php
use Spiriit\Bundle\AuthLogBundle\Entity\AbstractAuthenticationLog;
use Spiriit\Bundle\AuthLogBundle\Entity\AuthenticableLogInterface;

class UserAuthLog extends AbstractAuthenticationLog
{
    public function getUser(): AuthenticableLogInterface
    {
        return $this->user;
    }
}
```

**After:**

```php
use Spiriit\Bundle\AuthLogBundle\Entity\AbstractAuthenticationLog;
use Spiriit\Bundle\AuthLogBundle\Entity\AuthLogUserInterface;

class UserAuthLog extends AbstractAuthenticationLog
{
    public function getUser(): AuthLogUserInterface
    {
        return $this->user;
    }
}
```

### 3. Replace Factory with Repository + Creator

The `AuthenticationLogFactoryInterface` has been removed. Instead, implement two new interfaces in your Doctrine repository.

**Before (Factory class to delete):**

```php
use Spiriit\Bundle\AuthLogBundle\AuthenticationLogFactory\AuthenticationLogFactoryInterface;

class UserAuthLogFactory implements AuthenticationLogFactoryInterface
{
    public function supports(): string { return 'user'; }
    public function createUserReference(string $userIdentifier): UserReference { /* ... */ }
    public function isKnown(UserReference $userReference): bool { /* ... */ }
}
```

**After (Repository implementing both interfaces):**

```php
use Doctrine\ORM\EntityRepository;
use Spiriit\Bundle\AuthLogBundle\AuthenticationLog\AuthenticationLogCreatorInterface;
use Spiriit\Bundle\AuthLogBundle\Entity\AbstractAuthenticationLog;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;
use Spiriit\Bundle\AuthLogBundle\Repository\AuthenticationLogRepositoryInterface;

class UserAuthLogRepository extends EntityRepository implements
    AuthenticationLogRepositoryInterface,
    AuthenticationLogCreatorInterface
{
    public function save(AbstractAuthenticationLog $log): void
    {
        $this->getEntityManager()->persist($log);
        $this->getEntityManager()->flush();
    }

    public function findExistingLog(string $userIdentifier, UserInformation $userInformation): bool
    {
        return null !== $this->findOneBy([
            'user' => $userIdentifier,
            'ipAddress' => $userInformation->ipAddress,
        ]);
    }

    public function createLog(string $userIdentifier, UserInformation $userInformation): AbstractAuthenticationLog
    {
        // Build and return your entity
        return new UserAuthLog($userIdentifier, $userInformation);
    }
}
```

### 4. Remove Your Event Listener

The `markAsHandled()` mechanism has been removed. The bundle now handles persistence internally via `DoctrineAuthenticationLogHandler`.

**Delete your listener entirely.** If you had something like:

```php
class AuthLogListener
{
    public function onNewDevice(AuthenticationLogEvent $event): void
    {
        // persist the log...
        $event->markAsHandled();
    }
}
```

This is no longer needed. The bundle persists logs automatically through your repository.

If you still want to react to new device events (e.g. for custom logging), you can listen to `AuthenticationLogEvents::NEW_DEVICE`, but the event signature has changed:

```php
use Spiriit\Bundle\AuthLogBundle\Listener\AuthenticationLogEvent;
use Spiriit\Bundle\AuthLogBundle\Listener\AuthenticationLogEvents;

// Event methods changed:
$event->userIdentifier();   // was: getUserReference()
$event->userInformation();  // was: getUserInformation()

// Removed:
// $event->markAsHandled()  — no longer exists
// $event->isLogHandled()   — no longer exists
```

### 5. YAML Configuration

No changes required. The configuration structure remains the same:

```yaml
spiriit_auth_log:
    messenger: false
    transports:
        mailer: 'mailer'
        sender_email: 'no-reply@yourdomain.com'
        sender_name: 'Your App Security'
    location:
        provider: 'ipApi' # or 'geoip2' or null
```

### Summary of Removed Classes

| Removed Class | Replacement |
|---|---|
| `AuthenticableLogInterface` | `AuthLogUserInterface` |
| `AuthenticationLogFactoryInterface` | `AuthenticationLogRepositoryInterface` + `AuthenticationLogCreatorInterface` |
| `FetchAuthenticationLogFactory` | `DoctrineAuthenticationLogHandler` (internal) |
| `AuthenticationContextBuilder` | `LoginService` (internal, centralized orchestration) |
| `AuthenticationContext` | Removed (no replacement needed) |
| `AuthenticationEventPublisher` | `LoginService` (dispatches events directly) |
| `SpiriitAuthLogExtension` | Merged into `SpiriitAuthLogBundle` (uses `AbstractBundle`) |
| `Configuration` | Merged into `SpiriitAuthLogBundle::configure()` |
| `AuthenticationLogFactoryPass` | Replaced by `registerForAutoconfiguration()` |

### Summary of Changed Classes

| Class | Change |
|---|---|
| `AuthenticationLogEvent` | Removed `markAsHandled()`, `isLogHandled()`. New methods: `userIdentifier()`, `userInformation()` |
| `UserReference` | Now `final readonly` with public properties. Removed `setNotificationParameters()`, `getEmail()`, `getDisplayName()` |
| `LoginParameterDto` | Removed `factoryName` property |
| `LoginListener` | Now `final`. Removed `setMessageBus()` (constructor injection). Uses `AuthLogUserInterface` |
| `AbstractAuthenticationLog` | `getUser()` now returns `AuthLogUserInterface`. Fixed `getLocation()` bug |
| `SpiriitAuthLogBundle` | Extends `AbstractBundle` instead of `Bundle` |
