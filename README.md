# BAuth

> Une librairie PHP moderne, modulaire et framework-agnostique pour gérer l’authentification, l’autorisation, les sessions, les tokens JWT et la sécurité des utilisateurs dans n’importe quel projet PHP.

---

<p align="center">

![PHP](https://img.shields.io/badge/PHP-8.1+-blue)
![License](https://img.shields.io/badge/license-MIT-green)
![Version](https://img.shields.io/badge/version-1.1-orange)
![JWT](https://img.shields.io/badge/JWT-supported-success)
![2FA](https://img.shields.io/badge/2FA-TOTP-blueviolet)
![OAuth2](https://img.shields.io/badge/OAuth2-supported-blueviolet)
![WebAuthn](https://img.shields.io/badge/WebAuthn-Passkeys-blueviolet)

</p>

---

# 🚀 Fonctionnalités

- ✅ Authentification complète
- ✅ JWT (JSON Web Token)
- ✅ Sessions sécurisées
- ✅ Rôles & permissions
- ✅ Support 2FA / TOTP
- ✅ **OAuth2** (Google, GitHub, Facebook, Microsoft)
- ✅ **Connexion Sociale**
- ✅ **Clés API** pour applications tierces
- ✅ **Sessions Multiples** par utilisateur
- ✅ **WebAuthn / Passkeys** (authentification sans mot de passe)
- ✅ Compatible Laravel, Symfony et PHP natif
- ✅ Architecture PSR-4
- ✅ Framework-agnostique
- ✅ Extensible via interfaces et providers
- ✅ Sécurité moderne intégrée

---

# 📦 Installation

Installer avec Composer :

```bash
composer require bmvc/bauth
```

---

# ⚡ Démarrage rapide

## Configuration

```php
<?php

use Bmvc\BAuth\Config;
use Bmvc\BAuth\Auth;

$config = new Config([
    'jwt' => [
        'secret' => env('AUTH_JWT_SECRET'),
        'expiresIn' => 3600,
    ],

    'password' => [
        'algorithm' => PASSWORD_BCRYPT,
        'options' => [
            'cost' => 12,
        ],
    ],
]);

$auth = new Auth($config);
```

---

# 🔌 Fournisseurs d’authentification

BAuth utilise des **providers** pour communiquer avec votre système utilisateur.

Les providers permettent d’intégrer facilement BAuth avec :

- PHP natif
- Laravel
- Symfony
- ou n’importe quel framework PHP

---

## PHP natif / projet générique

```php
<?php

use Bmvc\BAuth\Adapters\Generic\GenericAuthProvider;

$provider = new GenericAuthProvider($config);

$provider
    ->setGetUserByEmailCallback(function ($email) {

        return User::where('email', $email)->first();

    })

    ->setGetUserByIdCallback(function ($id) {

        return User::find($id);

    })

    ->setCreateUserCallback(function ($data) {

        return User::create($data);

    });

$auth->setAuthProvider($provider);
```

---

## Laravel

```php
<?php

use Bmvc\BAuth\Adapters\Laravel\LaravelAuthProvider;

$provider = new LaravelAuthProvider(
    $config,
    'users'
);

$auth->setAuthProvider($provider);
```

---

## Symfony

```php
<?php

use Bmvc\BAuth\Adapters\Symfony\SymfonyAuthProvider;

$provider = new SymfonyAuthProvider(
    $config,
    $entityManager,
    App\Entity\User::class
);

$auth->setAuthProvider($provider);
```

---

# 🔐 Authentification

## Connexion utilisateur

```php
<?php

try {

    $result = $auth->login(
        'user@example.com',
        'password123'
    );

    $user = $result['user'];
    $token = $result['token'];

} catch (\Bmvc\BAuth\Exceptions\AuthenticationException $e) {

    echo $e->getMessage();
}
```

---

## Vérifier l’utilisateur connecté

```php
<?php

if ($auth->isAuthenticated()) {

    $user = $auth->user();

    echo $user['email'];
}
```

---

## Déconnexion

```php
<?php

$auth->logout();
```

---

# 🔑 JWT

## Obtenir le token actuel

```php
<?php

$token = $auth->token();
```

---

## Vérifier un token

```php
<?php

try {

    $payload = $auth->verifyToken($token);

} catch (\Bmvc\BAuth\Exceptions\InvalidTokenException $e) {

    echo "Token invalide";
}
```

---

## Rafraîchir un token

```php
<?php

$newToken = $auth->refreshToken();
```

---

# 🛡️ Autorisation

BAuth supporte les rôles et permissions via un provider dédié.

---

## Vérifier une permission

```php
<?php

if ($auth->can('posts.edit')) {

    echo "Autorisé";
}
```

---

## Vérifier un rôle

```php
<?php

if ($auth->hasRole('admin')) {

    echo "Administrateur";
}
```

---

## Autoriser une action

```php
<?php

$auth->authorize('users.delete');
```

---

# 🔒 Authentification à deux facteurs (2FA)

BAuth supporte le TOTP pour renforcer la sécurité des comptes utilisateurs.

```php
<?php

if ($auth->verify2FA($code)) {

    echo "Code valide";
}
```

---

# 🧩 Architecture

```text
src/
├── Auth.php
├── Config.php
│
├── Contracts/
│   ├── AuthProviderInterface.php
│   ├── AuthorizationProviderInterface.php
│   ├── SessionProviderInterface.php
│   ├── TokenProviderInterface.php
│   └── TwoFactorProviderInterface.php
│
├── Providers/
│   ├── BaseAuthProvider.php
│   ├── BaseAuthorizationProvider.php
│   ├── BaseTwoFactorProvider.php
│   ├── JWTProvider.php
│   └── SessionProvider.php
│
├── Support/
│   └── Password.php
│
├── Adapters/
│   ├── Generic/
│   ├── BMVC/
│   ├── Laravel/
│   ├── PDO/
│   └── Symfony/
│
└── Exceptions/
    ├── BAuthException.php
    ├── AuthenticationException.php
    ├── AuthorizationException.php
    ├── InvalidTokenException.php
    └── UserNotFoundException.php
```

---

# 🔐 Sécurité

BAuth applique plusieurs standards modernes de sécurité :

- bcrypt pour le hashage des mots de passe
- JWT signés avec HMAC SHA-256
- validation stricte des tokens
- support du 2FA TOTP
- protection contre les replay attacks
- architecture PSR-4 sécurisée et extensible

---

# 🧪 Tests

Exécuter les tests :

```bash
composer test
```

---

# 📚 Documentation

## Guides disponibles

- Installation
- Configuration
- Authentication
- Authorization
- JWT
- Sessions
- 2FA
- Middleware
- Laravel
- Symfony
- Custom Providers
- Testing

---

# 🛣️ Roadmap

- [x] JWT Authentication
- [x] Roles & Permissions
- [x] 2FA / TOTP
- [x] Session Management
- [x] OAuth2
- [x] Social Login
- [x] API Keys
- [x] Multi-session Management
- [x] WebAuthn / Passkeys

---

# 🤝 Contribution

Les contributions sont les bienvenues.

1. Fork du projet
2. Créer une branche
3. Commit des modifications
4. Push sur votre fork
5. Ouvrir une Pull Request

---

# 📄 Licence

MIT License

---

# 💡 Vision du projet

BAuth a pour objectif d’offrir :

- la simplicité de Laravel Auth
- la flexibilité de Symfony Security
- l’indépendance d’une librairie standalone
- une architecture moderne, modulaire et extensible

Le tout dans une seule librairie PHP légère, performante et professionnelle.
