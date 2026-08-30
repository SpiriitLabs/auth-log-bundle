---
layout: home

hero:
  name: Auth Log Bundle
  text: Stolen credentials don't break in. They sign in.
  tagline: Every login your app accepts is invisible until you record it. This bundle logs each one — IP, device, location — and alerts the user the moment a context looks new.
  image:
    light: /hero-alert-light.svg
    dark: /hero-alert-dark.svg
    alt: New sign-in alert
  actions:
    - theme: brand
      text: Get started
      link: /guide/installation
    - theme: alt
      text: Why your app needs this
      link: /owasp
    - theme: alt
      text: GitHub
      link: https://github.com/SpiriitLabs/auth-log-bundle

features:
  - icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h9M4 12h16M4 18h7"/><circle cx="17.5" cy="6" r="2.5"/></svg>'
    title: Authentication event logging
    details: Track successful logins with IP address, user agent, timestamp and location — the auditable events OWASP A09 expects you to record.
    link: /owasp
    linkText: Why it matters
  - icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>'
    title: Geolocation support
    details: Enrich logs with a city and country, using a local GeoIP2 database or the IP API service.
    link: /features/geolocation
    linkText: Pick a provider
  - icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3.5 7.5 8.5 6 8.5-6"/></svg>'
    title: Email notifications
    details: Alert users when a login from an unknown context is detected, with an overridable Twig template.
    link: /advanced/email-template
    linkText: Customize the email
  - icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2 4.5 13.5H11l-1 8.5 8.5-11.5H12z"/></svg>'
    title: Async with Messenger
    details: Move geolocation lookups and email sending out of the request, so logins stay fast.
    link: /features/messenger
    linkText: Go async
  - icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="8" ry="3"/><path d="M4 5v14c0 1.66 3.58 3 8 3s8-1.34 8-3V5"/><path d="M4 12c0 1.66 3.58 3 8 3s8-1.34 8-3"/></svg>'
    title: Repository-based persistence
    details: No factory or listener boilerplate — implement two interfaces in your repository and you are done.
    link: /guide/repository
    linkText: See the repository
  - icon: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><path d="M9 2v6M15 2v6"/><path d="M6 8h12v3a6 6 0 0 1-12 0z"/><path d="M12 17v5"/></svg>'
    title: Extensible
    details: Replace email with Slack, SMS or anything else through a single interface, and react to events.
    link: /advanced/custom-notification
    linkText: Plug your own transport
---

<div class="home-extra">

## Up and running in minutes

One command, four lines of YAML, two interfaces to implement — the rest is automatic.

```bash
composer require spiriitlabs/auth-log-bundle
```

```yaml
# config/packages/spiriit_auth_log.yaml
spiriit_auth_log:
    transports:
        sender_email: 'no-reply@yourdomain.com'
        sender_name: 'Security'
    location:
        provider: 'ipApi'
```

</div>
