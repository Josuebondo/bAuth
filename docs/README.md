# 📚 BAuth Documentation

> Librairie PHP moderne pour authentification, autorisation, JWT, OAuth2, 2FA, WebAuthn et plus.

---

## 🎯 Où commencer?

### 👶 Je suis complètement nouveau

**Parcours recommandé (15-30 min):**

1. 📖 [Getting Started](GETTING_STARTED.md) — Un guide étape par étape (15 min)
2. 🔧 [Installation](INSTALLATION.md) (5 min si besoin de détails)
3. 🎮 Essayer un [Exemple](../examples/) (5 min)

👉 **Après:** [Guide d'utilisation - Authentification](USAGE.md#authentification)

---

### 🚀 Je veux commencer rapidement

**Parcours accéléré (30 min):**

1. ⚡ [Installation rapide](INSTALLATION.md#installation-rapide-3-minutes) (3 min)
2. 🔑 [Getting Started](GETTING_STARTED.md) (20 min)
3. 💻 Copier/adapter les [Exemples](../examples/) (5 min)

👉 **Besoin d'aide?** Consulter [Quick Reference](QUICK_REFERENCE.md)

---

### 🎓 Je veux comprendre en profondeur

**Parcours complet (2-3 heures):**

1. 📖 [Installation détaillée](INSTALLATION.md)
2. 📘 [Guide d'utilisation complet](USAGE.md) - tous les concepts
3. 🎯 [Quick Reference](QUICK_REFERENCE.md) - snippets utiles
4. 🛠️ [API Reference](API.md) - toutes les méthodes
5. 🔒 [Guide de sécurité](SECURITY.md) - bonnes pratiques
6. 🚨 [Dépannage](TROUBLESHOOTING.md) - problèmes courants
7. 🔌 [Intégrations frameworks](LARAVEL.md) / [Symfony](SYMFONY.md)

---

### 🔧 Je dois intégrer BAuth à mon projet existant

**Selon votre framework:**

- **Framework:** [Laravel](LARAVEL.md) | [Symfony](SYMFONY.md)
- **Autre/PHP natif:** Suivre [Getting Started](GETTING_STARTED.md)

---

## 📚 Guides essentiels

| Document                                     | Durée  | But                                     |
| -------------------------------------------- | ------ | --------------------------------------- |
| **[Getting Started](GETTING_STARTED.md)** 🚀 | 30 min | Mettre en place un système auth complet |
| **[Quick Reference](QUICK_REFERENCE.md)** 📋 | 2 min  | Trouver rapidement un snippet           |
| **[Installation](INSTALLATION.md)** 🔧       | 10 min | Configurer BAuth en détail              |
| **[Guide d'utilisation](USAGE.md)** 📘       | 45 min | Maîtriser toutes les fonctionnalités    |
| **[Sécurité](SECURITY.md)** 🔒               | 20 min | Bonnes pratiques de sécurité            |

---

## ⚡ Quick Start (~5 min)

### Installation

```bash
composer require bmvc/bauth
```

### Configuration minimale

```php
<?php
use Bmvc\BAuth\Auth;
use Bmvc\BAuth\Config;

$config = new Config([
    'jwt' => ['secret' => 'your-secret', 'expiresIn' => 3600],
    'password' => ['algorithm' => PASSWORD_BCRYPT, 'options' => ['cost' => 12]],
]);

$auth = new Auth($config);
```

### Authentification

```php
// Connexion
$result = $auth->login('user@example.com', 'password123');
$user = $result['user'];
$token = $result['token'];

// Vérifier si connecté
if ($auth->isAuthenticated()) {
    $user = $auth->user();
    echo "Bienvenue, " . $user['email'];
}

// Déconnexion
$auth->logout();
```

👉 **En savoir plus:** [Getting Started](GETTING_STARTED.md) | [Guide d'utilisation](USAGE.md)

---

## 📖 Documentation par domaine

### ✅ Authentification & Sessions

| Sujet                | Débutant                                               | Avancé                                           |
| -------------------- | ------------------------------------------------------ | ------------------------------------------------ |
| **Authentification** | [USAGE.md#authentification](USAGE.md#authentification) | [API.md#auth-login](API.md#auth-login)           |
| **Sessions**         | [USAGE.md#sessions](USAGE.md#gestion-des-sessions)     | [SECURITY.md#sessions](SECURITY.md#sessions)     |
| **Tokens JWT**       | [USAGE.md#jwt](USAGE.md#jwt)                           | [SECURITY.md#tokens-jwt](SECURITY.md#tokens-jwt) |

### 🔐 Sécurité & Autorisation

| Sujet                   | Débutant                                                          | Avancé                                                 |
| ----------------------- | ----------------------------------------------------------------- | ------------------------------------------------------ |
| **Rôles & Permissions** | [USAGE.md#autorisation](USAGE.md#autorisation)                    | [API.md#authorization](API.md#authorization)           |
| **Mots de passe**       | [USAGE.md#sécurité-des-mots](USAGE.md#sécurité-des-mots-de-passe) | [SECURITY.md#mots-de-passe](SECURITY.md#mots-de-passe) |
| **2FA (TOTP)**          | [USAGE.md#2fa](USAGE.md#2fa)                                      | [Guide 2FA](SECURITY.md#2fa)                           |

### 🆕 Fonctionnalités avancées

| Fonctionnalité         | Description                          | Guide                                |
| ---------------------- | ------------------------------------ | ------------------------------------ |
| **OAuth2**             | Google, GitHub, Facebook, Microsoft  | [OAuth2.md](OAUTH2.md)               |
| **Connexion Sociale**  | Lier comptes sociaux, Single Sign-On | [SOCIAL_LOGIN.md](SOCIAL_LOGIN.md)   |
| **Clés API**           | Authentifier applications tierces    | [API_KEYS.md](API_KEYS.md)           |
| **Sessions Multiples** | Gérer plusieurs appareils/sessions   | [MULTI_SESSION.md](MULTI_SESSION.md) |
| **WebAuthn**           | Authentification FIDO2 / Passkeys    | [WEBAUTHN.md](WEBAUTHN.md)           |

---

## 🎛️ Intégration avec frameworks

### Laravel

- **[Guide complet - Laravel](LARAVEL.md)**
  - Installation et configuration
  - Service Provider
  - Contrôleurs d'authentification
  - Middleware
  - Authorization policies
  - Tests

### Symfony

- **[Guide complet - Symfony](SYMFONY.md)**
  - Installation et configuration
  - Service et DI
  - Entités Doctrine
  - Contrôleurs
  - Guards personnalisés
  - Voters d'autorisation
  - Événements

### PHP pur

- Voir [Guide d'utilisation](USAGE.md)
- Voir [Exemples - GenericAuthProvider](../examples/README.md)

## 🔧 Guides spécialisés

### Configuration

- [Installation - Configuration de base](INSTALLATION.md#configuration-basique)
- [Installation - Base de données](INSTALLATION.md#créer-la-base-de-données)
- [API - Classe Config](API.md#classe-config)

### Sécurité

- [Guide complet de sécurité](SECURITY.md)
  - Bonnes pratiques
  - HTTPS et TLS
  - Hachage des mots de passe
  - CORS et Rate limiting
  - Audit et logging
  - OWASP Top 10

### Gestion des erreurs

- [Guide d'utilisation - Gestion des erreurs](USAGE.md#gestion-des-erreurs)
- [API - Exceptions](API.md#exceptions)
- [Dépannage - Logs et débogage](TROUBLESHOOTING.md#logs-et-débogage)

## 🗂️ Fichiers de documentation

```
docs/
├── README.md                    # Cette page
├── INSTALLATION.md              # Guide d'installation complète
├── USAGE.md                     # Guide d'utilisation avec exemples
├── API.md                       # Référence API détaillée
├── LARAVEL.md                   # Guide d'intégration Laravel
├── SYMFONY.md                   # Guide d'intégration Symfony
├── SECURITY.md                  # Guide de sécurité
└── TROUBLESHOOTING.md           # Guide de dépannage
```

## 📋 Table des matières complète

### Installation

- Prérequis
- Installation via Composer
- Configuration de base
- Génération des clés secrètes
- Création de la base de données (MySQL, PostgreSQL, SQLite)
- Initialisation dans votre application
- Vérification de l'installation

### Utilisation

- Concepts de base
- Authentification (login, logout, vérification)
- Gestion des sessions
- Tokens JWT (génération, vérification, renouvellement)
- Autorisation (permissions, rôles)
- 2FA (activation, vérification)
- Gestion des utilisateurs
- Gestion des mots de passe
- Gestion des erreurs
- Cas d'usage complets

### Référence API

- Classe Auth
- Classe Config
- Interfaces (5 interfaces)
- Providers (fournisseurs d'implémentation)
- Exceptions
- Utilitaires (Password)
- Constantes
- Guide d'extensibilité

### Intégration Laravel

- Installation du Service Provider
- Configuration .env
- Structure des données
- Contrôleurs d'authentification
- Routes API
- Middleware
- Contrôle d'accès (Policies)
- Authentification JWT
- 2FA avec Laravel
- Événements
- Tests

### Intégration Symfony

- Installation du Service
- Configuration services.yaml
- Entités Doctrine
- Contrôleurs
- Guards d'authentification
- Voters d'autorisation
- Événements
- Tests
- Configuration avancée
- Intégration Doctrine

### Sécurité

- Bonnes pratiques (15 recommandations)
- Configuration HTTPS
- Sécurité des tokens JWT
- Sécurité des mots de passe
- Sécurité des sessions
- Configuration CORS
- Rate limiting
- Audit et logging
- OWASP Top 10 (toutes les vulnérabilités)
- Checklist de sécurité

### Dépannage

- Installation (3 problèmes communs)
- Authentification (4 problèmes)
- Tokens JWT (4 problèmes)
- Sessions (3 problèmes)
- Autorisation (3 problèmes)
- 2FA (2 problèmes)
- Base de données (3 problèmes)
- Framework-spécifique (Laravel, Symfony)
- Logs et débogage

## 🎓 Parcours d'apprentissage recommandé

### Débutant (1-2 heures)

1. [Installation](INSTALLATION.md)
2. [Guide d'utilisation - Authentification](USAGE.md#authentification)
3. [Exemples - quick-start.php](../examples/quick-start.php)

### Intermédiaire (3-4 heures)

1. [Guide d'utilisation complet](USAGE.md)
2. Choisir votre intégration :
   - [Laravel](LARAVEL.md) ou
   - [Symfony](SYMFONY.md) ou
   - [PHP pur](USAGE.md)
3. [Référence API](API.md)
4. [Exemples - api-rest.php](../examples/api-rest.php)

### Avancé (5-6 heures)

1. [Guide de sécurité](SECURITY.md)
2. [Référence API - Extensibilité](API.md#guide-dextensibilité)
3. [Dépannage](TROUBLESHOOTING.md)
4. Créer des implémentations personnalisées

## 🚀 Cas d'utilisation courants

### "Je veux juste me connecter/déconnecter"

→ [Guide d'utilisation - Authentification](USAGE.md#authentification)

### "Je veux des tokens JWT pour une API"

→ [Guide d'utilisation - Tokens JWT](USAGE.md#tokens-jwt)

### "Je veux implémenter les rôles et permissions"

→ [Guide d'utilisation - Autorisation](USAGE.md#autorisation)

### "Je veux ajouter le 2FA"

→ [Guide d'utilisation - 2FA](USAGE.md#2fa)

### "Je veux intégrer avec Laravel"

→ [Guide Laravel](LARAVEL.md)

### "Je veux intégrer avec Symfony"

→ [Guide Symfony](SYMFONY.md)

### "Je veux sécuriser mon application"

→ [Guide de sécurité](SECURITY.md)

### "J'ai une erreur"

→ [Guide de dépannage](TROUBLESHOOTING.md)

## 📞 Support et ressources

### Documentation externe

- [Documentation PHP](https://www.php.net/manual/)
- [Firebase JWT](https://github.com/firebase/php-jwt)
- [Symfony](https://symfony.com/doc/)
- [Laravel](https://laravel.com/docs/)
- [OWASP](https://owasp.org/)

### Obtenir de l'aide

1. Consultez d'abord le [guide de dépannage](TROUBLESHOOTING.md)
2. Vérifiez les [exemples](../examples/)
3. Posez une question sur le GitHub

## 📝 Convention de documentation

- **Code en PHP** : Utilisé pour tous les exemples
- **Chemins de fichiers** : Relatifs au répertoire racine du projet
- **Noms de classe** : Espaces de noms complets (ex: `BAuth\Auth`)
- **Exceptions** : Toujours catchées avec le type spécifique

## 🎯 Objectifs de cette documentation

✅ Complète - Couvre tous les aspects de BAuth
✅ Claire - Exemples concrets et cas d'utilisation
✅ Pratique - Solutions immédiatement applicables
✅ Sécurisée - Meilleures pratiques de sécurité
✅ Accessible - Pour débutants et avancés
✅ À jour - Synchronisée avec le code

---

**Dernière mise à jour :** 2026-05-09

**Version de BAuth :** 1.0.0

**Prêt à commencer ?** → [Installation](INSTALLATION.md) 🚀
