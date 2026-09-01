---
description: "Listen to the NEW_DEVICE event to run your own processing — logging, analytics, alerting — when an unknown login context is detected."
---

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

All constants live on `AuthenticationLogEvents`.

| Constant | Event class | Dispatched when |
|---|---|---|
| `NEW_DEVICE` | `AuthenticationLogEvent` | a login from an unknown context is detected |
| `LOGIN_ACKNOWLEDGED` | `AuthenticationLogConfirmationEvent` | the user confirms the login was theirs |
| `LOGIN_DISAVOWED` | `AuthenticationLogConfirmationEvent` | the user reports the login was not theirs |

The last two require the [login confirmation](/features/login-confirmation) feature.

`LOGIN_DISAVOWED` is dispatched **after** the configured [disavowal reactions](/features/disavowal-reactions) have run — when your listener receives it, the built-in protections are already in place.
