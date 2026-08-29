---
layout: home

hero:
  name: Auth Log Bundle
  text: Know when a login looks unusual
  tagline: Symfony authentication audit log with geolocation, device detection and security notifications.
  actions:
    - theme: brand
      text: Get started
      link: /guide/installation
    - theme: alt
      text: View on GitHub
      link: https://github.com/SpiriitLabs/auth-log-bundle

features:
  - title: Authentication event logging
    details: Track successful logins with IP address, user agent, timestamp and location.
  - title: Geolocation support
    details: Enrich logs with location data using a local GeoIP2 database or the IP API service.
  - title: Email notifications
    details: Automatically alert users when a login from an unknown context is detected.
  - title: Messenger integration
    details: Optional async processing with Symfony Messenger, so login requests stay fast.
  - title: Repository-based persistence
    details: No factory or listener boilerplate — implement two interfaces in your repository and you are done.
  - title: Extensible
    details: Replace the default email notification with any custom transport via NotificationInterface.
---
