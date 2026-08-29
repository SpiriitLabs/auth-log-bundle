# Async with Messenger

By default the login is processed synchronously, inside the request. Geolocation lookups and email sending then add latency to the login response. Symfony Messenger moves that work out of the request.

```yaml
spiriit_auth_log:
    messenger: 'messenger.default_bus'
```

Route the message to an async transport:

```yaml
framework:
    messenger:
        routing:
            'Spiriit\Bundle\AuthLogBundle\Messenger\AuthLoginMessage\AuthLoginMessage': async
```

::: tip Absolute URLs from a worker
A Messenger worker has no request context, so generating absolute URLs (used by the [login confirmation](/features/login-confirmation) links) requires a default URI:

```yaml
# config/packages/routing.yaml
framework:
    router:
        default_uri: 'https://your-domain.com'
```
:::
