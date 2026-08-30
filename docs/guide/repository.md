# Repository

Your repository must implement two interfaces:

- `AuthenticationLogRepositoryInterface` — check if a log already exists and save new logs
- `AuthenticationLogCreatorInterface` — build the log entity from a user identity and user information

A [`UserIdentity`](#useridentity) carries both the user identifier and the user class (FQCN). Keep the class in the lookup: two accounts of different classes may share the same identifier.

```php
use Doctrine\ORM\EntityRepository;
use Spiriit\Bundle\AuthLogBundle\AuthenticationLog\AuthenticationLogCreatorInterface;
use Spiriit\Bundle\AuthLogBundle\DTO\UserIdentity;
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

    public function findExistingLog(UserIdentity $userIdentity, UserInformation $userInformation): bool
    {
        $user = $this->findUser($userIdentity);

        if (null === $user) {
            return false;
        }

        return null !== $this->findOneBy([
            'user' => $user,
            'userClass' => $userIdentity->userClass,
            'ipAddress' => $userInformation->ipAddress,
        ]);
    }

    public function createLog(UserIdentity $userIdentity, UserInformation $userInformation): AuthenticationLogInterface
    {
        $user = $this->findUser($userIdentity);

        if (null === $user) {
            throw new \RuntimeException(sprintf('No user found for identifier "%s".', $userIdentity->userIdentifier));
        }

        return new UserAuthLog($user, $userIdentity, $userInformation);
    }

    private function findUser(UserIdentity $userIdentity): ?User
    {
        return $this->getEntityManager()->getRepository(User::class)->findOneBy([
            'email' => $userIdentity->userIdentifier,
        ]);
    }
}
```

::: tip That's it!
The bundle automatically listens to `LoginSuccessEvent`, checks if the login context is known, persists the log, and sends a notification email when a new context is detected.
:::

## UserIdentity

`UserIdentity` is a `final readonly` DTO with two public properties:

| Property | Type | Description |
|---|---|---|
| `userIdentifier` | `string` | what `UserInterface::getUserIdentifier()` returns |
| `userClass` | `string` | the user's FQCN, resolved through Doctrine proxies |

## Defining a "known context"

`findExistingLog()` is what defines a known context for your application. The example above matches on the identity and the IP address — widen it to the user agent or the location if you want a stricter definition.
