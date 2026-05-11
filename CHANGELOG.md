# Changelog

Tous les changements notables de ce projet seront documentés dans ce fichier.

## [1.1.0] - 2026-05-10

### Ajouté

#### OAuth2 Authentication

- Support complet de l'authentification OAuth2
- Intégration avec Google, GitHub, Facebook, Microsoft
- Gestion des tokens d'accès et de rafraîchissement
- Revocation de tokens
- Support des fournisseurs personnalisés
- Adapters pour Laravel et Symfony

#### Social Login

- Liaison de comptes sociaux à des utilisateurs existants
- Création d'utilisateurs via comptes sociaux
- Gestion de multiples comptes sociaux par utilisateur
- Mise à jour des données de profil social
- Adapters Laravel et Symfony

#### API Keys Management

- Génération de clés API sécurisées
- Permissions granulaires par clé
- Support des expirations de clés
- Historique d'utilisation des clés
- Révocation de clés simples et en masse
- Middleware d'authentification API
- Adapters Laravel et Symfony

#### Multi-Session Management

- Support de multiples sessions simultanées par utilisateur
- Gestion des appareils et dispositifs
- Détection d'activité suspecte
- Limitation du nombre de sessions simultanées
- Nettoyage automatique des sessions expirées
- Monitoring de l'inactivité
- Adapters Laravel et Symfony

#### WebAuthn / Passkeys

- Support complet de WebAuthn (FIDO2, U2F)
- Authentification sans mot de passe
- Enregistrement de clés de sécurité
- Support des codes de secours
- Détection du statut de sauvegarde
- Adapters Laravel et Symfony

### Interfaces ajoutées

- `OAuth2ProviderInterface`
- `SocialLoginProviderInterface`
- `APIKeyProviderInterface`
- `MultiSessionProviderInterface`
- `WebAuthnProviderInterface`

### Providers ajoutés

- `BaseOAuth2Provider`
- `BaseSocialLoginProvider`
- `BaseAPIKeyProvider`
- `BaseMultiSessionProvider`
- `BaseWebAuthnProvider`

### Adapters Laravel ajoutés

- `LaravelOAuth2Provider`
- `LaravelSocialLoginProvider`
- `LaravelAPIKeyProvider`
- `LaravelMultiSessionProvider`
- `LaravelWebAuthnProvider`

### Adapters Symfony ajoutés

- `SymfonyOAuth2Provider`
- `SymfonySocialLoginProvider`
- `SymfonyAPIKeyProvider`
- `SymfonyMultiSessionProvider`
- `SymfonyWebAuthnProvider`

### Documentation

- Documentation complète pour OAuth2 ([OAUTH2.md](docs/OAUTH2.md))
- Documentation complète pour Social Login ([SOCIAL_LOGIN.md](docs/SOCIAL_LOGIN.md))
- Documentation complète pour API Keys ([API_KEYS.md](docs/API_KEYS.md))
- Documentation complète pour Multi-Session ([MULTI_SESSION.md](docs/MULTI_SESSION.md))
- Documentation complète pour WebAuthn ([WEBAUTHN.md](docs/WEBAUTHN.md))
- Guide d'installation mis à jour ([INSTALLATION.md](docs/INSTALLATION.md))
- Examples et snippets de code pour tous les nouveaux providers

### Migration requise

Pour Laravel:

```bash
php artisan migrate
```

Tables à créer:

- `social_accounts`
- `api_keys`
- `sessions`
- `webauthn_credentials`
- `webauthn_backup_codes`

## [1.0.0] - 2026-05-09

### Ajouté

- Authentification par email/mot de passe
- Gestion des tokens JWT
- Gestion des sessions PHP
- Système de rôles et permissions
- Support du 2FA (TOTP)
- Hachage sécurisé des mots de passe (bcrypt)
- Intégrations Laravel et Symfony
- Fournisseur générique avec callbacks
- Fournisseur PDO pour MySQL, PostgreSQL, SQLite
- Tests unitaires complets
- Documentation complète
- Exemples d'utilisation

### Architecture

- Interfaces pour extensibilité
- Classes de base pour implémentation facile
- Configuration flexible
- Exceptions personnalisées
- Support PSR-4

## Versioning

Ce projet utilise le [Semantic Versioning](https://semver.org/).

### Ajouté

- Authentification par email/mot de passe
- Gestion des tokens JWT
- Gestion des sessions PHP
- Système de rôles et permissions
- Support du 2FA (TOTP)
- Hachage sécurisé des mots de passe (bcrypt)
- Intégrations Laravel et Symfony
- Fournisseur générique avec callbacks
- Fournisseur PDO pour MySQL, PostgreSQL, SQLite
- Tests unitaires complets
- Documentation complète
- Exemples d'utilisation

### Architecture

- Interfaces pour extensibilité
- Classes de base pour implémentation facile
- Configuration flexible
- Exceptions personnalisées
- Support PSR-4

## Versioning

Ce projet utilise le [Semantic Versioning](https://semver.org/).
