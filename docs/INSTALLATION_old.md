# Installation de BAuth

Ce guide explique comment installer et configurer BAuth dans votre application PHP.

---

# 📋 Prérequis

Avant d’installer BAuth, assurez-vous d’avoir :

- PHP 8.1 ou supérieur
- Composer
- Une base de données :
  - MySQL / MariaDB
  - PostgreSQL
  - SQLite

---

# 📦 Installation via Composer

Installer la librairie avec Composer :

```bash id="mwfw7r"
composer require bmvc/bauth
```

---

# ⚙️ Configuration de l’environnement

## 1. Créer le fichier `.env`

```bash id="w4xz5n"
cp .env.example .env
```

---

## 2. Configurer les variables d’environnement

```env id="46s5xp"
# JWT
AUTH_JWT_SECRET=your-super-secret-key
AUTH_JWT_ALGORITHM=HS256
AUTH_JWT_EXPIRES_IN=3600
AUTH_JWT_REFRESH_EXPIRES_IN=604800

# Password
AUTH_PASSWORD_ALGORITHM=2y
AUTH_PASSWORD_COST=12

# Session
AUTH_SESSION_NAME=bauth_session
AUTH_SESSION_LIFETIME=7200

# Database
DB_HOST=localhost
DB_PORT=3306
DB_NAME=bauth_db
DB_USER=root
DB_PASSWORD=

# 2FA
AUTH_2FA_ENABLED=false
AUTH_2FA_WINDOW=1
```

---

# 🔑 Générer une clé JWT

Exécuter :

```bash id="4ql4m7"
php -r "echo bin2hex(random_bytes(32));"
```

Puis utiliser la valeur générée pour :

```env id="pf4y2p"
AUTH_JWT_SECRET=
```

---

# 🗄️ Configuration de la base de données

## MySQL / MariaDB

Créer la base de données :

```sql id="d4jj2e"
CREATE DATABASE bauth_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

---

## Structure minimale des tables

### Table `users`

```sql id="sg7fwi"
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    username VARCHAR(255) UNIQUE,
    password VARCHAR(255) NOT NULL,
    totp_secret VARCHAR(255) NULL,
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
);
```

---

### Table `roles`

```sql id="u4egj7"
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

### Table `permissions`

```sql id="jlwmok"
CREATE TABLE permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

### Table `user_roles`

```sql id="q4cfbf"
CREATE TABLE user_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_user_role (user_id, role_id),

    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,

    FOREIGN KEY (role_id)
        REFERENCES roles(id)
        ON DELETE CASCADE
);
```

---

### Table `role_permissions`

```sql id="4e9k7h"
CREATE TABLE role_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_role_permission (
        role_id,
        permission_id
    ),

    FOREIGN KEY (role_id)
        REFERENCES roles(id)
        ON DELETE CASCADE,

    FOREIGN KEY (permission_id)
        REFERENCES permissions(id)
        ON DELETE CASCADE
);
```

---

### Table `revoked_tokens`

```sql id="xt3bb8"
CREATE TABLE revoked_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    token_hash VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

# 🚀 Initialisation de BAuth

## PHP natif

```php id="bqphn2"
<?php

require 'vendor/autoload.php';

use PDO;
use Bmvc\BAuth\Auth;
use Bmvc\BAuth\Config;
use Bmvc\BAuth\Adapters\Generic\GenericAuthProvider;

$config = new Config([
    'jwt' => [
        'secret' => $_ENV['AUTH_JWT_SECRET'],
        'expiresIn' => 3600,
    ]
]);

$pdo = new PDO(
    'mysql:host=localhost;dbname=bauth_db',
    'root',
    ''
);

$auth = new Auth($config);

$provider = new GenericAuthProvider($config);

$provider->setGetUserByEmailCallback(
    function ($email) use ($pdo) {

        $stmt = $pdo->prepare(
            "SELECT * FROM users WHERE email = ?"
        );

        $stmt->execute([$email]);

        return $stmt->fetch();
    }
);

$auth->setAuthProvider($provider);
```

---

# ⚡ Laravel

```php id="5yq0az"
<?php

use Bmvc\BAuth\Auth;
use Bmvc\BAuth\Config;
use Bmvc\BAuth\Adapters\Laravel\LaravelAuthProvider;

$config = new Config([
    'jwt' => [
        'secret' => env('AUTH_JWT_SECRET'),
        'expiresIn' => 3600,
    ]
]);

$auth = new Auth($config);

$provider = new LaravelAuthProvider(
    $config,
    'users'
);

$auth->setAuthProvider($provider);
```

---

# ⚡ Symfony

```php id="0d6tnr"
<?php

use Bmvc\BAuth\Auth;
use Bmvc\BAuth\Config;
use Doctrine\ORM\EntityManagerInterface;
use Bmvc\BAuth\Adapters\Symfony\SymfonyAuthProvider;

$config = new Config([
    'jwt' => [
        'secret' => $_ENV['AUTH_JWT_SECRET'],
        'expiresIn' => 3600,
    ]
]);

$auth = new Auth($config);

$provider = new SymfonyAuthProvider(
    $config,
    $entityManager,
    App\Entity\User::class
);

$auth->setAuthProvider($provider);
```

---

# 🧪 Vérifier l’installation

Créer un fichier :

```text id="jpcq1f"
test_install.php
```

---

## Contenu du fichier

```php id="um4yzj"
<?php

require 'vendor/autoload.php';

echo "Checking BAuth installation...\n\n";

$classes = [

    'Bmvc\BAuth\Auth',
    'Bmvc\BAuth\Config',
    'Bmvc\BAuth\Contracts\AuthProviderInterface',
    'Bmvc\BAuth\Contracts\TokenProviderInterface',

];

foreach ($classes as $class) {

    $exists = class_exists($class);

    echo ($exists ? '✓' : '✗')
        . " {$class}\n";
}

echo "\n✓ Installation complete!\n";
```

---

## Exécuter le test

```bash id="ifqlhp"
php test_install.php
```

---

# 🆕 Installation des nouvelles fonctionnalités

## OAuth2

Aucune installation supplémentaire requise. Consultez [OAUTH2.md](OAUTH2.md)

## Connexion Sociale

Aucune installation supplémentaire requise. Consultez [SOCIAL_LOGIN.md](SOCIAL_LOGIN.md)

## Clés API

### Base de données - Créer la table

```sql
CREATE TABLE api_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    api_key VARCHAR(255) UNIQUE NOT NULL,
    secret_hash VARCHAR(255) NOT NULL,
    permissions JSON,
    expires_at DATETIME,
    last_used_at DATETIME,
    revoked BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_api_key (api_key),
    INDEX idx_user_id (user_id)
);
```

Consultez [API_KEYS.md](API_KEYS.md)

## Sessions Multiples

### Base de données - Créer la table

```sql
CREATE TABLE sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(255) UNIQUE NOT NULL,
    device_name VARCHAR(255),
    user_agent TEXT,
    ip_address VARCHAR(45),
    active BOOLEAN DEFAULT TRUE,
    suspicious BOOLEAN DEFAULT FALSE,
    last_activity DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_session_id (session_id)
);
```

Consultez [MULTI_SESSION.md](MULTI_SESSION.md)

## WebAuthn / Passkeys

### Base de données - Créer les tables

```sql
CREATE TABLE webauthn_credentials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    credential_id VARCHAR(255) UNIQUE NOT NULL,
    credential_name VARCHAR(255),
    public_key LONGTEXT,
    sign_count INT DEFAULT 0,
    backup_eligible JSON,
    backup_state BOOLEAN DEFAULT FALSE,
    passwordless_enabled BOOLEAN DEFAULT TRUE,
    last_used_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
);

CREATE TABLE webauthn_backup_codes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    code_hash VARCHAR(255) NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
);
```

Consultez [WEBAUTHN.md](WEBAUTHN.md)

# 📚 Prochaines étapes

Après l'installation :

- Configurer l'authentification
- Générer des JWT
- Configurer les rôles et permissions
- Activer le 2FA
- Intégrer BAuth avec votre framework
- Configurer OAuth2 et la connexion sociale
- Configurer les clés API
- Configurer les sessions multiples
- Configurer WebAuthn / Passkeys

Documentation disponible :

- `USAGE.md`
- `JWT.md`
- `AUTHORIZATION.md`
- `LARAVEL.md`
- `SYMFONY.md`
- `TWO_FACTOR.md`
- `OAUTH2.md`
- `SOCIAL_LOGIN.md`
- `API_KEYS.md`
- `MULTI_SESSION.md`
- `WEBAUTHN.md`
