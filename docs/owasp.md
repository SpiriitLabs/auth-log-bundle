# Security & OWASP

Authentication logging is not a nice-to-have. It is the control that turns a silent account takeover into an incident somebody can see — and, when the user is notified, into one they can stop themselves.

## Why it matters

OWASP lists the absence of this control in its Top 10. Under [A09:2021 – Security Logging and Monitoring Failures](https://owasp.org/Top10/2021/A09_2021-Security_Logging_and_Monitoring_Failures/), the very first symptom is:

> Auditable events, such as logins, failed logins, and high-value transactions, are not logged.

and further down:

> The application cannot detect, escalate, or alert for active attacks in real-time or near real-time.

Stolen credentials rarely announce themselves. The attacker knows the password, so authentication *succeeds* — nothing fails, nothing errors, and nothing appears in your logs. What changes is the **context**: a different IP address, a different country, a different browser. That change is the signal, and recording it is the only way to see it.

[A07:2021 – Identification and Authentication Failures](https://owasp.org/Top10/2021/A07_2021-Identification_and_Authentication_Failures/) makes the alerting side explicit:

> Log all failures and alert administrators when credential stuffing, brute force, or other attacks are detected.

The [Authentication Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html) states the same requirement as a baseline:

> Enable logging and monitoring of authentication functions to detect attacks/failures on a real-time basis.

## How this bundle helps

| OWASP concern | What the bundle does |
|---|---|
| Auditable events are not logged | Persists every successful login — identity, IP address, user agent, timestamp and location |
| No real-time detection | Compares each login against known contexts and reacts on the spot, synchronously or via Messenger |
| No alerting | Notifies the account owner by email, or through any transport you plug in |
| Nobody reviews the logs | Puts the decision in the user's hands with signed "It was me / It wasn't me" links |

The last row is the point that matters most in practice. Security teams rarely have the capacity to review authentication logs; the account owner, on the other hand, knows instantly whether they just signed in from Lyon on a Mac. [Login confirmation](/features/login-confirmation) turns them into the reviewer, and hands your application a `LOGIN_DISAVOWED` event to act on.

## Design decisions

* **Signed, single-use, expiring links.** Confirmation URLs are signed with your application secret and carry a TTL. Replaying a used link shows an "already handled" page instead of acting twice.
* **No action from a link preview.** Clicking opens an intermediate page whose button issues a `POST`, so email scanners that follow every URL (Outlook Safe Links and friends) cannot acknowledge or disavow a login on the user's behalf.
* **Identity, not just an identifier.** Logs record the user class alongside the identifier, so two accounts of different classes sharing an identifier are never conflated.
* **No decision is taken for you.** The bundle records the outcome and dispatches an event. Forcing a password reset, invalidating sessions or paging your security team stays your application's call.

## What this bundle is not

It is an audit-log and alerting component, not a complete authentication defence. It does **not** provide multi-factor authentication, rate limiting or lockout on failed attempts, credential-stuffing detection, or bot protection — all of which A07 also calls for. Symfony's [login throttling](https://symfony.com/doc/current/security/login_throttling.html) and MFA belong alongside it, not after it.

It also records **successful** logins: failures never reach `LoginSuccessEvent`. Logging and reviewing authentication failures, as A07 requires, remains a separate concern.
