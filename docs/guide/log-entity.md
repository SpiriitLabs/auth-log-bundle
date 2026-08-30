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

> **Several user classes?** The example above relates the log to a single `User` entity, so it can only store logs for that class. If several firewalls with distinct user classes share one log table, declare one nullable relation per class and have `getUser()` return whichever is set (`getUser()` must always return an `AuthLogUserInterface`). The `user_class` column then tells them apart in `findExistingLog()` — that is what it is for. Keeping one log table per user class works too, and is simpler.

Generate a migration once the entity is in place:

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```
