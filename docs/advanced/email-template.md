# Custom email template

You can override the default email template:

![Default email template](/images/ipApi.png)

Create the file:

```
templates/bundles/SpiriitAuthLogBundle/new_device.html.twig
```

## Available variables

| Variable | Type | Description |
|---|---|---|
| `userInformation.ipAddress` | `?string` | Client IP address |
| `userInformation.userAgent` | `?string` | Browser / device user agent |
| `userInformation.loginAt` | `?DateTimeImmutable` | Login timestamp |
| `userInformation.location` | `?LocateValues` | Geolocation (city, country, latitude, longitude) |
| `authenticableLog.displayName` | `string` | User display name |
| `authenticableLog.email` | `string` | User email |
| `confirmationLinks` | `?ConfirmationLinks` | `acknowledgeUrl` / `disavowUrl` — only set when the [confirmation feature](/features/login-confirmation) is enabled |
