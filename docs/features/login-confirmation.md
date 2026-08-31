# Login confirmation

This optional feature adds two **signed links** to the notification email, so the user can confirm the login was theirs — or report that it wasn't — from any device, without being logged in.

- **Signed and expiring**, with your application secret.
- **Safe from link scanners**: clicking opens an intermediate page whose button issues a `POST`, so Outlook Safe Links and friends cannot answer on the user's behalf.
- **Single-use**: replaying a handled link shows an "already handled" page. If the log itself no longer exists — pruned by your retention policy — the page reports an invalid link with a `404` status.

The bundle records the outcome, executes the configured [disavowal reactions](/features/disavowal-reactions), then **dispatches an event** so your application can go further.

## 1. Enable the feature

```yaml
spiriit_auth_log:
    confirmation:
        enabled: true
        token_ttl: '3 days'   # relative expression: "12 hours", "1 week"...
```

Absolute URLs generated from a Messenger worker have no request context, so set a default URI:

```yaml
# config/packages/routing.yaml
framework:
    router:
        default_uri: 'https://your-domain.com'
```

## 2. Make your log entity confirmable

Add the trait and interface, then generate a migration for the new columns (`confirmation_token`, `status`, `responded_at`):

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
Leave the feature disabled and nothing changes: no trait, no columns, no migration.
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
    public function findOneByConfirmationToken(string $confirmationToken): ?ConfirmableAuthenticationLogInterface
    {
        return $this->findOneBy(['confirmationToken' => $confirmationToken]);
    }
}
```

## 4. Expose the confirmation route

**Import the bundle route.** A `prefix`, `host` or `condition` is allowed — the generated links follow it:

```yaml
# config/routes/spiriit_auth_log.yaml
spiriit_auth_log:
    resource: '@SpiriitAuthLogBundle/config/routes.php'
    prefix: /security   # optional
```

**Or declare your own**, for a custom path or format, and point the bundle at it:

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

Once the [disavowal reactions](/features/disavowal-reactions) have run, the bundle dispatches `LOGIN_ACKNOWLEDGED` or `LOGIN_DISAVOWED` with the confirmed log, for anything they do not cover:

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

        // notify the security team, feed a SIEM...
    }
}
```

## Overriding the confirmation pages

Same mechanism as the [email template](/advanced/email-template), under `templates/bundles/SpiriitAuthLogBundle/confirmation/`.
