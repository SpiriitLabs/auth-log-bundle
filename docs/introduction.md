# What is Auth Log Bundle?

With this Symfony bundle you can send an email alert when a user logs in from a new context — for example:

* a different IP address
* a different location (geolocation)
* a different User Agent (device/browser)

This helps detect unusual login activity early and increases visibility into authentication events.

## Requirements

| Requirement | Version |
|---|---|
| PHP | 8.2 or higher |
| Symfony | 6.4, 7.4 or 8.0 |
| Doctrine ORM | 3.x or 4.x |
| geoip2/geoip2 | optional, only for local geolocation |

## Features

- **Authentication Event Logging**: Track successful logins with IP, user agent, timestamp and location
- **Geolocation Support**: Enrich logs with location data using GeoIP2 or IP API
- **Email Notifications**: Automatically alert users when a login from an unknown context is detected
- **Messenger Integration**: Optional async processing with Symfony Messenger
- **Repository-Based Persistence**: No factory or listener boilerplate — implement two interfaces in your repository and you're done
- **Extensible**: Replace the default email notification with any custom transport via `NotificationInterface`

::: tip Upgrading from v1 or v2?
See the [upgrade guides](/upgrade/3.0) for a step-by-step migration.
:::
