# Custom notification

By default, the bundle sends email alerts via Symfony Mailer. To use a different transport (Slack, SMS, etc.), implement `NotificationInterface` and register it as a service:

```php
use Spiriit\Bundle\AuthLogBundle\Confirmation\ConfirmationLinks;
use Spiriit\Bundle\AuthLogBundle\DTO\UserReference;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;
use Spiriit\Bundle\AuthLogBundle\Notification\NotificationInterface;

final class SlackNotification implements NotificationInterface
{
    public function send(UserInformation $userInformation, UserReference $userReference, ?ConfirmationLinks $confirmationLinks = null): void
    {
        // send a Slack message, SMS, etc.
        // $confirmationLinks is null unless the confirmation feature is enabled
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
