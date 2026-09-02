---
title: "Auth Log Bundle 4.0: “It wasn't me” now does something"
date: 2026-09-02
description: "Disavowing a login now revokes the attacker's known contexts, can end every session and force a password reset — automatically, the moment the user reports it."
author: Spiriit
---

# Auth Log Bundle 4.0: “It wasn't me” now does something

**Starting with 4.0, disavowing a login is no longer just an event you have to handle. The bundle revokes the user's known contexts on the spot, and can end every session and force a password reset — with your own reactions running alongside.**

## A warning that only warns is half a security control

Picture the alert landing at 2am. Unknown device, unknown city. The user taps **“It wasn't me”**, and then?

The attacker's session is still open. Their IP address is now a *known context* — recorded, trusted, silent. Their next sign-in raises no alert at all. The user did everything right and nothing happened.

4.0 closes that window.

## Three reactions, one line of config each

```yaml
spiriit_auth_log:
    confirmation:
        enabled: true
        on_disavowal:
            revoke_known_contexts: true    # default
            invalidate_sessions: false     # opt-in
            force_password_reset: false    # opt-in
```

<div class="reaction-cards">
  <div class="reaction-card">
    <div class="reaction-card-head">
      <code class="reaction-card-name">revoke_known_contexts</code>
      <span class="reaction-card-badge is-default">On by default</span>
    </div>
    <p class="reaction-card-text">Untrusts every known context, so the attacker's next login raises a fresh alert.</p>
    <p class="reaction-card-port">Port to implement <code>RevocableAuthenticationLogRepositoryInterface</code></p>
  </div>
  <div class="reaction-card">
    <div class="reaction-card-head">
      <code class="reaction-card-name">invalidate_sessions</code>
      <span class="reaction-card-badge">Opt-in</span>
    </div>
    <p class="reaction-card-text">Signs the account out everywhere.</p>
    <p class="reaction-card-port">Port to implement <code>SessionInvalidatorInterface</code></p>
  </div>
  <div class="reaction-card">
    <div class="reaction-card-head">
      <code class="reaction-card-name">force_password_reset</code>
      <span class="reaction-card-badge">Opt-in</span>
    </div>
    <p class="reaction-card-text">Sends the user through your account recovery flow.</p>
    <p class="reaction-card-port">Port to implement <code>PasswordResetRequesterInterface</code></p>
  </div>
</div>

Revocation is on by default because it repairs the flaw that makes a disavowal dangerous in the first place. The other two are opt-in: they touch your session storage and your recovery flow, and no bundle can guess those.

Each reaction delegates its sensitive half to a small interface — a *port* — that your application implements. Write the class, and autoconfiguration wires it.

Enable a reaction whose port has no implementation and **container compilation fails**, naming the interface to write. A security measure that silently does nothing is worse than a build error.

## Bring your own reaction

Notify your SOC, revoke API tokens, quarantine the account, page someone. One class:

```php
final class NotifySecurityTeam implements DisavowalReactionInterface
{
    public function react(DisavowedLogin $disavowedLogin): void
    {
        // $disavowedLogin->user, ->userIdentity, ->authenticationLog
    }
}
```

It runs with the built-ins, before `LOGIN_DISAVOWED` is dispatched.

## Your logs now remember who they were written for

A log is a journal: it must remember, not ask again. `AbstractAuthenticationLog` records the user identifier it was written with, next to the user class.

Two things follow. Checking whether a context is known no longer reloads the User entity — **one query less on every single login**. And a log keeps telling the truth about who signed in, even after the account row changes underneath it.

This one needs a schema migration and a back-fill. The [upgrade guide](/upgrade/4.0) covers both, including logs pointing at deleted users.

## Get started

```bash
composer require spiriitlabs/auth-log-bundle:^4.0
```

PHP 8.2+, Symfony 6.4, 7.4 and 8.0, Doctrine ORM 3 or 4.

New here? Start with [disavowal reactions](/features/disavowal-reactions). Already running the bundle? The [upgrade guide](/upgrade/4.0) is a ten-minute read and tells you exactly what your repository owes the new version.
