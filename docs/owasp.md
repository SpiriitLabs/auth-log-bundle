# OWASP Authentication Best Practices

To ensure strong authentication security, this bundle aligns with guidance from the OWASP Authentication Cheat Sheet by:

* Treating authentication failures or unusual logins as events worthy of detection and alerting
* Ensuring all login events are logged, especially when the context changes (IP, location, device)
* Using secure channels (TLS) for all authentication-related operations
* Validating and normalizing incoming data (e.g. user agent strings, IP addresses) to avoid ambiguity or spoofing
