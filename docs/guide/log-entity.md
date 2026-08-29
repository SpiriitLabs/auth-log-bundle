# Log entity

Extend `AbstractAuthenticationLog` and add a relation to your User entity:

```php
use Doctrine\ORM\Mapping as ORM;
use Spiriit\Bundle\AuthLogBundle\Entity\AbstractAuthenticationLog;
use Spiriit\Bundle\AuthLogBundle\Entity\AuthLogUserInterface;
use Spiriit\Bundle\AuthLogBundle\FetchUserInformation\UserInformation;

#[ORM\Entity(repositoryClass: UserAuthLogRepository::class)]
class UserAuthLog extends AbstractAuthenticationLog
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private User $user;

    public function __construct(User $user, UserInformation $userInformation)
    {
        $this->user = $user;
        parent::__construct($userInformation);
    }

    public function getUser(): AuthLogUserInterface
    {
        return $this->user;
    }
}
```

`AbstractAuthenticationLog` is a Doctrine mapped superclass: it brings the IP address, user agent, location and login timestamp columns. You only declare the identifier and the relation to your own User entity.

Generate a migration once the entity is in place:

```bash
php bin/console doctrine:migrations:diff
php bin/console doctrine:migrations:migrate
```
