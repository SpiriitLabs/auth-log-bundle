# Security & OWASP

Authentication logging is the control that turns a silent account takeover into an incident somebody can see — and, when the user is notified, into one they can stop themselves.

## Why it matters

Stolen credentials rarely announce themselves. The attacker knows the password, so authentication *succeeds*: nothing fails, nothing errors, nothing appears in your logs. What changes is the **context** — a different IP address, country or browser. Recording it is the only way to see it.

OWASP lists the absence of this control in its Top 10. [A09:2021 – Security Logging and Monitoring Failures](https://owasp.org/Top10/2021/A09_2021-Security_Logging_and_Monitoring_Failures/) opens on it:

> Auditable events, such as logins, failed logins, and high-value transactions, are not logged.

and [A07:2021 – Identification and Authentication Failures](https://owasp.org/Top10/2021/A07_2021-Identification_and_Authentication_Failures/) adds the alerting side: *alert administrators when credential stuffing, brute force, or other attacks are detected*.

## How this bundle helps

| OWASP concern | What the bundle does |
|---|---|
| Auditable events are not logged | Persists every successful login — identity, IP address, user agent, timestamp and location |
| No real-time detection | Compares each login against known contexts and reacts on the spot, synchronously or via Messenger |
| No alerting | Notifies the account owner by email, or through any transport you plug in |
| Nobody reviews the logs | Puts the decision in the user's hands with signed "It was me / It wasn't me" links |

The last row matters most in practice. Security teams rarely have the capacity to review authentication logs; the account owner knows instantly whether they just signed in from Lyon on a Mac. [Login confirmation](/features/login-confirmation) turns them into the reviewer.

## Design decisions

* **Signed, single-use, expiring links.** Replaying a used link shows an "already handled" page instead of acting twice.
* **No action from a link preview.** Clicking opens an intermediate page whose button issues a `POST`, so email scanners that follow every URL cannot answer on the user's behalf.
* **Identity, not just an identifier.** Logs record the user class alongside the identifier, so two accounts of different classes are never conflated.
* **Sensible defaults, no surprises.** Disavowal revokes the user's known contexts; invalidating sessions or forcing a password reset stays [opt-in](/features/disavowal-reactions).

## What this bundle is not

It is an audit-log and alerting component, not a complete authentication defence. It provides no MFA, no rate limiting or lockout, no credential-stuffing detection, no bot protection — all of which A07 also calls for. Symfony's [login throttling](https://symfony.com/doc/current/security/login_throttling.html) and MFA belong alongside it, not after it.

It also records **successful** logins only: failures never reach `LoginSuccessEvent`.
