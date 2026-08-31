# Upgrade Guide

## Upgrading from 3.x to 4.0

Release 4.0 introduces disavowal reactions: when a user clicks "It wasn't me", the bundle now acts instead of only dispatching an event. The default reaction revokes the user's known contexts so the attacker's next login raises a fresh alert — fixing a 3.x gap where a disavowed context still counted as known and silenced future notifications.

Nothing changes if you do not use the login confirmation feature.

### 1. Implement `RevocableAuthenticationLogRepositoryInterface` (required with confirmation)

The `revoke_known_contexts` reaction is **enabled by default** as soon as `confirmation.enabled` is `true`. Your repository must implement the new interface — container compilation fails with an explicit message otherwise:

```php
use Spiriit\Bundle\AuthLogBundle\DTO\UserIdentity;
use Spiriit\Bundle\AuthLogBundle\Repository\RevocableAuthenticationLogRepositoryInterface;

class UserAuthLogRepository extends EntityRepository implements
    // ...existing interfaces
    RevocableAuthenticationLogRepositoryInterface
{
    public function revokeKnownContexts(UserIdentity $userIdentity): void
    {
        // UPDATE ... SET status = 'revoked'
        // WHERE user/userClass match AND status IN ('pending', 'acknowledged')
    }
}
```

To keep the 3.x behavior instead, set `spiriit_auth_log.confirmation.on_disavowal.revoke_known_contexts` to `false`.

### 2. Exclude revoked logs from `findExistingLog()` (required with confirmation)

Add a status filter so a revoked or disavowed log no longer makes a context "known":

```php
use Spiriit\Bundle\AuthLogBundle\Entity\AuthenticationLogStatus;

return null !== $this->findOneBy([
    'user' => $user,
    'userClass' => $userIdentity->userClass,
    'ipAddress' => $userInformation->ipAddress,
    'status' => [AuthenticationLogStatus::PENDING, AuthenticationLogStatus::ACKNOWLEDGED],
]);
```

### 3. New `AuthenticationLogStatus::REVOKED` case

The enum gains a `REVOKED = 'revoked'` case and the confirmable trait a `revoke()` method. The status column already stores strings up to 20 characters, so **no schema migration is needed** — but any exhaustive `match` on `AuthenticationLogStatus` must handle the new case.

### 4. Optional reactions

`on_disavowal.invalidate_sessions` (port: `SessionInvalidatorInterface`) and `on_disavowal.force_password_reset` (port: `PasswordResetRequesterInterface`) are opt-in. Custom reactions implement `DisavowalReactionInterface` and are picked up automatically. See the documentation for details.

## Upgrading from 2.x to 3.0

Release 3.0 introduces the optional login-confirmation feature ("It was me / It wasn't me"). As part of it, the bundle's persistence contract no longer references the concrete `AbstractAuthenticationLog` mapped superclass — it now depends on the new `AuthenticationLogInterface`. This decouples the contract from Doctrine inheritance: an integrator can implement a log without extending the mapped superclass.

3.0 also introduces `UserIdentity`, which carries the user identifier **and** the user class (FQCN). In a multi-firewall application, two accounts of different classes can share the same identifier; until now the bundle keyed everything on the identifier alone, so one account's log silenced the other's notification, and `createLog()` — which only received the identifier — could attach the log to the wrong account. The user class is now part of the uniqueness key and is persisted on the log.

Last, the persisted log is now handed to the `NEW_DEVICE` event and to the notification, so a consumer no longer has to query it back.

### 1. Log entity: constructor and `user_class` column (required)

`AbstractAuthenticationLog` now takes the `UserIdentity` as its first constructor argument and persists its `userClass` in a new `user_class` column.

**Before:**

```php
public function __construct(User $user, UserInformation $userInformation)
{
    $this->user = $user;
    parent::__construct($userInformation);
}
```

**After:**

```php
use Spiriit\Bundle\AuthLogBundle\DTO\UserIdentity;

public function __construct(User $user, UserIdentity $userIdentity, UserInformation $userInformation)
{
    $this->user = $user;
    parent::__construct($userIdentity, $userInformation);
}
```

If your log implements `AuthenticationLogInterface` **without** extending the mapped superclass, implement the new `getUserClass(): string` method yourself.

**Database migration.** `doctrine:migrations:diff` generates `ADD user_class VARCHAR(255) NOT NULL`, which fails on PostgreSQL when the table already holds rows (MySQL silently fills `''`). Backfill in three steps instead:

```sql
ALTER TABLE user_auth_log ADD user_class VARCHAR(255) DEFAULT '' NOT NULL;
UPDATE user_auth_log SET user_class = 'App\Entity\User';
ALTER TABLE user_auth_log ALTER user_class DROP DEFAULT;
```

Inside a PHP migration the backslashes must be escaped: `$this->addSql("UPDATE user_auth_log SET user_class = 'App\\\\Entity\\\\User'");`. Use one `UPDATE` per user class if a single table stores logs for several of them. The column stays `NOT NULL`: it belongs to the uniqueness key, and a nullable value would weaken it. Since that key is now `(user, user_class, ip_address)`, declare the matching index on your entity — a mapped superclass cannot do it for you:

```php
#[ORM\Entity(repositoryClass: UserAuthLogRepository::class)]
#[ORM\Index(columns: ['user_id', 'user_class', 'ip_address'])]
class UserAuthLog extends AbstractAuthenticationLog
```

### 2. Repository and creator: `UserIdentity` and `AuthenticationLogInterface` (required)

`AuthenticationLogRepositoryInterface::save()` and `AuthenticationLogCreatorInterface::createLog()` now type-hint `AuthenticationLogInterface`, and both `findExistingLog()` and `createLog()` receive a `UserIdentity` instead of a string identifier. A parameter type cannot be narrowed in an implementation, so an outdated signature is a fatal error at class load.

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

    public function findExistingLog(string $userIdentifier, UserInformation $userInformation): bool
    {
        return null !== $this->findOneBy([
            'user' => $userIdentifier,
            'ipAddress' => $userInformation->ipAddress,
        ]);
    }

    public function createLog(string $userIdentifier, UserInformation $userInformation): AbstractAuthenticationLog
    {
        $user = $this->getEntityManager()->getRepository(User::class)->findOneBy([
            'email' => $userIdentifier,
        ]);

        return new UserAuthLog($user, $userInformation);
    }
}
```

**After:**

```php
use Spiriit\Bundle\AuthLogBundle\DTO\UserIdentity;
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

    public function findExistingLog(UserIdentity $userIdentity, UserInformation $userInformation): bool
    {
        $user = $this->findUser($userIdentity);

        if (null === $user) {
            return false;
        }

        return null !== $this->findOneBy([
            'user' => $user,
            'userClass' => $userIdentity->userClass,
            'ipAddress' => $userInformation->ipAddress,
        ]);
    }

    public function createLog(UserIdentity $userIdentity, UserInformation $userInformation): AuthenticationLogInterface
    {
        $user = $this->findUser($userIdentity);

        if (null === $user) {
            throw new \RuntimeException(sprintf('No user found for identifier "%s".', $userIdentity->userIdentifier));
        }

        return new UserAuthLog($user, $userIdentity, $userInformation);
    }

    private function findUser(UserIdentity $userIdentity): ?User
    {
        return $this->getEntityManager()->getRepository(User::class)->findOneBy([
            'email' => $userIdentity->userIdentifier,
        ]);
    }
}
```

> **Note:** the 2.x example passed the identifier string as the `user` criterion, which compared a relation to an email. Load the user first, as above.

### 3. Custom notification: `send()` now takes a `NewDeviceNotification`

If you implemented `NotificationInterface` for a custom transport (Slack, SMS…), `send()` no longer receives a list of arguments but a single `NewDeviceNotification` carrying the user reference, the user information, the **persisted log** and the confirmation links (`null` unless the confirmation feature is enabled).

**Before:**

```php
use Spiriit\Bundle\AuthLogBundle\Notification\NotificationInterface;

final class SlackNotification implements NotificationInterface
{
    public function send(UserInformation $userInformation, UserReference $userReference, ?ConfirmationLinks $confirmationLinks = null): void
    {
        // ...
    }
}
```

**After:**

```php
use Spiriit\Bundle\AuthLogBundle\Notification\NewDeviceNotification;
use Spiriit\Bundle\AuthLogBundle\Notification\NotificationInterface;

final class SlackNotification implements NotificationInterface
{
    public function send(NewDeviceNotification $notification): void
    {
        $notification->userReference;      // email, display name, user identity
        $notification->userInformation;    // IP, user agent, location
        $notification->authenticationLog;  // the log that was just persisted
        $notification->confirmationLinks;  // null unless confirmation is enabled
    }
}
```

### 4. Custom handler (advanced): `UserIdentity` and returned log

Only relevant if you replaced the bundle's default `DoctrineAuthenticationLogHandler` with your own `AuthenticationLogHandlerInterface` implementation.

```php
// Before
public function isKnown(string $userIdentifier, UserInformation $userInformation): bool
public function handle(string $userIdentifier, UserInformation $userInformation): void

// After
public function isKnown(UserIdentity $userIdentity, UserInformation $userInformation): bool
public function handle(UserIdentity $userIdentity, UserInformation $userInformation): AuthenticationLogInterface
```

`handle()` must return the log it saved, so the caller can pass it to the event and to the notification.

### 5. `NEW_DEVICE` listeners: nothing to change

`AuthenticationLogEvent::userIdentifier()` is still there, so existing listeners keep working. Two accessors are new:

```php
public function __invoke(AuthenticationLogEvent $event): void
{
    $event->userIdentifier();            // unchanged
    $event->userIdentity()->userClass;   // new: the user FQCN
    $event->authenticationLog();         // new: the persisted log
}
```

Only code that **constructs** the event (typically your tests) has to pass the three arguments.

### 6. Messenger: drain the queue before deploying

`LoginParameterDto` now carries a `UserIdentity` object instead of a `userIdentifier` string, so the payload shape of `AuthLoginMessage` changes. A 2.x message decoded by 3.0 code fails (`Cannot create dynamic property …::$userIdentifier`, surfaced as a `MessageDecodingFailedException`) and is rejected — those logins would be lost.

If you route `AuthLoginMessage` to an asynchronous transport, drain the queue first:

```bash
bin/console messenger:stop-workers   # let the running workers finish
# consume until the transport is empty, then deploy and restart the workers
```

With the `serializer` transport the DTO is denormalized through its constructor, so the nested `UserIdentity` requires `symfony/property-info` to be wired. Draining the queue avoids the question entirely — do it whichever transport you use.

### 7. Twig template

The email context gains `authenticationLog` (the persisted log) and `userReference`. The `authenticableLog` variable still points to the same `UserReference` object, but it is deprecated in favour of `userReference` and will be removed in 4.0. If your overridden template read `authenticableLog.userIdentifier`, use `userReference.userIdentity.userIdentifier` instead.

### Summary of Changed Interfaces

| Interface | Change |
|---|---|
| `AuthenticationLogInterface` | **New.** Abstraction for an authentication log (`getUser()` + read accessors), plus `getUserClass()`. `AbstractAuthenticationLog` implements it |
| `AuthenticationLogRepositoryInterface` | `save()` type-hints `AuthenticationLogInterface`; `findExistingLog()` takes a `UserIdentity` |
| `AuthenticationLogCreatorInterface` | `createLog()` takes a `UserIdentity` and returns `AuthenticationLogInterface` |
| `NotificationInterface` | `send()` takes a single `NewDeviceNotification` argument |
| `AuthenticationLogHandlerInterface` | `isKnown()` / `handle()` take a `UserIdentity`; `handle()` returns `AuthenticationLogInterface` (was `void`) — advanced, only if you replaced the default handler |

### Summary of New Classes

| Class | Purpose |
|---|---|
| `Spiriit\Bundle\AuthLogBundle\DTO\UserIdentity` | User identifier + user class (FQCN). `UserIdentity::fromUser()` builds it from a `UserInterface`, resolving Doctrine proxies |
| `Spiriit\Bundle\AuthLogBundle\Notification\NewDeviceNotification` | Everything a notification needs: user reference, user information, persisted log, confirmation links |

### Summary of Changed Classes

| Class | Change |
|---|---|
| `AbstractAuthenticationLog` | Constructor takes a `UserIdentity` first; new `user_class` column and `getUserClass()` |
| `LoginParameterDto` | `userIdentifier` (string) replaced by `userIdentity` (`UserIdentity`) |
| `UserReference` | `userIdentifier` (string) replaced by `userIdentity` (`UserIdentity`) |
| `AuthenticationLogEvent` | Constructor takes `(UserIdentity, UserInformation, AuthenticationLogInterface)`; new `userIdentity()` and `authenticationLog()` accessors; `userIdentifier()` kept |
| `MailerNotification` | Template context gains `authenticationLog` and `userReference` (`authenticableLog` deprecated) |

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
