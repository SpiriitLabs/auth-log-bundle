---
description: "Enrich each log with city, country and coordinates, using a local MaxMind GeoIP2 database or the IP API service."
---

# Geolocation

Geolocation enriches each log with a city, country, latitude and longitude. Two providers are available.

## GeoIP2 (local database)

Resolves the IP against a local MaxMind database — no external call, no rate limit.

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

## IP API (external service)

Calls the [ip-api.com](https://ip-api.com) service. No database to maintain, but the free tier is limited to **45 requests per minute**.

The request is bounded (2 s idle, 4 s total). If ip-api.com is slow or unreachable, the location is dropped, a warning is logged and the login proceeds normally — the log is recorded without location. On the synchronous path an outage still adds up to 4 s to every login, so move the call out of the request with [async processing](/features/messenger).

```yaml
spiriit_auth_log:
    location:
        provider: 'ipApi'
```

::: warning Rate limit
Because the call happens during login, a burst of logins can hit the limit. Combine this provider with [async processing](/features/messenger) on a busy application.
:::

## Disabling geolocation

Set the provider to `null` — logs are then recorded with the IP address and user agent only.

```yaml
spiriit_auth_log:
    location:
        provider: null
```
