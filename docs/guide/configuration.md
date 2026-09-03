---
description: "Configure spiriit_auth_log — sender identity for alert emails, geolocation provider, Messenger bus — with the full option reference."
---

# Configuration

Create the bundle configuration file and set the sender identity used for notification emails:

```yaml
# config/packages/spiriit_auth_log.yaml
spiriit_auth_log:
    transports:
        sender_email: 'no-reply@yourdomain.com'
        sender_name: 'Security'
```

This is the minimum required configuration. Every other option is covered in its own page:

- [Geolocation](/features/geolocation) — enrich logs with city and country
- [Async with Messenger](/features/messenger) — process logins outside the request
- [Login confirmation](/features/login-confirmation) — "It was me / It wasn't me" links
- [Disavowal reactions](/features/disavowal-reactions) — what happens when a login is disavowed
- [Custom notification](/advanced/custom-notification) — replace email with Slack, SMS…

## Full reference

```yaml
spiriit_auth_log:
    # false, or the id of the message bus handling AuthLoginMessage
    messenger: false

    transports:
        # 'mailer', or the id of your own NotificationInterface service
        mailer: 'mailer'
        sender_email: 'no-reply@yourdomain.com'   # required
        sender_name: 'Your App Security'          # required

    location:
        provider: null                # 'geoip2' (recommended), 'ipApi' (development only) or null
        geoip2_database_path: null    # required with the 'geoip2' provider

    confirmation:
        enabled: false
        token_ttl: '3 days'                        # relative expression
        route_name: 'spiriit_auth_log_confirm'     # override with your own route
        on_disavowal:
            revoke_known_contexts: true   # requires RevocableAuthenticationLogRepositoryInterface
            invalidate_sessions: false    # requires SessionInvalidatorInterface
            force_password_reset: false   # requires PasswordResetRequesterInterface
```

`location` can be omitted entirely — geolocation is then disabled.

::: warning Pick `geoip2` in production
The `ipApi` provider sends the user's IP address to a third party over unencrypted HTTP, and trusts the answer it gets back. See [Geolocation](/features/geolocation) before enabling it outside development.
:::

## Validation rules

The container refuses to compile when the configuration is inconsistent, rather than degrading silently:

- `location.provider: 'geoip2'` without `geoip2_database_path`
- `confirmation.token_ttl` that is not a valid relative date expression
- an `on_disavowal` reaction enabled while `confirmation.enabled` is `false`
- an `on_disavowal` reaction enabled without an implementation of the interface it needs
