# Repository

Your repository implements two interfaces:

- `AuthenticationLogRepositoryInterface` — tell whether a log already exists, and save new ones
- `AuthenticationLogCreatorInterface` — build the log entity

Both receive a `UserIdentity`, a `final readonly` DTO carrying `userIdentifier` (what `UserInterface::getUserIdentifier()` returns) and `userClass` (the FQCN, resolved through Doctrine proxies). Keep the class in the lookup: two accounts of different classes may share the same identifier.

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
        return null !== $this->findOneBy([
            'userIdentifier' => $userIdentity->userIdentifier,
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
The bundle listens to `LoginSuccessEvent`, checks whether the context is known, persists the log and sends the notification on its own.
:::

## Defining a "known context"

`findExistingLog()` is what defines a known context for your application. The example above matches the identity and the IP address — widen it to the user agent or the location for a stricter definition.

The lookup reads the log's own `user_identifier` and `user_class` columns, so it costs a single query: only `createLog()` needs the User entity itself.

With [login confirmation](/features/login-confirmation) enabled, also exclude the logs the user disavowed and those revoked by the [disavowal reactions](/features/disavowal-reactions) — otherwise a reported context would still count as known and silence future alerts:

```php
use Spiriit\Bundle\AuthLogBundle\Entity\AuthenticationLogStatus;

return null !== $this->findOneBy([
    'userIdentifier' => $userIdentity->userIdentifier,
    'userClass' => $userIdentity->userClass,
    'ipAddress' => $userInformation->ipAddress,
    'status' => [AuthenticationLogStatus::PENDING, AuthenticationLogStatus::ACKNOWLEDGED],
]);
```
