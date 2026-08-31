# Log entity

Extend `AbstractAuthenticationLog` and add a relation to your User entity:

```php
use Doctrine\ORM\Mapping as ORM;
use Spiriit\Bundle\AuthLogBundle\DTO\UserIdentity;
use Spiriit\Bundle\AuthLogBundle\Entity\AbstractAuthenticationLog;
use Spiriit\Bundle\AuthLogBundle\Entity\AuthLogUserInterface;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;

#[ORM\Entity(repositoryClass: UserAuthLogRepository::class)]
#[ORM\Index(columns: ['user_id', 'user_class', 'ip_address'])]
class UserAuthLog extends AbstractAuthenticationLog
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private User $user;

    public function __construct(User $user, UserIdentity $userIdentity, UserInformation $userInformation)
    {
        $this->user = $user;
        parent::__construct($userIdentity, $userInformation);
    }

    public function getUser(): AuthLogUserInterface
    {
        return $this->user;
    }
}
```

`AbstractAuthenticationLog` is a Doctrine mapped superclass: it brings the IP address, user agent, location, login timestamp and `user_class` columns. You only declare the identifier and the relation to your own User entity.

The index matches the lookup performed on every login — see [Repository](/guide/repository).

Generate a migration once the entity is in place:

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```

## Several user classes

The entity above stores logs for a single `User` class. With several firewalls and distinct user classes, the simplest setup is one log entity — one table — per class.

Sharing one table also works: declare one nullable relation per class, set the right one in the constructor, and let `getUser()` return whichever is set.

```php
#[ORM\ManyToOne(targetEntity: User::class)]
private ?User $user = null;

#[ORM\ManyToOne(targetEntity: Admin::class)]
private ?Admin $admin = null;

public function __construct(User|Admin $user, UserIdentity $userIdentity, UserInformation $userInformation)
{
    if ($user instanceof User) {
        $this->user = $user;
    } else {
        $this->admin = $user;
    }

    parent::__construct($userIdentity, $userInformation);
}

public function getUser(): AuthLogUserInterface
{
    return $this->user ?? $this->admin ?? throw new \LogicException('Log has no user.');
}
```

The [Repository](/guide/repository) must follow: resolve the user repository from the class carried by `UserIdentity` instead of hardcoding one.

```php
private function findUser(UserIdentity $userIdentity): User|Admin|null
{
    return $this->getEntityManager()
        ->getRepository($userIdentity->userClass)
        ->findOneBy(['email' => $userIdentity->userIdentifier]);
}
```

The inherited `user_class` column records which class each log belongs to — keep it in the `findExistingLog()` lookup, as the repository example already does.
