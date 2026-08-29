# SpiriitLabs Auth Log Bundle

Symfony bundle pour détecter et logger les activités d'authentification inhabituelles (nouvelle IP, nouveau device, nouvelle localisation). Envoie des alertes email et peut persister les logs en base de données.

## Stack technique

- **PHP** : >= 8.2
- **Symfony** : 6.4 | 7.4 | 8.0
- **Doctrine ORM** : 3.x | 4.x
- **Optionnel** : geoip2/geoip2 (géolocalisation locale)

## Commandes utiles

```bash
composer test        # Lancer les tests (PHPUnit via Symfony Bridge)
composer cs-check    # Vérifier le code style (PHP-CS-Fixer, dry-run)
composer cs-fix      # Corriger le code style
```

PHPStan n'a pas de script composer dédié :

```bash
vendor/bin/phpstan analyse
```

## Architecture

### Namespace racine : `Spiriit\Bundle\AuthLogBundle\`

```
src/
├── AuthenticationLogFactory/   # Factory pattern : création et vérification des logs
├── DependencyInjection/        # Configuration Symfony (extension, compiler pass)
├── DTO/                        # LoginParameterDto, UserReference
├── Entity/                     # AbstractAuthenticationLog (mapped superclass)
├── FetchUserInformation/       # Collecte IP, user agent, géolocalisation
├── Listener/                   # LoginListener (écoute LoginSuccessEvent)
├── Messenger/                  # Support async via Symfony Messenger
├── Notification/               # Envoi d'emails (MailerNotification)
├── Resources/                  # Config services, templates Twig, traductions
├── Services/                   # Logique métier (LoginService, AuthenticationContextBuilder)
└── SpiriitAuthLogBundle.php    # Point d'entrée du bundle
```

### Flux d'authentification

1. `LoginListener` écoute `LoginSuccessEvent`
2. Crée `LoginParameterDto` depuis la requête
3. Dispatch vers `LoginService` (sync) ou `AuthLoginMessage` (async via Messenger)
4. `LoginService` construit le contexte via `AuthenticationContextBuilder`
5. Vérifie si le contexte est connu via la factory
6. Si inconnu : `AuthenticationEventPublisher` dispatche l'événement et envoie un email

### Patterns utilisés

- **Factory** : `AuthenticationLogFactoryInterface` pour créer les logs
- **Strategy** : `FetchUserInformationMethodInterface` pour les providers de géoloc
- **Observer** : Événements `AuthenticationLogEvent` avec listeners
- **Compiler Pass** : Collecte automatique des factories tagguées

## CI / Matrice de tests

GitHub Actions teste 3 combinaisons :
- PHP 8.2 + Symfony 6.4
- PHP 8.2 + Symfony 7.4
- PHP 8.4 + Symfony 8.0

## Conventions

- Classes `final` par défaut
- Pas d'héritage (sauf `AbstractAuthenticationLog` qui est un mapped superclass Doctrine)
- DTOs en `readonly`
- Les factories sont enregistrées via le tag `spiriit_auth_log.factory`
- Les listeners doivent appeler `markAsHandled()` sur l'événement
