# Guide d'installation de BAuth

## Prérequis

- PHP 8.0 ou supérieur
- Composer
- Une base de données (MySQL, PostgreSQL, SQLite, etc.)

## Installation via Composer

```bash
composer require bauth/bauth
```

## Configuration basique

### 1. Créer le fichier .env

```bash
cp .env.example .env
```

### 2. Configurer les variables d'environnement

```env
# JWT
AUTH_JWT_SECRET=votre-clé-secrète-très-importante-ici
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

### 3. Générer une clé secrète JWT

```bash
php -r "echo bin2hex(random_bytes(32));"
```

Utilisez cette valeur pour `AUTH_JWT_SECRET`.

### 4. Créer la base de données

#### MySQL/MariaDB

```sql
CREATE DATABASE bauth_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bauth_db;

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    username VARCHAR(255) UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE user_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_role (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

CREATE TABLE role_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_role_permission (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

-- Pour 2FA (optionnel)
ALTER TABLE users ADD COLUMN totp_secret VARCHAR(255);
ALTER TABLE users ADD COLUMN two_factor_enabled BOOLEAN DEFAULT FALSE;
```

#### PostgreSQL

```sql
CREATE DATABASE bauth_db WITH ENCODING 'UTF8';
\c bauth_db;

CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    username VARCHAR(255) UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE roles (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE permissions (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE user_roles (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    role_id INT NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, role_id)
);

CREATE TABLE role_permissions (
    id SERIAL PRIMARY KEY,
    role_id INT NOT NULL REFERENCES roles(id) ON DELETE CASCADE,
    permission_id INT NOT NULL REFERENCES permissions(id) ON DELETE CASCADE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(role_id, permission_id)
);

-- Pour 2FA
ALTER TABLE users ADD COLUMN totp_secret VARCHAR(255);
ALTER TABLE users ADD COLUMN two_factor_enabled BOOLEAN DEFAULT FALSE;
```

#### SQLite

```sql
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email TEXT UNIQUE NOT NULL,
    username TEXT UNIQUE,
    password TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE user_roles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    role_id INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_id, role_id),
    FOREIGN KEY(user_id) REFERENCES users(id),
    FOREIGN KEY(role_id) REFERENCES roles(id)
);

CREATE TABLE role_permissions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    role_id INTEGER NOT NULL,
    permission_id INTEGER NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(role_id, permission_id),
    FOREIGN KEY(role_id) REFERENCES roles(id),
    FOREIGN KEY(permission_id) REFERENCES permissions(id)
);

-- Pour 2FA
ALTER TABLE users ADD COLUMN totp_secret TEXT;
ALTER TABLE users ADD COLUMN two_factor_enabled INTEGER DEFAULT 0;
```

## Initialisation dans votre application

### PHP pur

```php
<?php

require 'vendor/autoload.php';

use BAuth\Config;
use BAuth\Auth;
use BAuth\Examples\PDO\PDOAuthProvider;

// Configuration
$config = new Config([
    'jwt' => [
        'secret' => $_ENV['AUTH_JWT_SECRET'],
        'expiresIn' => 3600,
    ]
]);

// Connexion à la base de données
$pdo = new PDO(
    'mysql:host=localhost;dbname=bauth_db',
    'root',
    ''
);

// Initialiser Auth
$auth = new Auth($config);
$authProvider = new PDOAuthProvider($config, $pdo, 'users');
$auth->setAuthProvider($authProvider);
```

### Laravel

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use BAuth\Config;
use BAuth\Auth;
use BAuth\Examples\Laravel\LaravelAuthProvider;

class BAuthServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('bauth', function () {
            $config = new Config([
                'jwt' => [
                    'secret' => env('AUTH_JWT_SECRET'),
                    'expiresIn' => 3600,
                ]
            ]);

            $auth = new Auth($config);
            $authProvider = new LaravelAuthProvider($config, 'users');
            $auth->setAuthProvider($authProvider);

            return $auth;
        });
    }
}
```

Enregistrez le provider dans `config/app.php` :

```php
'providers' => [
    // ...
    App\Providers\BAuthServiceProvider::class,
],
```

### Symfony

```php
<?php

namespace App\Service;

use BAuth\Config;
use BAuth\Auth;
use BAuth\Examples\Symfony\SymfonyAuthProvider;
use Doctrine\ORM\EntityManagerInterface;

class BAuthService
{
    private Auth $auth;

    public function __construct(
        EntityManagerInterface $entityManager,
        string $jwtSecret
    ) {
        $config = new Config([
            'jwt' => [
                'secret' => $jwtSecret,
                'expiresIn' => 3600,
            ]
        ]);

        $this->auth = new Auth($config);
        $authProvider = new SymfonyAuthProvider(
            $config,
            $entityManager,
            'App\\Entity\\User'
        );
        $this->auth->setAuthProvider($authProvider);
    }

    public function getAuth(): Auth
    {
        return $this->auth;
    }
}
```

Enregistrez le service dans `config/services.yaml` :

```yaml
services:
  App\Service\BAuthService:
    arguments:
      $jwtSecret: "%env(AUTH_JWT_SECRET)%"
```

## Vérification de l'installation

Créez un fichier `test_install.php` :

```php
<?php

require 'vendor/autoload.php';

echo "Vérification de l'installation de BAuth...\n\n";

// Vérifier les classes
$classes = [
    'BAuth\Auth',
    'BAuth\Config',
    'BAuth\Contracts\AuthProviderInterface',
    'BAuth\Contracts\TokenProviderInterface',
    'Firebase\JWT\JWT',
];

foreach ($classes as $class) {
    $exists = class_exists($class);
    echo ($exists ? '✓' : '✗') . " $class\n";
}

echo "\n✓ Installation complète!\n";
```

Exécutez-le :

```bash
php test_install.php
```

## Prochaines étapes

- Consultez le [Guide d'utilisation](USAGE.md)
- Intégrez avec votre framework: [Laravel](LARAVEL.md), [Symfony](SYMFONY.md)
- Explorez les [Exemples](../examples/)
