# Repository

Your repository must implement two interfaces:

- `AuthenticationLogRepositoryInterface` — check if a log already exists and save new logs
- `AuthenticationLogCreatorInterface` — build the log entity from a user identifier and user information

```php
use Doctrine\ORM\EntityRepository;
use Spiriit\Bundle\AuthLogBundle\AuthenticationLog\AuthenticationLogCreatorInterface;
use Spiriit\Bundle\AuthLogBundle\Entity\AuthenticationLogInterface;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;
use Spiriit\Bundle\AuthLogBundle\Repository\AuthenticationLogRepositoryInterface;

class UserAuthLogRepository extends EntityRepository implements
    AuthenticationLogRepositoryInterface,
    AuthenticationLogCreatorInterface
{
    public function save(AuthenticationLogInterface $log): void
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

    public function createLog(string $userIdentifier, UserInformation $userInformation): AuthenticationLogInterface
    {
        $user = $this->getEntityManager()->getRepository(User::class)->findOneBy([
            'email' => $userIdentifier,
        ]);

        return new UserAuthLog($user, $userInformation);
    }
}
```

::: tip That's it!
The bundle automatically listens to `LoginSuccessEvent`, checks if the login context is known, persists the log, and sends a notification email when a new context is detected.
:::

`findExistingLog()` is what defines "a known context" for your application. The example above matches on the IP address only — widen it to the user agent or the location if you want a stricter definition.
