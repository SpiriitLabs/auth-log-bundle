# Custom notification

The bundle sends email alerts through Symfony Mailer. For another transport — Slack, SMS… — implement `NotificationInterface` and register it as a service:

```php
use Spiriit\Bundle\AuthLogBundle\Notification\NewDeviceNotification;
use Spiriit\Bundle\AuthLogBundle\Notification\NotificationInterface;

final class SlackNotification implements NotificationInterface
{
    public function send(NewDeviceNotification $notification): void
    {
        // ...
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

Everything the bundle knows about the login, in a single `final readonly` object:

| Property | Type | Description |
|---|---|---|
| `userReference` | `UserReference` | email, display name and `userIdentity` |
| `userInformation` | `UserInformation` | IP address, user agent, login timestamp, location |
| `authenticationLog` | `AuthenticationLogInterface` | the log that was just persisted |
| `confirmationLinks` | `?ConfirmationLinks` | `acknowledgeUrl` / `disavowUrl`, `null` unless [confirmation](/features/login-confirmation) is enabled |
