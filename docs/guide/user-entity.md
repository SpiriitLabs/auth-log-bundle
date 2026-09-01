---
description: "Implement AuthLogUserInterface on your User entity to tell the bundle where to send the alert and how to address the user."
---

# User entity

Implement `AuthLogUserInterface` on your User entity. It tells the bundle where to send the alert and how to address the user.

`AuthLogUserInterface` extends `UserInterface`, so you no longer need to declare it explicitly.

```php
use Spiriit\Bundle\AuthLogBundle\Entity\AuthLogUserInterface;

class User implements AuthLogUserInterface
{
    // ... your existing User fields

    public function getAuthLogEmail(): string
    {
        return $this->email;
    }

    public function getAuthLogDisplayName(): string
    {
        return $this->name;
    }
}
```
