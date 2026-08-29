# Architecture

Internal flow when a user logs in:

1. `LoginListener` catches Symfony's `LoginSuccessEvent`
2. Builds a `LoginParameterDto` from the request (IP, user agent, user identifier)
3. Dispatches to `LoginService` (sync) or `AuthLoginMessage` (async via Messenger)
4. `LoginService` fetches geolocation data via `FetchUserInformation`
5. `DoctrineAuthenticationLogHandler` checks if the context is known (`findExistingLog`), and if not, creates and saves the log (`createLog` + `save`)
6. Dispatches `AuthenticationLogEvents::NEW_DEVICE` event
7. Sends notification via `NotificationInterface`

## Extension points

| Interface | Responsibility | Page |
|---|---|---|
| `AuthLogUserInterface` | expose the email and display name of the user | [User entity](/guide/user-entity) |
| `AuthenticationLogRepositoryInterface` | decide whether a context is known, and persist logs | [Repository](/guide/repository) |
| `AuthenticationLogCreatorInterface` | build the log entity | [Repository](/guide/repository) |
| `ConfirmableAuthenticationLogRepositoryInterface` | look a log up by its confirmation token | [Login confirmation](/features/login-confirmation) |
| `NotificationInterface` | deliver the alert | [Custom notification](/advanced/custom-notification) |
| `AuthenticationLogHandlerInterface` | replace the whole persistence step | advanced |
