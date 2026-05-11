# Installation de BAuth

> Guide complet pour installer et configurer BAuth dans votre application PHP.

---

## ⚡ Installation rapide (3 minutes)

Si vous êtes impatient, voici le chemin le plus court :

### 1. Installer via Composer

```bash
composer require bmvc/bauth
```

### 2. Créer configuration basique

```php
<?php
use Bmvc\BAuth\Auth;
use Bmvc\BAuth\Config;

$config = new Config([
    'jwt' => ['secret' => 'votre-clé-secrète', 'expiresIn' => 3600],
    'password' => ['algorithm' => PASSWORD_BCRYPT, 'options' => ['cost' => 12]],
]);

$auth = new Auth($config);
```

### 3. C'est tout! 🎉

Pour plus de détails, continuez ci-dessous.

---

## 📋 Prérequis

Avant d'installer BAuth, assurez-vous d'avoir :

- ✅ **PHP 8.1 ou supérieur**
- ✅ **Composer** (gestionnaire de dépendances PHP)
- ✅ **Une base de données** (MySQL, PostgreSQL, ou SQLite)
- ✅ **Une connexion PDO** vers la base de données

---

## 📦 Étape 1 : Installation via Composer

Installer la librairie avec Composer :

```bash
composer require bmvc/bauth
```

**Qu'est-ce qui se passe?**

- BAuth est téléchargée dans le dossier `vendor/`
- L'autoloader Composer est configuré

✅ **Vérification:** Vérifier que `vendor/bAuth/` existe

```bash
ls vendor/bAuth/
```

---

## 🔐 Étape 2 : Générer une clé JWT secrète

**Pourquoi?** La clé secrète sert à signer vos tokens JWT. Elle doit être très sécurisée et aléatoire.

### Générer une clé

Exécutez cette commande :

```bash
php -r "echo bin2hex(random_bytes(32));"
```

Output:

```
a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1
```

**Gardez cette valeur** - vous en aurez besoin à l'étape suivante!

---

## ⚙️ Étape 3 : Configuration de l'environnement

### Créer le fichier `.env`

À la racine de votre projet :

```bash
cp .env.example .env
```

Ou créer manuellement :

```bash
touch .env
```

### Configurer les variables d'environnement

Éditez `.env` avec vos valeurs :

```env
# JWT - Utilisez la valeur générée à l'étape 2
AUTH_JWT_SECRET=a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1
AUTH_JWT_ALGORITHM=HS256
AUTH_JWT_EXPIRES_IN=3600
AUTH_JWT_REFRESH_EXPIRES_IN=604800

# Mot de passe - Algorithme de hachage
AUTH_PASSWORD_ALGORITHM=2y
AUTH_PASSWORD_COST=12

# Session
AUTH_SESSION_NAME=bauth_session
AUTH_SESSION_LIFETIME=7200

# Base de données
DB_HOST=localhost
DB_PORT=3306
DB_NAME=bauth_db
DB_USER=root
DB_PASSWORD=

# 2FA (optionnel pour commencer)
AUTH_2FA_ENABLED=false
AUTH_2FA_WINDOW=1
```

**Explications:**

- `AUTH_JWT_SECRET`: Votre clé secrète (générée à l'étape 2)
- `AUTH_PASSWORD_COST`: Plus haut = plus sécurisé mais plus lent (12-14 conseillé)
- `DB_*`: Vos informations de connexion base de données

---

## 🗄️ Étape 4 : Préparer la base de données

### Créer la base de données

#### MySQL / MariaDB

```bash
mysql -u root -p
```

```sql
CREATE DATABASE bauth_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

#### PostgreSQL

```bash
psql -U postgres
```

```sql
CREATE DATABASE bauth_db;
```

#### SQLite

```bash
sqlite3 bauth.db
```

### Créer les tables

**Table `users`** (utilisateurs)

```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    username VARCHAR(255) UNIQUE,
    password VARCHAR(255) NOT NULL,
    totp_secret VARCHAR(255) NULL,
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Table `roles`** (rôles)

```sql
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Table `permissions`** (permissions)

```sql
CREATE TABLE permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

**Table `user_roles`** (liaison utilisateurs-rôles)

```sql
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

**Table `role_permissions`** (liaison rôles-permissions)

```sql
CREATE TABLE role_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_role_permission (role_id, permission_id),

    FOREIGN KEY (role_id)
        REFERENCES roles(id)
        ON DELETE CASCADE,

    FOREIGN KEY (permission_id)
        REFERENCES permissions(id)
        ON DELETE CASCADE
);
```

**Table `revoked_tokens`** (tokens révoqués)

```sql
CREATE TABLE revoked_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    token_hash VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_expires_at (expires_at)
);
```

---

## 🚀 Étape 5 : Initialiser BAuth dans votre application

### PHP natif ou projet générique

```php
<?php

require 'vendor/autoload.php';

use PDO;
use Bmvc\BAuth\Auth;
use Bmvc\BAuth\Config;
use Bmvc\BAuth\Adapters\Generic\GenericAuthProvider;

// 1. Configuration
$config = new Config([
    'jwt' => [
        'secret' => $_ENV['AUTH_JWT_SECRET'] ?? 'dev-secret',
        'expiresIn' => 3600,
    ],
    'password' => [
        'algorithm' => PASSWORD_BCRYPT,
        'options' => ['cost' => 12],
    ]
]);

// 2. Connexion base de données
$pdo = new PDO(
    'mysql:host=localhost;dbname=bauth_db;charset=utf8mb4',
    $_ENV['DB_USER'],
    $_ENV['DB_PASSWORD']
);

// 3. Initialiser Auth
$auth = new Auth($config);

// 4. Configurer le provider
$provider = new GenericAuthProvider($config);
$provider->setConnection($pdo);

$auth->setAuthProvider($provider);

// 5. Utiliser!
$result = $auth->login('user@example.com', 'password123');
$user = $result['user'];
$token = $result['token'];
```

### Laravel

Voir [Guide Laravel](LARAVEL.md)

### Symfony

Voir [Guide Symfony](SYMFONY.md)

---

## ✅ Vérifier l'installation

### Tester la configuration

```php
<?php

try {
    // Vérifier la configuration
    echo "JWT Secret: " . strlen($config->get('jwt.secret')) . " caractères\n";

    // Vérifier la connexion DB
    $pdo->query('SELECT 1');
    echo "✅ Base de données connectée\n";

    // Vérifier les tables
    $result = $pdo->query("SHOW TABLES")->fetchAll();
    echo "✅ Tables trouvées: " . count($result) . "\n";

    // Test rapide d'authentification
    // (créer un utilisateur de test d'abord)

} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage();
}
```

---

## 🐛 Problèmes courants

### "PDO: Connection refused"

**Cause:** La base de données n'est pas accessible

**Solution:**

```bash
# Vérifier que MySQL est en cours d'exécution
mysql -u root -p -h localhost

# Vérifier les paramètres de connexion
# DB_HOST, DB_PORT, DB_USER, DB_PASSWORD dans .env
```

### "Class not found: Bmvc\BAuth\Auth"

**Cause:** L'autoloader Composer n'est pas chargé

**Solution:**

```php
<?php
// Assurez-vous d'avoir:
require 'vendor/autoload.php';
```

### "JWT Secret not set"

**Cause:** La variable d'environnement n'est pas définie

**Solution:**

```bash
# Générer une nouvelle clé
php -r "echo bin2hex(random_bytes(32));"

# Ajouter au .env
AUTH_JWT_SECRET=<votre-clé-générée>
```

### "Table users not found"

**Cause:** Les tables n'ont pas été créées

**Solution:**

```bash
# Exécuter les scripts SQL fournis (voir Étape 4)
mysql -u root -p bauth_db < setup_tables.sql
```

---

## 📚 Prochaines étapes

1. **Nouveau?** → [Guide d'utilisation](USAGE.md)
2. **Intégration Laravel?** → [Guide Laravel](LARAVEL.md)
3. **Intégration Symfony?** → [Guide Symfony](SYMFONY.md)
4. **Sécurité?** → [Guide de sécurité](SECURITY.md)
5. **Problème?** → [Dépannage](TROUBLESHOOTING.md)
