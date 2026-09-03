---
description: "Enrich each log with city, country and coordinates — a local MaxMind GeoIP2 database in production, the IP API service in development only."
---

# Geolocation

Geolocation enriches each log with a city, country, latitude and longitude. Two providers are available — **GeoIP2 is the one to use in production**, IP API is a development convenience.

| Provider | Outbound call | Production |
|---|---|---|
| `geoip2` | none — local database lookup | ✅ recommended |
| `ipApi` | unencrypted HTTP call to a third party | ❌ development only |

## GeoIP2 (local database) — recommended

Resolves the IP against a local MaxMind database: no external call, no rate limit, and no user data leaving your infrastructure.

```bash
composer require geoip2/geoip2
```

```yaml
spiriit_auth_log:
    location:
        provider: 'geoip2'
        geoip2_database_path: '%kernel.project_dir%/var/GeoLite2-City.mmdb'
```

You must download the `GeoLite2-City.mmdb` database yourself and keep it up to date.

## IP API (external service) — development only

Calls the [ip-api.com](https://ip-api.com) service. No database to maintain, but the free tier is limited to **45 requests per minute**.

The request is bounded (2 s idle, 4 s total). If ip-api.com is slow or unreachable, the location is dropped, a warning is logged and the login proceeds normally — the log is recorded without location. On the synchronous path an outage still adds up to 4 s to every login, so move the call out of the request with [async processing](/features/messenger).
::: danger The user's IP address leaves your server in clear text
The free tier of ip-api.com exposes no HTTPS endpoint — only the paid `pro.ip-api.com` does — so the bundle queries it over plain `http://`. Two consequences:

- **The IP address of every authenticated user** — personal data under the GDPR — is sent unencrypted to a third party you have no contract with. Using this provider in production makes that transfer a processing activity to declare in your record of processing activities (GDPR art. 30), and to cover with the third party's terms.
- **The response is trusted as-is.** Anyone able to observe or tamper with the traffic between your server and ip-api.com can rewrite the location before it reaches the alert email and the persisted log — showing "Paris, France" for a login from Moscow. The user then acknowledges a fraudulent login, and the very signal the detection relies on is turned against them.

Keep `ipApi` for development, and use `geoip2` — or no provider at all — in production.
:::

```yaml
spiriit_auth_log:
    location:
        provider: 'ipApi'
```

::: warning Rate limit
Because the call happens during login, a burst of logins can hit the limit. Combine this provider with [async processing](/features/messenger) on a busy application.
:::

## Disabling geolocation

Set the provider to `null` — logs are then recorded with the IP address and user agent only. Nothing leaves your server, and the IP/user-agent pair is still enough to detect a new context.

```yaml
spiriit_auth_log:
    location:
        provider: null
```
