---
title: "Who logged in as you?"
date: 2026-08-31
description: "A stolen password is not the failure. What your application does in the minutes after someone says “That wasn't me” — that is the part you control."
author: Spiriit
---

# Who logged in as you?

Do you know who logged into your application last week? From where? On which device?

And more importantly: what happens when the user says **“That wasn't me”**?

That's often where it ends.

The numbers say the rest. The median intruder goes **14 days** before anyone notices, and barely half of intrusions — **52 %** — are caught by the organization itself, [Mandiant reports](https://cloud.google.com/blog/topics/threat-intelligence/m-trends-2026). Everyone else finds out from a third party, or from a ransom note.

The password may have been stolen somewhere else. It usually was: credential abuse still turns up somewhere in **39 % of breaches**, and half of ransomware victims had credentials leaked in the **95 days** before the attack — [Verizon's 2026 DBIR](https://www.verizon.com/about/news/breach-industry-wide-dbir-finds).

What your application can control is what happens next.

New IP address, new device, new location: notify the user.

**“That was me” / “That wasn't me”**

And if it wasn't them, take action: close sessions, revoke access, require a password change, raise an alert.

Speed is the whole game. Stolen access now changes hands **22 seconds** after the initial compromise — it was more than eight hours in 2022. And breaches that run past 200 days cost about **a third more** than the ones closed sooner.

That's exactly what **Auth Log Bundle** is for: logging authentication attempts, detecting unusual contexts, and [reacting when a login is disputed](/features/disavowal-reactions).

```bash
composer require spiriitlabs/auth-log-bundle
```

The question isn't whether an account will ever be compromised.

It's **what you'll do when it happens.**

---

*Sources: [Mandiant M-Trends 2026](https://cloud.google.com/blog/topics/threat-intelligence/m-trends-2026) · [Verizon DBIR 2026](https://www.verizon.com/business/resources/reports/dbir/) · [IBM Cost of a Data Breach 2026](https://www.ibm.com/reports/data-breach)*
