# Events

When a new device/context is detected, the bundle dispatches a `AuthenticationLogEvents::NEW_DEVICE` event. You can listen to it for custom processing (logging, analytics, etc.):

```php
use Spiriit\Bundle\AuthLogBundle\Listener\AuthenticationLogEvent;
use Spiriit\Bundle\AuthLogBundle\Listener\AuthenticationLogEvents;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener(event: AuthenticationLogEvents::NEW_DEVICE)]
final class NewDeviceListener
{
    public function __invoke(AuthenticationLogEvent $event): void
    {
        $userIdentifier = $event->userIdentifier();
        $userClass = $event->userIdentity()->userClass;
        $userInformation = $event->userInformation();
        $authenticationLog = $event->authenticationLog();

        // your custom logic here
    }
}
```

::: info
Persistence and notification are handled automatically by the bundle. You do **not** need to listen to this event for the bundle to work.
:::

## Available events

| Constant | Dispatched when | Event class |
|---|---|---|
| `AuthenticationLogEvents::NEW_DEVICE` | a login from an unknown context is detected | `AuthenticationLogEvent` |
| `AuthenticationLogEvents::LOGIN_ACKNOWLEDGED` | the user confirms the login was theirs | `AuthenticationLogConfirmationEvent` |
| `AuthenticationLogEvents::LOGIN_DISAVOWED` | the user reports the login was not theirs | `AuthenticationLogConfirmationEvent` |

The last two require the [login confirmation](/features/login-confirmation) feature.
