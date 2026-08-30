# Custom notification

By default, the bundle sends email alerts via Symfony Mailer. To use a different transport (Slack, SMS, etc.), implement `NotificationInterface` and register it as a service:

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
        $notification->confirmationLinks;  // null unless the confirmation feature is enabled
    }
}
```

Then point the `mailer` transport to your service ID:

```yaml
spiriit_auth_log:
    transports:
        mailer: 'App\Notification\SlackNotification'
        sender_email: 'no-reply@yourdomain.com'
        sender_name: 'Security'
```

## NewDeviceNotification

Everything the bundle knows about the login is carried by a single `final readonly` object:

| Property | Type | Description |
|---|---|---|
| `userReference` | `UserReference` | email, display name and `userIdentity` |
| `userInformation` | `UserInformation` | IP address, user agent, login timestamp, location |
| `authenticationLog` | `AuthenticationLogInterface` | the log that was just persisted |
| `confirmationLinks` | `?ConfirmationLinks` | `acknowledgeUrl` / `disavowUrl`, `null` unless [confirmation](/features/login-confirmation) is enabled |
