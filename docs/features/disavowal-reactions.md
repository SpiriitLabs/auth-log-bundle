---
description: "Act when a user reports a login was not theirs: revoke known contexts, invalidate sessions, force a password reset, or add your own reaction."
---

# Disavowal reactions

When a user clicks **"It wasn't me"**, recording the incident is not enough — something should *happen*. The bundle executes a set of **reactions** at that moment, each enabled by configuration, before dispatching `LOGIN_DISAVOWED`.

Requires the [login confirmation](/features/login-confirmation) feature.

## Configuration

```yaml
spiriit_auth_log:
    confirmation:
        enabled: true
        on_disavowal:
            revoke_known_contexts: true    # default
            invalidate_sessions: false     # opt-in
            force_password_reset: false    # opt-in
```

Each built-in reaction delegates the sensitive part to a small interface — a *port* — that your application implements. Implementing it is enough: autoconfiguration tags it and the bundle wires it. If a reaction is enabled and its port has no implementation, **container compilation fails** naming the interface to implement; a protection that silently does nothing would be worse than an error.

## `revoke_known_contexts` (enabled by default)

A disavowed login means the attacker's IP/device is currently a *known context* — without this reaction, their next login would not even raise an alert. Revoking the user's known contexts makes **any next login trigger a fresh notification**.

Your repository implements `RevocableAuthenticationLogRepositoryInterface`:

```php
use Spiriit\Bundle\AuthLogBundle\DTO\UserIdentity;
use Spiriit\Bundle\AuthLogBundle\Entity\AuthenticationLogStatus;
use Spiriit\Bundle\AuthLogBundle\Repository\RevocableAuthenticationLogRepositoryInterface;

class UserAuthLogRepository extends EntityRepository implements RevocableAuthenticationLogRepositoryInterface
{
    public function revokeKnownContexts(UserIdentity $userIdentity): void
    {
        $this->getEntityManager()->createQuery(
            'UPDATE App\Entity\UserAuthLog l
             SET l.status = :revoked
             WHERE l.userIdentifier = :userIdentifier AND l.userClass = :userClass AND l.status IN (:revocable)'
        )
            ->setParameter('revoked', AuthenticationLogStatus::REVOKED->value)
            ->setParameter('userIdentifier', $userIdentity->userIdentifier)
            ->setParameter('userClass', $userIdentity->userClass)
            ->setParameter('revocable', [AuthenticationLogStatus::PENDING->value, AuthenticationLogStatus::ACKNOWLEDGED->value])
            ->getQuery()
            ->execute();
    }
}
```

::: warning Update your `findExistingLog()` query
Revocation is only effective if your definition of a known context excludes revoked and disavowed logs. Add the status filter shown in [Defining a known context](/guide/repository#defining-a-known-context).
:::

The trait exposes `revoke()` for a single log: `PENDING` or `ACKNOWLEDGED` become `REVOKED` — making the confirmation link inert — and a `DISAVOWED` log is never downgraded.

## `invalidate_sessions` (opt-in)

Logs the user out everywhere. Symfony has no session registry, so implement `SessionInvalidatorInterface` against your own session storage (PDO, Redis, remember-me tokens…):

```php
use Spiriit\Bundle\AuthLogBundle\Disavowal\SessionInvalidatorInterface;
use Spiriit\Bundle\AuthLogBundle\Entity\AuthLogUserInterface;

final class PdoSessionInvalidator implements SessionInvalidatorInterface
{
    public function invalidateSessions(AuthLogUserInterface $user): void
    {
        // delete the user's rows from the sessions table,
        // clear their remember-me tokens...
    }
}
```

## `force_password_reset` (opt-in)

Triggers your own password-reset flow through `PasswordResetRequesterInterface`:

```php
use Spiriit\Bundle\AuthLogBundle\Disavowal\PasswordResetRequesterInterface;
use Spiriit\Bundle\AuthLogBundle\Entity\AuthLogUserInterface;

final class ResetPasswordRequester implements PasswordResetRequesterInterface
{
    public function requestPasswordReset(AuthLogUserInterface $user): void
    {
        // expire the password, or send a reset link
        // through symfonycasts/reset-password-bundle
    }
}
```

## Custom reactions

Any service implementing `DisavowalReactionInterface` is picked up automatically, with no configuration:

```php
use Spiriit\Bundle\AuthLogBundle\Disavowal\DisavowalReactionInterface;
use Spiriit\Bundle\AuthLogBundle\Disavowal\DisavowedLogin;

final class NotifySecurityTeamReaction implements DisavowalReactionInterface
{
    public function react(DisavowedLogin $disavowedLogin): void
    {
        $log = $disavowedLogin->authenticationLog;   // the disavowed log
        $identity = $disavowedLogin->userIdentity;   // identifier + user class, as recorded on the log
        $user = $disavowedLogin->user;               // the account to act on, resolved once by the executor

        // page the on-call, open a ticket...
    }
}
```

## Execution model

- Reactions run **synchronously** in the confirmation controller, after the log is saved and **before** `LOGIN_DISAVOWED` is dispatched: when your listeners run, the protections are already in place. An event you dispatch manually triggers no reaction.
- Order follows the `priority` attribute of the `spiriit_auth_log.disavowal_reaction` tag, higher first: `revoke_known_contexts` (100), `invalidate_sessions` (50), `force_password_reset` (0). Custom reactions default to 0 — set a priority through `#[AutoconfigureTag]`.
- The executor resolves the user **once**, before the first reaction. If the account cannot be resolved — deleted row, broken relation — no reaction runs and the failure is logged: a partial protection is worse than none.
- Each reaction is isolated: if one throws, the failure is logged and the **remaining reactions still run**.
