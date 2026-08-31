# Supported login types

The bundle hooks into a single extension point: Symfony's [`LoginSuccessEvent`](https://symfony.com/doc/current/security.html#authentication-events). The authenticator manager dispatches it on **every successful authentication, whatever the authenticator** — so any login mechanism built on the authenticator system works, with nothing to configure.

| Login type | Triggered | Notes |
|---|---|---|
| `form_login` | ✅ | on form submit |
| `json_login` | ✅ | e.g. the `/api/login_check` endpoint of LexikJWTAuthenticationBundle |
| `login_link` | ✅ | |
| `remember_me` | ✅ | when the cookie re-authenticates the user |
| Programmatic login (`Security::login()`) | ✅ | |
| Custom authenticator (`AbstractAuthenticator`) | ✅ | |
| JWT (LexikJWT), `access_token`, `http_basic` | ✅ | stateless: fires on **every request**, see below |
| `switch_user` (impersonation) | ❌ | dispatches `SwitchUserEvent`, not `LoginSuccessEvent` |

The authenticated user must implement [`AuthLogUserInterface`](/guide/user-entity) — the listener ignores any other user class.

## Stateless firewalls (JWT, access tokens)

Without a session, the token is re-authenticated on **every request**, and each one dispatches `LoginSuccessEvent`. Once the IP/device pair is known the check short-circuits — no log, no email — but every API request still pays for the geolocation lookup (if a [provider](/features/geolocation) is configured) and the "is this context known?" query, or a message on the bus in [async mode](/features/messenger).

::: tip Recommendations for high-traffic APIs
- Prefer the **GeoIP2 local database**: an external HTTP call per request is a non-starter.
- Enable [async processing](/features/messenger) so the work happens in a worker.
- To cover the initial login only, keep the login firewall in scope alone — the simplest way is a user class that does not implement `AuthLogUserInterface` when authenticated through the stateless firewall.
:::

## API Platform

API Platform delegates authentication to the Symfony firewall, and the bundle plugs in below it, at the security-component level: **compatible out of the box**. The JWT login endpoint (`json_login`) is the nominal case; Bearer-token requests fall under the stateless behavior above.

With [login confirmation](/features/login-confirmation) enabled, the email links are opened in a browser without a token — keep the confirmation route out of your stateless API firewall:

```yaml
# config/packages/security.yaml
security:
    access_control:
        - { path: ^/auth-log/confirm, roles: PUBLIC_ACCESS }
        - { path: ^/api, roles: IS_AUTHENTICATED_FULLY }
```
