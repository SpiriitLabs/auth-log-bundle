# Login confirmation

This optional feature adds two **signed links** to the notification email so the user can confirm the login was theirs — or report that it wasn't — from any device, without being logged in.

- The links are signed with your application secret and carry an expiration.
- Clicking a link opens an intermediate page with a confirmation button that **POSTs** the action. This prevents email link scanners (Outlook Safe Links, etc.) from triggering the action just by following the URL.
- A link is **single-use**: once the login is acknowledged or disavowed, replaying it shows an "already handled" page.
- If the login no longer exists (e.g. it was pruned by your retention policy), the page reports that the link is no longer valid, with a `404` status — distinct from the "already handled" page.

The bundle only records the outcome and **dispatches an event** — your application decides what to do next (e.g. force a password change or log out other sessions on a disavow).

## 1. Enable the feature

```yaml
spiriit_auth_log:
    confirmation:
        enabled: true
        token_ttl: '3 days'   # relative expression: "12 hours", "1 week"...
```

Generating absolute URLs from a Messenger worker (no request context) requires a default URI:

```yaml
# config/packages/routing.yaml
framework:
    router:
        default_uri: 'https://your-domain.com'
```

## 2. Make your log entity confirmable

Add the trait and interface to the entity that already extends `AbstractAuthenticationLog`, then generate a migration for the new columns (`confirmation_token`, `status`, `responded_at`):

```php
use Spiriit\Bundle\AuthLogBundle\Entity\AbstractAuthenticationLog;
use Spiriit\Bundle\AuthLogBundle\Entity\ConfirmableAuthenticationLogInterface;
use Spiriit\Bundle\AuthLogBundle\Entity\ConfirmableAuthenticationLogTrait;

#[ORM\Entity(repositoryClass: UserAuthLogRepository::class)]
class UserAuthLog extends AbstractAuthenticationLog implements ConfirmableAuthenticationLogInterface
{
    use ConfirmableAuthenticationLogTrait;

    // ... your existing fields
}
```

::: info Opt-in
If you don't enable the feature, nothing changes: the trait and columns are opt-in, so existing integrators don't need a migration.
:::

## 3. Implement the confirmable repository

```php
use Spiriit\Bundle\AuthLogBundle\Entity\ConfirmableAuthenticationLogInterface;
use Spiriit\Bundle\AuthLogBundle\Repository\ConfirmableAuthenticationLogRepositoryInterface;

class UserAuthLogRepository extends EntityRepository implements
    AuthenticationLogRepositoryInterface,
    AuthenticationLogCreatorInterface,
    ConfirmableAuthenticationLogRepositoryInterface
{
    // ... existing methods

    public function findOneByConfirmationToken(string $confirmationToken): ?ConfirmableAuthenticationLogInterface
    {
        return $this->findOneBy(['confirmationToken' => $confirmationToken]);
    }
}
```

## 4. Expose the confirmation route

You keep full control over the route. Pick one of the two approaches.

### a. Import the default route

The simplest option. You may add a `prefix`, `host` or `condition` — the generated links follow it automatically:

```yaml
# config/routes/spiriit_auth_log.yaml
spiriit_auth_log:
    resource: '@SpiriitAuthLogBundle/config/routes.php'
    prefix: /security   # optional
```

### b. Declare your own route

Point the bundle at it — use this when you want your own path or format:

```yaml
# config/routes.yaml
my_login_confirmation:
    path: /account/logins/{action}/{token}
    controller: spiriit_auth_log.confirmation_controller
    methods: [GET, POST]
    requirements: { action: 'acknowledge|disavow' }
```

```yaml
spiriit_auth_log:
    confirmation:
        enabled: true
        route_name: my_login_confirmation   # defaults to "spiriit_auth_log_confirm"
```

## 5. React to the user's response

The bundle dispatches `AuthenticationLogEvents::LOGIN_ACKNOWLEDGED` or `AuthenticationLogEvents::LOGIN_DISAVOWED`, carrying the confirmed log:

```php
use Spiriit\Bundle\AuthLogBundle\Listener\AuthenticationLogConfirmationEvent;
use Spiriit\Bundle\AuthLogBundle\Listener\AuthenticationLogEvents;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: AuthenticationLogEvents::LOGIN_DISAVOWED)]
final class LoginDisavowedListener
{
    public function __invoke(AuthenticationLogConfirmationEvent $event): void
    {
        $log = $event->authenticationLog();
        $user = $log->getUser();

        // e.g. force a password reset, invalidate sessions, notify security...
    }
}
```

## Overriding the confirmation pages

You can override the confirmation pages the same way as the [email template](/advanced/email-template), under `templates/bundles/SpiriitAuthLogBundle/confirmation/`.
