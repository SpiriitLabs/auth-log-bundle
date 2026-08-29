# Installation

Install the bundle with Composer:

```bash
composer require spiriitlabs/auth-log-bundle
```

If you use Symfony Flex, the bundle is registered automatically. Otherwise, enable it manually:

```php
// config/bundles.php
return [
    // ...
    Spiriit\Bundle\AuthLogBundle\SpiriitAuthLogBundle::class => ['all' => true],
];
```

Setting the bundle up takes four more steps: [configure it](/guide/configuration), then implement one interface on your [User entity](/guide/user-entity), create your [log entity](/guide/log-entity) and its [repository](/guide/repository).
