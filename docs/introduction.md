---
description: "A Symfony bundle that logs every successful login and emails the user when the context changes — new IP address, location or device."
---

# What is Auth Log Bundle?

This Symfony bundle emails an alert when a user logs in from a new context — a different IP address, a different location, or a different device.

Recording that change is what turns a silent account takeover into an incident somebody can see: [why it matters](/owasp).

## Requirements

| Requirement | Version |
|---|---|
| PHP | 8.2 or higher |
| Symfony | 6.4, 7.4 or 8.0 |
| Doctrine ORM | 3.x or 4.x |
| geoip2/geoip2 | optional, only for local geolocation |

::: tip Already using the bundle?
The [upgrade guides](/upgrade/4.0) cover each major version step by step.
:::
