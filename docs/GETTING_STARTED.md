# 🚀 Getting Started — BAuth

> Apprenez à mettre en place l'authentification en 30 minutes. Parfait pour commencer!

---

## 📋 Prérequis

- ✅ PHP 8.1+
- ✅ Composer
- ✅ Une base de données (MySQL, PostgreSQL, SQLite)
- ⏱️ 30 minutes

---

## 🎯 Votre mission

À la fin de ce guide, vous aurez:

- ✅ BAuth installé dans votre projet
- ✅ Une base de données configurée
- ✅ Un formulaire de connexion fonctionnel
- ✅ Un système d'authentification complet

---

## ⚡ Étapes rapides

### Étape 1: Installation (2 min)

```bash
composer require bmvc/bauth
```

### Étape 2: Configuration (3 min)

Créer `.env` à la racine:

```env
AUTH_JWT_SECRET=a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3x4y5z6a7b8c9d0e1
DB_HOST=localhost
DB_PORT=3306
DB_NAME=auth_db
DB_USER=root
DB_PASSWORD=
```

**Générer une nouvelle clé JWT:**

```bash
php -r "echo bin2hex(random_bytes(32));"
```

### Étape 3: Base de données (5 min)

Créer la base de données:

```bash
mysql -u root -p
CREATE DATABASE auth_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Créer les tables (MySQL):

```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) UNIQUE NOT NULL
);

CREATE TABLE permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) UNIQUE NOT NULL
);

CREATE TABLE user_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    UNIQUE KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

CREATE TABLE role_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    UNIQUE KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
);

CREATE TABLE revoked_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    token_hash VARCHAR(255) NOT NULL UNIQUE,
    expires_at TIMESTAMP NOT NULL
);
```

### Étape 4: Initialiser BAuth (5 min)

Créer `auth.php`:

```php
<?php

require 'vendor/autoload.php';

use Bmvc\BAuth\Auth;
use Bmvc\BAuth\Config;
use Bmvc\BAuth\Adapters\Generic\GenericAuthProvider;
use PDO;

// Configuration
$config = new Config([
    'jwt' => [
        'secret' => $_ENV['AUTH_JWT_SECRET'] ?? 'dev-secret',
        'expiresIn' => 3600,
    ],
    'password' => [
        'algorithm' => PASSWORD_BCRYPT,
        'options' => ['cost' => 12],
    ],
]);

// Connexion DB
$pdo = new PDO(
    'mysql:host=localhost;dbname=auth_db;charset=utf8mb4',
    'root',
    ''
);

// Créer Auth
$auth = new Auth($config);

// Configurer le provider
$provider = new GenericAuthProvider($config);
$provider->setConnection($pdo);
$auth->setAuthProvider($provider);

// C'est prêt!
```

### Étape 5: Formulaire de connexion (8 min)

Créer `login.html`:

```html
<!DOCTYPE html>
<html>
  <head>
    <title>Connexion</title>
    <style>
      body {
        font-family: Arial;
        max-width: 400px;
        margin: 100px auto;
      }
      form {
        border: 1px solid #ddd;
        padding: 20px;
        border-radius: 5px;
      }
      input {
        width: 100%;
        padding: 8px;
        margin: 10px 0;
        border: 1px solid #ddd;
        box-sizing: border-box;
      }
      button {
        width: 100%;
        padding: 10px;
        background: #007bff;
        color: white;
        border: none;
        cursor: pointer;
        border-radius: 3px;
      }
      button:hover {
        background: #0056b3;
      }
      .error {
        color: red;
        padding: 10px;
        background: #ffe0e0;
        border-radius: 3px;
      }
    </style>
  </head>
  <body>
    <h1>Connexion</h1>

    <form method="POST" action="login.php">
      <?php if (isset($error)): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <input type="email" name="email" placeholder="Email" required />
      <input
        type="password"
        name="password"
        placeholder="Mot de passe"
        required
      />
      <button type="submit">Se connecter</button>
    </form>

    <p>Pas encore inscrit? <a href="register.html">Créer un compte</a></p>
  </body>
</html>
```

### Étape 6: Script de connexion (5 min)

Créer `login.php`:

```php
<?php

require 'auth.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    try {
        // Tentative de connexion
        $result = $auth->login($email, $password);

        // ✅ Succès!
        $_SESSION['user_id'] = $result['user']['id'];
        $_SESSION['token'] = $result['token'];

        // Redirection
        header('Location: dashboard.php');
        exit;

    } catch (Exception $e) {
        // ❌ Erreur
        $error = "Email ou mot de passe incorrect";
    }
}

?>
<?php include 'login.html'; ?>
```

### Étape 7: Page protégée (3 min)

Créer `dashboard.php`:

```php
<?php

require 'auth.php';

// Vérifier si connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user = $auth->getAuthProvider()->getUserById($_SESSION['user_id']);

?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #ddd; padding-bottom: 20px; }
        button { padding: 10px 20px; background: #dc3545; color: white; border: none; cursor: pointer; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Bienvenue, <?= htmlspecialchars($user['email']) ?>!</h1>
        <form method="POST" action="logout.php" style="margin: 0;">
            <button type="submit">Déconnexion</button>
        </form>
    </div>

    <p>Vous êtes connecté. Commencez à construire!</p>
</body>
</html>
```

### Étape 8: Déconnexion (1 min)

Créer `logout.php`:

```php
<?php

require 'auth.php';

// Déconnexion
$auth->logout();
session_destroy();

// Redirection
header('Location: login.php');
exit;
```

---

## ✅ Test votre installation

### 1. Créer un utilisateur de test

```bash
php -r "
require 'auth.php';

\$user = \$auth->getAuthProvider()->createUser([
    'email' => 'test@example.com',
    'password' => 'password123'
]);

echo 'Utilisateur créé: ' . \$user['id'];
"
```

### 2. Tester la connexion

Ouvrir `http://localhost/login.php` et essayer:

- Email: `test@example.com`
- Mot de passe: `password123`

### 3. Vérifier le dashboard

Si la connexion réussit, vous devriez voir: `Bienvenue, test@example.com!`

---

## 🎓 Prochaines étapes

### Niveau suivant

1. **[Ajouter la 2FA](USAGE.md#2fa)** — Sécuriser davantage vos utilisateurs
2. **[Gérer les rôles](USAGE.md#autorisation)** — Admin, modérateur, utilisateur
3. **[Créer une API JSON](USAGE.md#exemple-3-api-json-avec-jwt)** — Pour les apps mobiles
4. **[OAuth2](OAUTH2.md)** — Connexion avec Google/Facebook

### Documentation complète

- 📖 [Guide d'utilisation](USAGE.md) — Toutes les fonctionnalités
- 🔒 [Sécurité](SECURITY.md) — Bonnes pratiques
- 🚨 [Dépannage](TROUBLESHOOTING.md) — Problèmes courants
- 🔌 [Intégrations](LARAVEL.md) — Laravel, Symfony, etc.

---

## 💡 Astuces

**Session vs JWT?**

- Utiliser **sessions** pour les sites web traditionnel
- Utiliser **JWT** pour les API REST et les SPAs

**Sécuriser votre `.env`**

```bash
# Ne pas commit dans Git!
echo ".env" >> .gitignore
```

**Tester rapidement**

```bash
# Démarrer un serveur local
php -S localhost:8000

# Accéder à http://localhost:8000/login.php
```

---

## 🆘 Problèmes courants?

- **"PDO: Connection refused"** → Vérifier que MySQL est lancé
- **"Class not found"** → Vérifier que `vendor/autoload.php` est inclus
- **"Table not found"** → Exécuter les scripts SQL

👉 Voir [Dépannage](TROUBLESHOOTING.md) pour plus d'aide.

---

## 🎉 Félicitations!

Vous avez un système d'authentification complètement fonctionnel!

**Maintenant, quoi faire?**

- ✅ Lire [USAGE.md](USAGE.md) pour maîtriser toutes les fonctionnalités
- ✅ Adapter à votre framework (Laravel, Symfony)
- ✅ Ajouter des fonctionnalités (2FA, OAuth2, etc.)
- ✅ Sécuriser votre application (HTTPS, rate limiting, etc.)

Bonne chance! 🚀
