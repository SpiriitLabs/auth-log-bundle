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
- [Custom notification](/advanced/custom-notification) — replace email with Slack, SMS…

## Full reference

```yaml
spiriit_auth_log:
    messenger: false
    transports:
        mailer: 'mailer'
        sender_email: 'no-reply@yourdomain.com'
        sender_name: 'Your App Security'
    location:
        provider: 'ipApi' # or 'geoip2' or null
    confirmation:
        enabled: false
        token_ttl: '3 days'
```
